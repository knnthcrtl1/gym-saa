const responseCache = new Map<string, { data: unknown; expires: number }>();

export const useApi = () => {
  const config = useRuntimeConfig();
  const token = useCookie("auth_token");

  const api = $fetch.create({
    baseURL: `${config.public.apiBase}/api/v1`,
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
    onResponseError({ response }) {
      if (response.status === 401 && import.meta.client) {
        token.value = null;
        useState("auth.user").value = null;
        useState("auth.initialized").value = false;
        navigateTo("/login");
      }
    },
  });

  const cachedGet = async <T>(
    url: string,
    options?: Record<string, unknown>,
    ttl = 5000,
  ): Promise<T> => {
    const cacheKey = `${url}:${JSON.stringify(options ?? {})}`;
    const cached = responseCache.get(cacheKey);

    if (cached && cached.expires > Date.now()) {
      return cached.data as T;
    }

    const data = await api<T>(url, options);
    responseCache.set(cacheKey, { data, expires: Date.now() + ttl });
    return data;
  };

  const invalidateCache = (prefix?: string) => {
    if (!prefix) {
      responseCache.clear();
      return;
    }
    for (const key of responseCache.keys()) {
      if (key.startsWith(prefix)) {
        responseCache.delete(key);
      }
    }
  };

  return { api, cachedGet, invalidateCache };
};
