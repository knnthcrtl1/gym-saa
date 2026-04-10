export const useApi = () => {
  const config = useRuntimeConfig();
  const forwardedHeaders = import.meta.server
    ? useRequestHeaders(["cookie", "x-xsrf-token"])
    : undefined;

  const api = $fetch.create({
    baseURL: config.public.apiBase,
    credentials: "include",
    headers: {
      Accept: "application/json",
      ...forwardedHeaders,
    },
  });

  return { api };
};
