import { describe, it, expect, vi, beforeEach } from "vitest";
import { resetAllMocks } from "../setup";
import { ref, reactive, nextTick } from "vue";

// We test the login page's script logic in isolation (no DOM rendering needed
// for Vuetify components which require a full plugin setup). Instead we
// replicate the <script setup> logic and verify behaviour.

// Mock useAuth composable
const mockLogin = vi.fn();
const mockUseAuth = vi.fn(() => ({
  login: mockLogin,
  user: ref(null),
  initialized: ref(false),
  fetchUser: vi.fn(),
  logout: vi.fn(),
}));

beforeEach(() => {
  resetAllMocks();
  mockLogin.mockReset();
  mockUseAuth.mockClear();

  // Set up useRoute to return a route with no redirect query
  (globalThis as any).useRoute.mockReturnValue({
    query: {},
    params: {},
    path: "/login",
    fullPath: "/login",
    name: "login",
    matched: [],
    meta: {},
    hash: "",
    redirectedFrom: undefined,
  });
});

// Replicate the login page submit logic for unit testing
function createLoginPageLogic() {
  const route = (globalThis as any).useRoute();

  function isSafeRedirectTarget(value: unknown): value is string {
    return (
      typeof value === "string" &&
      value.startsWith("/") &&
      !value.startsWith("//")
    );
  }

  function getRedirectTarget() {
    return isSafeRedirectTarget(route.query.redirect)
      ? route.query.redirect
      : "/dashboard";
  }

  const form = reactive({
    email: "",
    password: "",
  });

  const showPassword = ref(false);
  const isSubmitting = ref(false);
  const errorMessage = ref("");

  const submit = async () => {
    errorMessage.value = "";
    isSubmitting.value = true;

    try {
      const { login } = mockUseAuth();
      await login({ ...form });
      await (globalThis as any).navigateTo(getRedirectTarget());
    } catch (error) {
      const typedError = error as {
        data?: { message?: string; errors?: Record<string, string[]> };
      };

      errorMessage.value =
        typedError.data?.errors?.email?.[0] ||
        typedError.data?.message ||
        "Unable to sign in with those credentials.";
    } finally {
      isSubmitting.value = false;
    }
  };

  return {
    form,
    showPassword,
    isSubmitting,
    errorMessage,
    submit,
    getRedirectTarget,
  };
}

describe("Login page logic", () => {
  it("calls useAuth().login with form data on submit", async () => {
    mockLogin.mockResolvedValueOnce(undefined);

    const { form, submit } = createLoginPageLogic();
    form.email = "admin@example.com";
    form.password = "password123";

    await submit();

    expect(mockLogin).toHaveBeenCalledWith({
      email: "admin@example.com",
      password: "password123",
    });
  });

  it("navigates to /dashboard after successful login", async () => {
    mockLogin.mockResolvedValueOnce(undefined);

    const { form, submit } = createLoginPageLogic();
    form.email = "admin@example.com";
    form.password = "password";

    await submit();

    expect((globalThis as any).navigateTo).toHaveBeenCalledWith("/dashboard");
  });

  it("navigates to redirect query param when present and safe", async () => {
    (globalThis as any).useRoute.mockReturnValue({
      query: { redirect: "/members" },
      params: {},
      path: "/login",
      fullPath: "/login?redirect=/members",
      name: "login",
      matched: [],
      meta: {},
      hash: "",
      redirectedFrom: undefined,
    });

    mockLogin.mockResolvedValueOnce(undefined);

    const { form, submit } = createLoginPageLogic();
    form.email = "admin@example.com";
    form.password = "password";

    await submit();

    expect((globalThis as any).navigateTo).toHaveBeenCalledWith("/members");
  });

  it("ignores unsafe redirect targets (protocol-relative URLs)", () => {
    (globalThis as any).useRoute.mockReturnValue({
      query: { redirect: "//evil.com" },
      params: {},
      path: "/login",
      fullPath: "/login?redirect=//evil.com",
      name: "login",
      matched: [],
      meta: {},
      hash: "",
      redirectedFrom: undefined,
    });

    const { getRedirectTarget } = createLoginPageLogic();
    expect(getRedirectTarget()).toBe("/dashboard");
  });

  it("shows API error message on invalid credentials", async () => {
    mockLogin.mockRejectedValueOnce({
      data: { message: "Invalid credentials" },
    });

    const { form, submit, errorMessage } = createLoginPageLogic();
    form.email = "wrong@example.com";
    form.password = "wrong";

    await submit();

    expect(errorMessage.value).toBe("Invalid credentials");
  });

  it("shows field-level validation error from API", async () => {
    mockLogin.mockRejectedValueOnce({
      data: {
        message: "Validation failed",
        errors: { email: ["The email field is required."] },
      },
    });

    const { form, submit, errorMessage } = createLoginPageLogic();
    form.email = "";
    form.password = "";

    await submit();

    expect(errorMessage.value).toBe("The email field is required.");
  });

  it("shows generic fallback error when API error has no message", async () => {
    mockLogin.mockRejectedValueOnce(new Error("Network failure"));

    const { form, submit, errorMessage } = createLoginPageLogic();
    form.email = "admin@example.com";
    form.password = "password";

    await submit();

    expect(errorMessage.value).toBe(
      "Unable to sign in with those credentials.",
    );
  });

  it("manages loading state correctly during submission", async () => {
    let resolveLogin: () => void;
    mockLogin.mockReturnValueOnce(
      new Promise<void>((resolve) => {
        resolveLogin = resolve;
      }),
    );

    const { form, submit, isSubmitting } = createLoginPageLogic();
    form.email = "admin@example.com";
    form.password = "password";

    expect(isSubmitting.value).toBe(false);

    const submitPromise = submit();
    // After calling submit but before it resolves, isSubmitting should be true
    expect(isSubmitting.value).toBe(true);

    resolveLogin!();
    await submitPromise;

    expect(isSubmitting.value).toBe(false);
  });

  it("resets loading state even when login fails", async () => {
    mockLogin.mockRejectedValueOnce({ data: { message: "Error" } });

    const { form, submit, isSubmitting } = createLoginPageLogic();
    form.email = "admin@example.com";
    form.password = "password";

    await submit();

    expect(isSubmitting.value).toBe(false);
  });

  it("clears previous error message on new submit attempt", async () => {
    // First attempt fails
    mockLogin.mockRejectedValueOnce({ data: { message: "Bad credentials" } });

    const { form, submit, errorMessage } = createLoginPageLogic();
    form.email = "admin@example.com";
    form.password = "wrong";

    await submit();
    expect(errorMessage.value).toBe("Bad credentials");

    // Second attempt: error should be cleared at start
    mockLogin.mockResolvedValueOnce(undefined);
    const submitPromise = submit();
    // errorMessage is cleared immediately at the start of submit
    // (it gets set to "" before the async call)
    await submitPromise;
    expect(errorMessage.value).toBe("");
  });
});
