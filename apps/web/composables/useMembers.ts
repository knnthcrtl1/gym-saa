import type { Member, PaginatedResponse } from "../types/api";

export type MemberPayload = {
  tenant_id: number;
  branch_id: number;
  member_code: string;
  first_name: string;
  last_name: string;
  email?: string | null;
  phone?: string | null;
  birthdate?: string | null;
  sex?: string | null;
  address?: string | null;
  emergency_contact_name?: string | null;
  emergency_contact_phone?: string | null;
  qr_code_value?: string | null;
  status: "active" | "inactive" | "blocked";
  joined_at?: string | null;
};

export type MemberSortField =
  | "created_at"
  | "joined_at"
  | "member_code"
  | "name"
  | "status";

export type MemberListParams = {
  search?: string;
  status?: MemberPayload["status"];
  page?: number;
  per_page?: number;
  sort_by?: MemberSortField;
  direction?: "asc" | "desc";
};

type MemberResponse = {
  data: Member;
};

type MemberMutationResponse = {
  message: string;
  data: Member;
};

type DeleteMemberResponse = {
  message: string;
  deleted?: number;
};

export const useMembers = () => {
  const { api, cachedGet, invalidateCache } = useApi();

  const list = (params?: MemberListParams) => {
    return cachedGet<PaginatedResponse<Member>>("/members", {
      query: params,
    });
  };

  const get = (id: number) => {
    return cachedGet<MemberResponse>(`/members/${id}`);
  };

  const create = async (payload: MemberPayload) => {
    const result = await api<MemberMutationResponse>("/members", {
      method: "POST",
      body: payload,
    });
    invalidateCache("/members");
    return result;
  };

  const update = async (id: number, payload: Partial<MemberPayload>) => {
    const result = await api<MemberMutationResponse>(`/members/${id}`, {
      method: "PUT",
      body: payload,
    });
    invalidateCache("/members");
    return result;
  };

  const remove = async (id: number) => {
    const result = await api<DeleteMemberResponse>(`/members/${id}`, {
      method: "DELETE",
    });
    invalidateCache("/members");
    return result;
  };

  const bulkRemove = async (ids: number[]) => {
    const result = await api<DeleteMemberResponse>("/members/bulk-delete", {
      method: "DELETE",
      body: { ids },
    });
    invalidateCache("/members");
    return result;
  };

  return {
    list,
    get,
    create,
    update,
    remove,
    bulkRemove,
  };
};
