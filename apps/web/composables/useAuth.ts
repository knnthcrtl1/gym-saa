import type { AuthUser, LoginPayload, MeResponse } from "../types/auth";

export const useAuth = () => {
  const config = useRuntimeConfig();
  const user = useState<AuthUser | null>("auth.user", () => null);
  const initialized = useState<boolean>("auth.initialized", () => false);

  const api = $fetch.create({
    baseURL: config.public.apiBase,
    credentials: "include",
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  });

  const csrf = async () => {
    await api("/sanctum/csrf-cookie");
  };

  const fetchUser = async () => {
    try {
      const response = await api<MeResponse>("/api/v1/me");
      user.value = response.user;
      return response.user;
    } catch {
      user.value = null;
      return null;
    } finally {
      initialized.value = true;
    }
  };

  const login = async (payload: LoginPayload) => {
    await csrf();

    await api("/api/v1/login", {
      method: "POST",
      body: payload,
    });

    await fetchUser();
  };

  const logout = async () => {
    try {
      await api("/api/v1/logout", {
        method: "POST",
      });
    } finally {
      user.value = null;
      initialized.value = true;
      await navigateTo("/login");
    }
  };

  return {
    user,
    initialized,
    csrf,
    fetchUser,
    login,
    logout,
  };
};
