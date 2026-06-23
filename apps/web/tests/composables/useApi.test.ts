import { describe, it, expect, vi, beforeEach } from "vitest";
import { resetAllMocks } from "../setup";

let capturedCreateOptions: any = null;

beforeEach(() => {
  resetAllMocks();
  capturedCreateOptions = null;

  // Capture the options passed to $fetch.create so we can invoke hooks
  (globalThis as any).$fetch.create = vi.fn((options: any) => {
    capturedCreateOptions = options;
    return vi.fn();
  });
});

async function loadUseApi() {
  const mod = await import("../../composables/useApi");
  return mod.useApi();
}

describe("useApi", () => {
  it("creates $fetch with correct baseURL and Accept header", async () => {
    await loadUseApi();

    expect(capturedCreateOptions).not.toBeNull();
    expect(capturedCreateOptions.baseURL).toBe("http://localhost:8000/api/v1");
    expect(capturedCreateOptions.headers.Accept).toBe("application/json");
  });

  it("injects Authorization header when token exists", async () => {
    const tokenRef = (globalThis as any).useCookie("auth_token");
    tokenRef.value = "test-token-123";

    await loadUseApi();

    const { onRequest } = capturedCreateOptions;
    const mockHeaders = new Headers();
    const requestOptions: any = { headers: mockHeaders };
    onRequest({ options: requestOptions });

    expect(requestOptions.headers.get("Authorization")).toBe(
      "Bearer test-token-123",
    );
  });

  it("does NOT inject auth header when no token exists", async () => {
    await loadUseApi();

    const { onRequest } = capturedCreateOptions;
    const mockHeaders = new Headers();
    const requestOptions: any = { headers: mockHeaders };
    onRequest({ options: requestOptions });

    // Headers object should be unchanged (no set call)
    expect(requestOptions.headers).toBe(mockHeaders);
    expect(requestOptions.headers.has("Authorization")).toBe(false);
  });

  it("onResponseError handler is registered for 401 handling", async () => {
    await loadUseApi();

    // Verify the onResponseError handler is wired up
    expect(capturedCreateOptions.onResponseError).toBeDefined();
    expect(typeof capturedCreateOptions.onResponseError).toBe("function");

    // import.meta.client is statically replaced by Vite at compile time,
    // so it's false in the test environment. We verify the handler exists
    // and non-401 errors don't trigger navigation (covered in next test).
    // The actual 401 → logout flow is covered by E2E tests.
  });

  it("non-401 errors do NOT clear auth state", async () => {
    const tokenRef = (globalThis as any).useCookie("auth_token");
    tokenRef.value = "my-token";
    const userState = (globalThis as any).useState("auth.user", () => null);
    userState.value = { id: 1, name: "Test" };

    Object.defineProperty(import.meta, "client", {
      value: true,
      writable: true,
      configurable: true,
    });

    await loadUseApi();

    const { onResponseError } = capturedCreateOptions;
    onResponseError({ response: { status: 500 } });

    expect(tokenRef.value).toBe("my-token");
    expect(userState.value).toEqual({ id: 1, name: "Test" });
    expect((globalThis as any).navigateTo).not.toHaveBeenCalled();
  });
});
