import type { DashboardPreviewState, DashboardResponse } from "../types/api";

const previewDashboardStats: DashboardResponse = {
  stats: {
    active_members: 184,
    expired_subscriptions: 23,
    today_checkins: 61,
    monthly_revenue: 128500,
  },
};

export const useDashboard = async () => {
  const { api } = useApi();
  const config = useRuntimeConfig();
  const preview = useState<DashboardPreviewState>(
    "dashboard-preview-state",
    () => ({
      isPreview: false,
    }),
  );

  const asyncData = await useAsyncData<DashboardResponse>(
    "dashboard",
    async () => {
      try {
        preview.value.isPreview = false;

        return await api("/dashboard");
      } catch (error) {
        if (config.public.dashboardPreviewMode) {
          preview.value.isPreview = true;
          return previewDashboardStats;
        }

        throw error;
      }
    },
  );

  return {
    ...asyncData,
    preview,
  };
};
