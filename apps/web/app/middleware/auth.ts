export default defineNuxtRouteMiddleware(async (to) => {
  const { user, initialized, fetchUser } = useAuth();

  if (!initialized.value || (import.meta.client && !user.value)) {
    await fetchUser();
  }

  if (!user.value) {
    return navigateTo({
      path: "/login",
      query: { redirect: to.fullPath },
    });
  }
});
