<template>
  <v-app class="gym-app">
    <v-layout>
      <v-navigation-drawer
        v-if="showAppShell"
        v-model="drawer"
        temporary
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
            >
              <template #prepend>
                <Icon :name="item.icon" size="20" class="mr-3" />
              </template>
            </v-list-item>
          </v-list>

          <v-spacer />

          <div class="app-user-card">
            <div class="page-header__eyebrow">Signed in</div>
            <div class="text-subtitle-1 font-weight-bold mt-2">
              {{ user?.name || "Gym Operator" }}
            </div>
            <div class="muted-text text-body-2 mt-1">
              {{ user?.email || "No session" }}
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
          class="mr-3"
          color="primary"
          variant="tonal"
          @click="drawer = !drawer"
        >
          <Icon name="lucide:menu" size="20" class="mr-2" />
          Menu
        </v-btn>

        <div>
          <div class="app-bar__title">Gym SaaS</div>
        </div>

        <v-spacer />

        <v-btn icon variant="text" @click="toggleTheme">
          <Icon :name="isDark ? 'lucide:sun' : 'lucide:moon'" size="20" />
        </v-btn>
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
import type { AuthUser } from "../../types/api";
import { useTheme } from "vuetify";

const route = useRoute();
const user = useState<AuthUser | null>("auth.user", () => null);
const theme = useTheme();

const publicRoutes = new Set(["/", "/login"]);
const drawer = ref(true);

const isDark = computed(() => theme.global.current.value.dark);

const toggleTheme = () => {
  theme.global.name.value = isDark.value ? "gymLight" : "gymDark";
};

const navigationItems = [
  {
    label: "Dashboard",
    description: "Operations overview",
    to: "/dashboard",
    icon: "lucide:layout-dashboard",
  },
  {
    label: "Members",
    description: "Roster and statuses",
    to: "/members",
    icon: "lucide:users",
  },
  {
    label: "Plans",
    description: "Offers and durations",
    to: "/plans",
    icon: "lucide:credit-card",
  },
  {
    label: "Subscriptions",
    description: "Lifecycle and billing",
    to: "/subscriptions",
    icon: "lucide:repeat",
  },
  {
    label: "Payments",
    description: "Verification queue",
    to: "/payments",
    icon: "lucide:wallet",
  },
  {
    label: "Attendance",
    description: "Check-ins and desk flow",
    to: "/attendance",
    icon: "lucide:scan-line",
  },
  {
    label: "Staff",
    description: "Admin-managed operators",
    to: "/staff",
    icon: "lucide:user-cog",
  },
  {
    label: "Branches",
    description: "Locations and ownership",
    to: "/branches",
    icon: "lucide:map-pin",
  },
  {
    label: "Tenants",
    description: "Multi-gym accounts",
    to: "/tenants",
    icon: "lucide:building-2",
  },
];

const showAppShell = computed(() => !publicRoutes.has(route.path));

const isActive = (path: string) =>
  route.path === path || route.path.startsWith(`${path}/`);

const formatRole = (role?: string | null) =>
  role ? role.replace(/_/g, " ") : "No role loaded";

const handleLogout = async () => {
  const { logout } = useAuth();
  await logout();
  await navigateTo("/login");
};

watch(
  () => route.path,
  () => {
    if (showAppShell.value) {
      drawer.value = false;
    }
  },
);
</script>
