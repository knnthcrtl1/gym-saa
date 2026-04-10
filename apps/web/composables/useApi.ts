export const useApi = () => {
  const config = useRuntimeConfig();
  const forwardedHeaders = import.meta.server
    ? useRequestHeaders(["cookie", "x-xsrf-token"])
    : undefined;

  const api = $fetch.create({
    baseURL: `${config.public.apiBase}/api/v1`,
    credentials: "include",
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
      ...forwardedHeaders,
    },
  });

  return { api };
};
