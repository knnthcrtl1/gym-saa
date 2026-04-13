import type { PaginatedResponse, Payment } from "../types/api";

export type PaymentListParams = {
  status?: Payment["status"];
  member_id?: number;
  subscription_id?: number;
  page?: number;
  per_page?: number;
};

export type PaymentIntentPayload = {
  tenant_id: number;
  branch_id: number;
  member_id: number;
  subscription_id?: number | null;
  amount: number;
  currency?: string;
  notes?: string | null;
};

export type ManualPaymentPayload = {
  tenant_id: number;
  branch_id: number;
  member_id: number;
  subscription_id?: number | null;
  payment_date: string;
  amount: number;
  payment_method: Payment["payment_method"];
  reference_no?: string | null;
  notes?: string | null;
  status?: Payment["status"];
  proof?: File | null;
};

export type PaymentProofUploadPayload = {
  proof: File;
};

export type PaymentReviewPayload = {
  notes?: string | null;
};

type PaymentResponse = {
  data: Payment;
};

type PaymentIntentResponse = {
  message: string;
  data: {
    payment: Payment;
    checkout_url: string;
  };
};

type PaymentMutationResponse = {
  message: string;
  data: Payment;
};

export const usePayments = () => {
  const { api } = useApi();

  const toFormData = (
    payload: ManualPaymentPayload | PaymentProofUploadPayload,
  ) => {
    const formData = new FormData();

    for (const [key, value] of Object.entries(payload)) {
      if (value === null || value === undefined || value === "") {
        continue;
      }

      if (value instanceof File) {
        formData.append(key, value);
        continue;
      }

      formData.append(key, String(value));
    }

    return formData;
  };

  const list = (params?: PaymentListParams) => {
    return api<PaginatedResponse<Payment>>("/payments", {
      query: params,
    });
  };

  const get = (id: number) => {
    return api<PaymentResponse>(`/payments/${id}`);
  };

  const createIntent = (payload: PaymentIntentPayload) => {
    return api<PaymentIntentResponse>("/payments/intent", {
      method: "POST",
      body: payload,
    });
  };

  const recordManual = (payload: ManualPaymentPayload) => {
    return api<PaymentMutationResponse>("/payments/manual", {
      method: "POST",
      body: toFormData(payload),
    });
  };

  const uploadProof = (id: number, payload: PaymentProofUploadPayload) => {
    return api<PaymentMutationResponse>(`/payments/${id}/proof`, {
      method: "POST",
      body: toFormData(payload),
    });
  };

  const verify = (id: number, payload?: PaymentReviewPayload) => {
    return api<PaymentMutationResponse>(`/payments/${id}/verify`, {
      method: "PUT",
      body: payload,
    });
  };

  const reject = (id: number, payload?: PaymentReviewPayload) => {
    return api<PaymentMutationResponse>(`/payments/${id}/reject`, {
      method: "PUT",
      body: payload,
    });
  };

  return {
    list,
    get,
    createIntent,
    recordManual,
    uploadProof,
    verify,
    reject,
  };
};
