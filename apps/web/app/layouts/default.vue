<template>
  <v-app class="gym-app">
    <v-layout>
      <!-- Persistent sidebar (desktop) / overlay drawer (mobile) -->
      <v-navigation-drawer
        v-if="showAppShell"
        v-model="drawer"
        :rail="isDesktop && drawerRail"
        :rail-width="88"
        :permanent="isDesktop"
        :temporary="!isDesktop"
        class="app-drawer"
        width="260"
      >
        <div class="app-drawer__inner">
          <!-- Brand -->
          <NuxtLink class="app-drawer__brand" to="/dashboard">
            <div class="app-drawer__brand-icon">
              <Icon name="lucide:dumbbell" size="20" />
            </div>
            <span class="app-drawer__brand-text">Gym SaaS</span>
          </NuxtLink>

          <!-- Section label -->
          <div class="app-drawer__section">Main Menu</div>

          <!-- Nav items -->
          <v-list class="app-nav" density="compact" nav>
            <v-list-item
              v-for="item in visibleNavigationItems"
              :key="item.to"
              :active="isActive(item.to)"
              :title="item.label"
              :to="item.to"
              color="primary"
            >
              <template #prepend>
                <Icon :name="item.icon" size="20" class="mr-3" />
              </template>
            </v-list-item>
          </v-list>

          <!-- Bottom CTA -->
          <div class="sidebar-cta">
            <div class="sidebar-cta__card">
              <div class="sidebar-cta__icon">
                <Icon name="lucide:scan-line" size="22" />
              </div>
              <div class="sidebar-cta__title">Quick Check-in</div>
              <v-btn
                class="sidebar-cta__btn"
                size="small"
                rounded="xl"
                block
                to="/attendance"
              >
                Check in now
              </v-btn>
            </div>
          </div>
        </div>
      </v-navigation-drawer>

      <!-- Top bar -->
      <v-app-bar v-if="showAppShell" class="app-bar" flat>
        <div class="app-bar__headline">
          <v-btn
            icon
            variant="text"
            class="app-bar__menu-btn"
            @click="toggleNavigation"
          >
            <Icon
              :name="isDesktop ? 'lucide:panel-left' : 'lucide:menu'"
              size="22"
            />
          </v-btn>

          <div class="app-bar__title">{{ currentPageTitle }}</div>
        </div>

        <v-spacer />

        <!-- Icon buttons -->
        <div class="app-bar__icons">
          <button class="app-bar__icon-btn" title="Notifications">
            <Icon name="lucide:bell" size="20" />
            <span class="badge-dot" />
          </button>
          <button class="app-bar__icon-btn" title="Tasks">
            <Icon name="lucide:clipboard-check" size="20" />
          </button>
          <button
            class="app-bar__icon-btn"
            @click="toggleTheme"
            title="Toggle theme"
          >
            <Icon :name="isDark ? 'lucide:sun' : 'lucide:moon'" size="20" />
          </button>
        </div>

        <!-- User profile -->
        <v-menu offset-y>
          <template #activator="{ props }">
            <div class="app-bar__user" v-bind="props">
              <div class="app-bar__avatar">
                {{ userInitials }}
              </div>
              <div class="app-bar__user-info">
                <div class="app-bar__user-name">
                  {{ user?.name || "Gym Operator" }}
                </div>
                <div class="app-bar__user-role">
                  {{ formatRole(user?.role) }}
                </div>
              </div>
              <Icon
                name="lucide:chevron-down"
                size="16"
                style="color: var(--gym-text-muted)"
              />
            </div>
          </template>
          <v-list density="compact">
            <v-list-item title="Sign out" @click="handleLogout">
              <template #prepend>
                <Icon name="lucide:log-out" size="18" class="mr-2" />
              </template>
            </v-list-item>
          </v-list>
        </v-menu>
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
import { useAuthorization } from "../../composables/useAuthorization";
import type { AuthUser } from "../../types/api";
import { useTheme, useDisplay } from "vuetify";

const route = useRoute();
const user = useState<AuthUser | null>("auth.user", () => null);
const { hasPermission } = useAuthorization();
const theme = useTheme();
const { mdAndUp } = useDisplay();

const publicRoutes = new Set(["/", "/login"]);
const drawer = ref(true);
const drawerRail = ref(false);

const isDesktop = computed(() => mdAndUp.value);
const isDark = computed(() => theme.global.current.value.dark);

const toggleTheme = () => {
  theme.global.name.value = isDark.value ? "gymLight" : "gymDark";
};

const toggleNavigation = () => {
  if (isDesktop.value) {
    drawerRail.value = !drawerRail.value;
    return;
  }

  drawer.value = !drawer.value;
};

const userInitials = computed(() => {
  const name = user.value?.name || "GO";
  return name
    .split(" ")
    .map((n) => n[0])
    .join("")
    .toUpperCase()
    .slice(0, 2);
});

const pageTitles: Record<string, string> = {
  "/dashboard": "Dashboard",
  "/members": "Members",
  "/plans": "Plans",
  "/subscriptions": "Subscriptions",
  "/payments": "Payments",
  "/attendance": "Attendance",
  "/staff": "Staff",
  "/branches": "Branches",
  "/tenants": "Tenants",
};

const currentPageTitle = computed(() => {
  for (const [path, title] of Object.entries(pageTitles)) {
    if (route.path === path || route.path.startsWith(`${path}/`)) {
      return title;
    }
  }
  return "Dashboard";
});

const navigationItems = [
  {
    label: "Dashboard",
    to: "/dashboard",
    icon: "lucide:layout-dashboard",
    permission: "dashboard.view",
  },
  {
    label: "Members",
    to: "/members",
    icon: "lucide:users",
    permission: "members.view",
  },
  {
    label: "Plans",
    to: "/plans",
    icon: "lucide:credit-card",
    permission: "plans.view",
  },
  {
    label: "Subscriptions",
    to: "/subscriptions",
    icon: "lucide:repeat",
    permission: "subscriptions.view",
  },
  {
    label: "Payments",
    to: "/payments",
    icon: "lucide:wallet",
    permission: "payments.view",
  },
  {
    label: "Attendance",
    to: "/attendance",
    icon: "lucide:scan-line",
    permission: "attendance.view",
  },
  {
    label: "Staff",
    to: "/staff",
    icon: "lucide:user-cog",
    permission: "staff.view",
  },
  {
    label: "Branches",
    to: "/branches",
    icon: "lucide:map-pin",
    permission: "branches.view",
  },
  {
    label: "Tenants",
    to: "/tenants",
    icon: "lucide:building-2",
    permission: "tenants.view",
  },
];

const visibleNavigationItems = computed(() =>
  navigationItems.filter((item) => hasPermission(item.permission)),
);

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
    if (!isDesktop.value) {
      drawer.value = false;
    }
  },
);

watch(isDesktop, (desktop) => {
  if (!desktop) {
    drawerRail.value = false;
  }
});
</script>
