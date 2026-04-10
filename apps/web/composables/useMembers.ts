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

type MemberListParams = {
  search?: string;
  status?: MemberPayload["status"];
  page?: number;
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
};

export const useMembers = () => {
  const { api } = useApi();

  const list = (params?: MemberListParams) => {
    return api<PaginatedResponse<Member>>("/members", {
      query: params,
    });
  };

  const get = (id: number) => {
    return api<MemberResponse>(`/members/${id}`);
  };

  const create = (payload: MemberPayload) => {
    return api<MemberMutationResponse>("/members", {
      method: "POST",
      body: payload,
    });
  };

  const update = (id: number, payload: Partial<MemberPayload>) => {
    return api<MemberMutationResponse>(`/members/${id}`, {
      method: "PUT",
      body: payload,
    });
  };

  const remove = (id: number) => {
    return api<DeleteMemberResponse>(`/members/${id}`, {
      method: "DELETE",
    });
  };

  return {
    list,
    get,
    create,
    update,
    remove,
  };
};
