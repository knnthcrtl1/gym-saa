<template>
  <v-app class="gym-app">
    <v-layout>
      <v-navigation-drawer
        v-if="showAppShell"
        v-model="drawer"
        :permanent="mdAndUp"
        :temporary="!mdAndUp"
        class="app-drawer"
        width="288"
      >
        <div class="app-drawer__inner">
          <NuxtLink class="app-drawer__brand" to="/dashboard">
            <AppLogo />
          </NuxtLink>

          <div class="app-drawer__section">Operations</div>

          <v-list class="app-nav" density="comfortable" nav>
            <v-list-item
              v-for="item in navigationItems"
              :key="item.to"
              :active="isActive(item.to)"
              :subtitle="item.description"
              :title="item.label"
              :to="item.to"
              rounded="xl"
              color="primary"
            />
          </v-list>

          <v-spacer />

          <div class="app-user-card">
            <div class="page-header__eyebrow">Signed in</div>
            <div class="text-subtitle-1 font-weight-bold mt-2">
              {{ user?.name || "Gym Operator" }}
            </div>
            <div class="muted-text text-body-2 mt-1">
              {{ user?.email || "Waiting for session" }}
            </div>
            <div class="muted-text text-caption text-uppercase mt-3">
              {{ formatRole(user?.role) }}
            </div>
            <v-btn
              class="mt-4"
              color="primary"
              variant="tonal"
              block
              @click="handleLogout"
            >
              Sign out
            </v-btn>
          </div>
        </div>
      </v-navigation-drawer>

      <v-app-bar v-if="showAppShell" class="app-bar" flat>
        <v-btn
          v-if="!mdAndUp"
          class="mr-3"
          color="primary"
          variant="tonal"
          @click="drawer = !drawer"
        >
          Menu
        </v-btn>

        <div>
          <div class="app-bar__eyebrow">Dark mode default</div>
          <div class="app-bar__title">Gym SaaS</div>
        </div>

        <v-spacer />

        <div class="text-right">
          <div class="text-caption muted-text text-uppercase">
            Live workspace
          </div>
          <div class="text-body-2 font-weight-medium">
            Mobile friendly up to desktop-wide
          </div>
        </div>
      </v-app-bar>

      <v-main class="app-main">
        <v-container
          :class="
            showAppShell ? 'app-shell__container' : 'public-shell__container'
          "
          fluid
        >
          <div
            :class="
              showAppShell ? 'app-shell__content' : 'public-shell__content'
            "
          >
            <slot />
          </div>
        </v-container>
      </v-main>
    </v-layout>
  </v-app>
</template>

<script setup lang="ts">
import { useDisplay } from "vuetify";

const route = useRoute();
const { mdAndUp } = useDisplay();
const { user, logout } = useAuth();

const publicRoutes = new Set(["/", "/login"]);
const drawer = ref(false);

const navigationItems = [
  {
    label: "Dashboard",
    description: "Operations overview",
    to: "/dashboard",
  },
  {
    label: "Members",
    description: "Roster and statuses",
    to: "/members",
  },
  {
    label: "Plans",
    description: "Offers and durations",
    to: "/plans",
  },
  {
    label: "Subscriptions",
    description: "Lifecycle and billing state",
    to: "/subscriptions",
  },
  {
    label: "Payments",
    description: "Manual verification queue",
    to: "/payments",
  },
  {
    label: "Attendance",
    description: "Check-ins and desk flow",
    to: "/attendance",
  },
  {
    label: "Staff",
    description: "Admin-managed operators",
    to: "/staff",
  },
  {
    label: "Branches",
    description: "Locations and ownership",
    to: "/branches",
  },
  {
    label: "Tenants",
    description: "Multi-gym accounts",
    to: "/tenants",
  },
];

const showAppShell = computed(() => !publicRoutes.has(route.path));

const isActive = (path: string) =>
  route.path === path || route.path.startsWith(`${path}/`);

const formatRole = (role?: string | null) =>
  role ? role.replace(/_/g, " ") : "No role loaded";

const handleLogout = async () => {
  await logout();
  await navigateTo("/login");
};

watch(
  mdAndUp,
  (value) => {
    drawer.value = value;
  },
  { immediate: true },
);

watch(
  () => route.path,
  () => {
    if (!mdAndUp.value) {
      drawer.value = false;
    }
  },
);
</script>
