export const useApi = () => {
  const config = useRuntimeConfig();

  const api = $fetch.create({
    baseURL: config.public.apiBase,
    credentials: "include",
    headers: {
      Accept: "application/json",
    },
  });

  return { api };
};
