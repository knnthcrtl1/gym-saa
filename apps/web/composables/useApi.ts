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
  });

  return { api };
};
