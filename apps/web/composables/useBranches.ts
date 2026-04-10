import type { Branch, PaginatedResponse } from "../types/api";

export const useBranches = async () => {
  const { api } = useApi();

  return await useAsyncData<PaginatedResponse<Branch>>("branches", () =>
    api("/branches"),
  );
};
