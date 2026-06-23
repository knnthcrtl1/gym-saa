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

  return { api };
};
