import type { Branch, PaginatedResponse } from "../types/api";

export type BranchPayload = {
  tenant_id: number;
  name: string;
  code?: string | null;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  status: "active" | "inactive";
};

export type BranchListParams = {
  page?: number;
  per_page?: number;
};

type BranchResponse = {
  data: Branch;
};

type BranchMutationResponse = Branch;

type DeleteBranchResponse = {
  message: string;
};

export const useBranches = () => {
  const { api } = useApi();

  const list = (params?: BranchListParams) =>
    api<PaginatedResponse<Branch>>("/branches", {
      query: params,
    });

  const get = (id: number) => api<BranchResponse>(`/branches/${id}`);

  const create = (payload: BranchPayload) =>
    api<BranchMutationResponse>("/branches", {
      method: "POST",
      body: payload,
    });

  const update = (id: number, payload: Partial<BranchPayload>) =>
    api<BranchMutationResponse>(`/branches/${id}`, {
      method: "PUT",
      body: payload,
    });

  const remove = (id: number) =>
    api<DeleteBranchResponse>(`/branches/${id}`, {
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
