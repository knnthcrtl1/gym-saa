import type { PaginatedResponse, Subscription } from "../types/api";

export type SubscriptionPayload = {
  tenant_id: number;
  branch_id: number;
  member_id: number;
  membership_plan_id: number;
  start_date: string;
  amount: number;
  sessions_remaining?: number | null;
  payment_status: "unpaid" | "partial" | "paid";
  status: "pending" | "active" | "expired" | "frozen" | "cancelled";
};

export type SubscriptionListParams = {
  status?: SubscriptionPayload["status"];
  page?: number;
  per_page?: number;
};

type SubscriptionResponse = {
  data: Subscription;
};

type SubscriptionMutationResponse = {
  message: string;
  data: Subscription;
};

type DeleteSubscriptionResponse = {
  message: string;
};

type MutationOptions = {
  idempotencyKey?: string;
};

export const useSubscriptions = () => {
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

  const list = (params?: SubscriptionListParams) => {
    return api<PaginatedResponse<Subscription>>("/subscriptions", {
      query: params,
    });
  };

  const get = (id: number) => {
    return api<SubscriptionResponse>(`/subscriptions/${id}`);
  };

  const create = (payload: SubscriptionPayload, options?: MutationOptions) => {
    return api<SubscriptionMutationResponse>("/subscriptions", {
      method: "POST",
      body: payload,
      ...mutationHeaders(options),
    });
  };

  const update = (id: number, payload: Partial<SubscriptionPayload>) => {
    return api<SubscriptionMutationResponse>(`/subscriptions/${id}`, {
      method: "PUT",
      body: payload,
    });
  };

  const remove = (id: number) => {
    return api<DeleteSubscriptionResponse>(`/subscriptions/${id}`, {
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
