import type { Checkin, PaginatedResponse } from "../types/api";

export type CheckinPayload = {
  tenant_id: number;
  branch_id: number;
  member_id: number;
  subscription_id?: number | null;
  source?: "manual" | "qr" | "kiosk";
};

export type CheckinListParams = {
  search?: string;
  member_id?: number;
  date?: string;
  page?: number;
  per_page?: number;
};

type CheckinMutationResponse = {
  message: string;
  data: Checkin;
};

export const useCheckins = () => {
  const { api } = useApi();

  const list = (params?: CheckinListParams) =>
    api<PaginatedResponse<Checkin>>("/checkins", { query: params });

  const create = (payload: CheckinPayload) =>
    api<CheckinMutationResponse>("/checkins", {
      method: "POST",
      body: payload,
    });

  return {
    list,
    create,
  };
};
