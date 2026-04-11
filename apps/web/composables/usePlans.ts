import type { MembershipPlan, PaginatedResponse } from "../types/api";

export type PlanPayload = {
  tenant_id: number;
  branch_id?: number | null;
  name: string;
  description?: string | null;
  duration_type: "day" | "week" | "month" | "year" | "session";
  duration_value: number;
  price: number;
  session_limit?: number | null;
  freeze_limit_days?: number | null;
  status: "active" | "inactive";
};

export type PlanListParams = {
  status?: PlanPayload["status"];
  page?: number;
  per_page?: number;
};

type PlanResponse = {
  data: MembershipPlan;
};

type PlanMutationResponse = {
  message: string;
  data: MembershipPlan;
};

type DeletePlanResponse = {
  message: string;
};

export const usePlans = () => {
  const { api } = useApi();

  const list = (params?: PlanListParams) => {
    return api<PaginatedResponse<MembershipPlan>>("/membership-plans", {
      query: params,
    });
  };

  const get = (id: number) => {
    return api<PlanResponse>(`/membership-plans/${id}`);
  };

  const create = (payload: PlanPayload) => {
    return api<PlanMutationResponse>("/membership-plans", {
      method: "POST",
      body: payload,
    });
  };

  const update = (id: number, payload: Partial<PlanPayload>) => {
    return api<PlanMutationResponse>(`/membership-plans/${id}`, {
      method: "PUT",
      body: payload,
    });
  };

  const remove = (id: number) => {
    return api<DeletePlanResponse>(`/membership-plans/${id}`, {
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
