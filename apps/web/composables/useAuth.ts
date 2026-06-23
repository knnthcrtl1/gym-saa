import type { AuthUser, LoginPayload, MeResponse } from "../types/auth";

export const useAuth = () => {
  const config = useRuntimeConfig();
  const user = useState<AuthUser | null>("auth.user", () => null);
  const initialized = useState<boolean>("auth.initialized", () => false);
  const token = useCookie("auth_token", { maxAge: 60 * 60 * 24 * 30 });

  const api = $fetch.create({
    baseURL: config.public.apiBase,
    headers: {
      Accept: "application/json",
    },
    onRequest({ options }) {
      if (token.value) {
        const headers = new Headers(options.headers);
        headers.set("Authorization", `Bearer ${token.value}`);
        options.headers = headers;
      }
    },
  });

  const fetchUser = async () => {
    try {
      const response = await api<MeResponse>("/api/v1/me");
      user.value = response.user;
      return response.user;
    } catch {
      user.value = null;
      token.value = null;
      return null;
    } finally {
      initialized.value = true;
    }
  };

  const login = async (payload: LoginPayload) => {
    const response = await api<{ user: AuthUser; token: string }>(
      "/api/v1/login",
      {
        method: "POST",
        body: payload,
      },
    );

    token.value = response.token;
    user.value = response.user;
    initialized.value = true;
  };

  const logout = async () => {
    try {
      await api("/api/v1/logout", {
        method: "POST",
      });
    } finally {
      user.value = null;
      token.value = null;
      initialized.value = true;
      await navigateTo("/login");
    }
  };

  return {
    user,
    initialized,
    fetchUser,
    login,
    logout,
  };
};
