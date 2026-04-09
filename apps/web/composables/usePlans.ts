import type { MembershipPlan, PaginatedResponse } from "../types/api";

export const usePlans = async () => {
  const { api } = useApi();

  return await useAsyncData<PaginatedResponse<MembershipPlan>>("plans", () =>
    api("/membership-plans"),
  );
};
