import type { Member, PaginatedResponse } from "../types/api";

export const useMembers = async () => {
  const { api } = useApi();

  return await useAsyncData<PaginatedResponse<Member>>("members", () =>
    api("/members"),
  );
};
