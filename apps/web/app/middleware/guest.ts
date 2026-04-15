function isSafeRedirectTarget(value: unknown): value is string {
  return (
    typeof value === "string" &&
    value.startsWith("/") &&
    !value.startsWith("//")
  );
}

export default defineNuxtRouteMiddleware(async (to) => {
  const { user, initialized, fetchUser } = useAuth();

  if (!initialized.value || (import.meta.client && !user.value)) {
    await fetchUser();
  }

  if (user.value) {
    const redirectTarget = isSafeRedirectTarget(to.query.redirect)
      ? to.query.redirect
      : "/dashboard";

    return navigateTo(redirectTarget);
  }
});
