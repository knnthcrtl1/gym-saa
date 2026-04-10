const publicRoutes = new Set(["/", "/login"]);

export default defineNuxtRouteMiddleware(async (to) => {
  const { user, initialized, fetchUser } = useAuth();

  if (!initialized.value) {
    await fetchUser();
  }

  if (user.value && to.path === "/login") {
    return navigateTo("/dashboard");
  }

  if (!user.value && !publicRoutes.has(to.path)) {
    return navigateTo("/login");
  }
});
