import type { AuditLog, PaginatedResponse } from "../types/api";

export type AuditLogListParams = {
  action?: string;
  auditable_type?: string;
  actor_id?: number;
  date_from?: string;
  date_to?: string;
  page?: number;
  per_page?: number;
};

export const useAuditLogs = () => {
  const { api } = useApi();

  const list = (params?: AuditLogListParams) => {
    return api<PaginatedResponse<AuditLog>>("/audit-logs", {
      query: params,
    });
  };

  return {
    list,
  };
};
