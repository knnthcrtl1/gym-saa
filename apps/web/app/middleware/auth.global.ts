const publicRoutes = new Set(["/", "/login"]);

export default defineNuxtRouteMiddleware(async (to) => {
  if (publicRoutes.has(to.path)) {
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
