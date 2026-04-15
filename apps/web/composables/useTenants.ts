import type { PaginatedResponse, Tenant } from "../types/api";

export type TenantPayload = {
  name: string;
  slug: string;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  status: "active" | "inactive";
};

export type TenantListParams = {
  page?: number;
  per_page?: number;
};

type TenantResponse = {
  data: Tenant;
};

type TenantMutationResponse = Tenant;

type DeleteTenantResponse = {
  message: string;
};

export const useTenants = () => {
  const { api } = useApi();

  const list = (params?: TenantListParams) =>
    api<PaginatedResponse<Tenant>>("/tenants", {
      query: params,
    });

  const get = (id: number) => api<TenantResponse>(`/tenants/${id}`);

  const create = (payload: TenantPayload) =>
    api<TenantMutationResponse>("/tenants", {
      method: "POST",
      body: payload,
    });

  const update = (id: number, payload: Partial<TenantPayload>) =>
    api<TenantMutationResponse>(`/tenants/${id}`, {
      method: "PUT",
      body: payload,
    });

  const remove = (id: number) =>
    api<DeleteTenantResponse>(`/tenants/${id}`, {
      method: "DELETE",
    });

  return {
    list,
    get,
    create,
    update,
    remove,
  };
};
