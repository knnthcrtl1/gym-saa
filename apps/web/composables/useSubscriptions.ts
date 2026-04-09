import type { PaginatedResponse, Subscription } from "../types/api";

export const useSubscriptions = async () => {
  const { api } = useApi();

  return await useAsyncData<PaginatedResponse<Subscription>>(
    "subscriptions",
    () => api("/subscriptions"),
  );
};
