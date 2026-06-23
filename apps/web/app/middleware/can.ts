import { useAuthorization } from "../../composables/useAuthorization";

export default defineNuxtRouteMiddleware((to) => {
  const permission =
    typeof to.meta.permission === "string" ? to.meta.permission : undefined;

  if (!permission) {
    return;
  }

  const { hasPermission } = useAuthorization();

  if (!hasPermission(permission)) {
    return navigateTo("/dashboard");
  }
});
