import type { AuthUser, LoginResponse } from "../types/api";

type LoginPayload = {
  email: string;
  password: string;
};

const publicStatus = (error: unknown) => {
  const typedError = error as {
    status?: number;
    statusCode?: number;
    response?: { status?: number };
    data?: { message?: string };
  };

  return (
    typedError?.statusCode ??
    typedError?.status ??
    typedError?.response?.status ??
    500
  );
};

const resolveSanctumBase = (apiBase: string) => {
  try {
    return new URL(apiBase).origin;
  } catch {
    return apiBase.replace(/\/api\/v\d+.*$/, "") || "/";
  }
};

export const useAuth = () => {
  const { api } = useApi();
  const config = useRuntimeConfig();

  const user = useState<AuthUser | null>("auth-user", () => null);
  const initialized = useState<boolean>("auth-initialized", () => false);
  const pending = useState<boolean>("auth-pending", () => false);

  const clear = () => {
    user.value = null;
  };

  const fetchUser = async (force = false) => {
    if (pending.value) {
      return user.value;
    }

    if (initialized.value && !force) {
      return user.value;
    }

    pending.value = true;

    try {
      user.value = await api<AuthUser>("/me");
    } catch (error) {
      if ([401, 419].includes(publicStatus(error))) {
        user.value = null;
      } else {
        throw error;
      }
    } finally {
      initialized.value = true;
      pending.value = false;
    }

    return user.value;
  };

  const ensureCsrfCookie = async () => {
    const sanctumBase = resolveSanctumBase(config.public.apiBase);

    await $fetch("/sanctum/csrf-cookie", {
      baseURL: sanctumBase,
      credentials: "include",
      headers: import.meta.server ? useRequestHeaders(["cookie"]) : undefined,
    });
  };

  const login = async (payload: LoginPayload) => {
    await ensureCsrfCookie();

    const response = await api<LoginResponse>("/login", {
      method: "POST",
      body: payload,
    });

    user.value = response.user;
    initialized.value = true;

    return response.user;
  };

  const logout = async () => {
    try {
      await api("/logout", { method: "POST" });
    } finally {
      clear();
      initialized.value = true;
    }
  };

  return {
    user,
    initialized,
    pending,
    fetchUser,
    login,
    logout,
    clear,
  };
};
