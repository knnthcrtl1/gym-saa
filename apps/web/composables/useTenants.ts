import type { PaginatedResponse, Tenant } from "../types/api";

export const useTenants = async () => {
  const { api } = useApi();

  return await useAsyncData<PaginatedResponse<Tenant>>("tenants", () =>
    api("/tenants"),
  );
};
