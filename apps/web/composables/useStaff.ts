import type { PaginatedResponse, StaffUser } from "../types/api";

export type StaffPayload = {
  tenant_id: number;
  branch_id?: number | null;
  name: string;
  email: string;
  password?: string | null;
  role: "gym_admin" | "staff";
  staff_role?: "owner" | "manager" | "front_desk" | null;
  status: "active" | "inactive";
  permissions?: string[] | null;
};

export type StaffListParams = {
  search?: string;
  status?: StaffPayload["status"];
  role?: StaffPayload["role"];
  staff_role?: NonNullable<StaffPayload["staff_role"]>;
  page?: number;
  per_page?: number;
};

type StaffResponse = {
  data: StaffUser;
};

type StaffMutationResponse = {
  message: string;
  data: StaffUser;
};

type DeleteStaffResponse = {
  message: string;
};

export const useStaff = () => {
  const { api } = useApi();

  const list = (params?: StaffListParams) =>
    api<PaginatedResponse<StaffUser>>("/staff", { query: params });

  const get = (id: number) => api<StaffResponse>(`/staff/${id}`);

  const create = (payload: StaffPayload) =>
    api<StaffMutationResponse>("/staff", {
      method: "POST",
      body: payload,
    });

  const update = (id: number, payload: Partial<StaffPayload>) =>
    api<StaffMutationResponse>(`/staff/${id}`, {
      method: "PUT",
      body: payload,
    });

  const remove = (id: number) =>
    api<DeleteStaffResponse>(`/staff/${id}`, {
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
