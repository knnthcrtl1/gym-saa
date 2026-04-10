const publicRoutes = new Set(["/", "/login"]);

export default defineNuxtRouteMiddleware(async (to) => {
  const config = useRuntimeConfig();
  const previewRoutes = config.public.dashboardPreviewMode
    ? new Set(["/dashboard"])
    : new Set<string>();

  if (publicRoutes.has(to.path) || previewRoutes.has(to.path)) {
    return;
  }

  const { user, initialized, fetchUser } = useAuth();

  if (!initialized.value || !user.value) {
    try {
      await fetchUser();
    } catch {
      return navigateTo("/login");
    }
  }

  if (!user.value) {
    return navigateTo("/login");
  }
});
