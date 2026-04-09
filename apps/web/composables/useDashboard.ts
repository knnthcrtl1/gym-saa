import type { DashboardResponse } from "../types/api";

export const useDashboard = async () => {
  const { api } = useApi();

  return await useAsyncData<DashboardResponse>("dashboard", () =>
    api("/dashboard"),
  );
};
