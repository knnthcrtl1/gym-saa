import { describe, it, expect, vi, beforeEach } from "vitest";
import { resetAllMocks } from "../setup";
import type { AuthUser } from "../../types/auth";

// We need to control the $fetch.create mock per test, so we set it up before
// importing the composable (which is re-evaluated each call anyway).
const mockApi = vi.fn();

beforeEach(() => {
  resetAllMocks();

  // Make $fetch.create return our controllable mock
  (globalThis as any).$fetch.create = vi.fn(() => mockApi);
  mockApi.mockReset();
});

// Helper: lazy-import so global mocks are in place
async function loadUseAuth() {
  // Re-import to pick up fresh global mocks
  const mod = await import("../../composables/useAuth");
  return mod.useAuth();
}

const fakeUser: AuthUser = {
  id: 1,
  tenant_id: 1,
  branch_id: 1,
  name: "John Doe",
  email: "john@example.com",
  role: "gym_admin",
  staff_role: null,
  status: "active",
  permissions: ["dashboard.view", "members.view"],
};

describe("useAuth", () => {
  it("login() stores token and sets user state", async () => {
    mockApi.mockResolvedValueOnce({ token: "abc123", user: fakeUser });

    const { login, user } = await loadUseAuth();
    await login({ email: "john@example.com", password: "secret" });

    // Token should be set in the cookie ref
    const tokenCookie = (globalThis as any).useCookie("auth_token");
    expect(tokenCookie.value).toBe("abc123");

    // User state should be populated
    expect(user.value).toEqual(fakeUser);
  });

  it("login() calls the API with correct endpoint and payload", async () => {
    mockApi.mockResolvedValueOnce({ token: "abc123", user: fakeUser });

    const { login } = await loadUseAuth();
    await login({ email: "john@example.com", password: "secret" });

    expect(mockApi).toHaveBeenCalledWith("/api/v1/login", {
      method: "POST",
      body: { email: "john@example.com", password: "secret" },
    });
  });

  it("logout() clears token, nulls user, and navigates to /login", async () => {
    // Seed state as if user is logged in
    mockApi.mockResolvedValueOnce({ token: "abc123", user: fakeUser });

    const { login, logout, user } = await loadUseAuth();
    await login({ email: "john@example.com", password: "secret" });

    // Now logout (the POST may succeed or fail, either way state is cleared)
    mockApi.mockResolvedValueOnce(undefined);
    await logout();

    const tokenCookie = (globalThis as any).useCookie("auth_token");
    expect(tokenCookie.value).toBeNull();
    expect(user.value).toBeNull();
    expect((globalThis as any).navigateTo).toHaveBeenCalledWith("/login");
  });

  it("logout() clears state even when the API call fails", async () => {
    mockApi.mockResolvedValueOnce({ token: "abc123", user: fakeUser });

    const { login, logout, user } = await loadUseAuth();
    await login({ email: "john@example.com", password: "secret" });

    mockApi.mockRejectedValueOnce(new Error("Network error"));

    // logout has try/finally without catch, so the error propagates
    // but the finally block still clears state
    try {
      await logout();
    } catch {
      // expected
    }

    const tokenCookie = (globalThis as any).useCookie("auth_token");
    expect(tokenCookie.value).toBeNull();
    expect(user.value).toBeNull();
    expect((globalThis as any).navigateTo).toHaveBeenCalledWith("/login");
  });

  it("fetchUser() populates user on success", async () => {
    mockApi.mockResolvedValueOnce({ user: fakeUser });

    const { fetchUser, user, initialized } = await loadUseAuth();
    const result = await fetchUser();

    expect(result).toEqual(fakeUser);
    expect(user.value).toEqual(fakeUser);
    expect(initialized.value).toBe(true);
  });

  it("fetchUser() handles 401/error gracefully (clears token)", async () => {
    mockApi.mockRejectedValueOnce({ status: 401, message: "Unauthenticated" });

    const { fetchUser, user, initialized } = await loadUseAuth();
    const result = await fetchUser();

    expect(result).toBeNull();
    expect(user.value).toBeNull();
    const tokenCookie = (globalThis as any).useCookie("auth_token");
    expect(tokenCookie.value).toBeNull();
    expect(initialized.value).toBe(true);
  });

  it("$fetch.create is called with auth header injection via onRequest", async () => {
    await loadUseAuth();

    const createCall = (globalThis as any).$fetch.create;
    expect(createCall).toHaveBeenCalledTimes(1);

    const options = createCall.mock.calls[0][0];
    expect(options.baseURL).toBe("http://localhost:8000");
    expect(options.headers.Accept).toBe("application/json");
    expect(typeof options.onRequest).toBe("function");
  });

  it("onRequest injects Bearer token when token exists", async () => {
    // Set a token in the cookie store before loading
    const tokenRef = (globalThis as any).useCookie("auth_token");
    tokenRef.value = "my-token";

    await loadUseAuth();

    const createCall = (globalThis as any).$fetch.create;
    const { onRequest } = createCall.mock.calls[0][0];

    // Simulate the onRequest hook
    const mockHeaders = new Headers();
    const requestOptions: any = { headers: mockHeaders };
    onRequest({ options: requestOptions });

    expect(requestOptions.headers.get("Authorization")).toBe(
      "Bearer my-token",
    );
  });

  it("onRequest does NOT inject auth header when no token", async () => {
    await loadUseAuth();

    const createCall = (globalThis as any).$fetch.create;
    const { onRequest } = createCall.mock.calls[0][0];

    const mockHeaders = new Headers();
    const requestOptions: any = { headers: mockHeaders };
    onRequest({ options: requestOptions });

    // Headers should not be replaced (token is falsy so the if-block is skipped)
    expect(requestOptions.headers).toBe(mockHeaders);
  });
});
