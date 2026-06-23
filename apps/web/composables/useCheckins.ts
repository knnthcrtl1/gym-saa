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

type MutationOptions = {
  idempotencyKey?: string;
};

export const useCheckins = () => {
  const { api } = useApi();

  const mutationHeaders = (options?: MutationOptions) => {
    if (!options?.idempotencyKey) {
      return {};
    }

    return {
      headers: {
        "X-Idempotency-Key": options.idempotencyKey,
      },
    };
  };

  const list = (params?: CheckinListParams) =>
    api<PaginatedResponse<Checkin>>("/checkins", { query: params });

  const create = (payload: CheckinPayload, options?: MutationOptions) =>
    api<CheckinMutationResponse>("/checkins", {
      method: "POST",
      body: payload,
      ...mutationHeaders(options),
    });

  return {
    list,
    create,
  };
};
