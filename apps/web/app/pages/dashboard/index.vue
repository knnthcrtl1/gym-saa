<template>
  <div class="app-page">
    <div class="page-header">
      <div>
        <div class="page-header__eyebrow">Overview</div>
        <h1 class="page-header__title">
          Welcome back, {{ user?.name?.split(" ")[0] || "Operator" }}
        </h1>
        <p class="page-header__body">
          Here's what's happening at your gym today.
        </p>
      </div>

      <div class="toolbar-actions">
        <v-btn variant="outlined" to="/attendance">
          <Icon name="lucide:scan-line" size="18" class="mr-2" />
          Attendance
        </v-btn>
        <v-btn color="primary" to="/payments">
          <Icon name="lucide:wallet" size="18" class="mr-2" />
          Payments
        </v-btn>
      </div>
    </div>

    <!-- Stat cards row -->
    <div class="stat-grid">
      <v-card v-for="card in cards" :key="card.label" class="stat-card">
        <v-card-text>
          <div style="display: flex; align-items: flex-start; gap: 14px">
            <div :class="['stat-card__icon', card.iconClass]">
              <Icon :name="card.icon" size="22" />
            </div>
            <div style="flex: 1; min-width: 0">
              <div class="stat-card__label">{{ card.label }}</div>
              <div class="stat-card__value">{{ card.value }}</div>
              <div class="stat-card__delta">{{ card.caption }}</div>
              <div class="stat-card__bar">
                <div
                  :class="['stat-card__bar-fill', card.barClass]"
                  :style="{ width: card.barWidth }"
                />
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>
    </div>

    <!-- Content sections -->
    <div class="dashboard-content">
      <v-card class="content-panel">
        <v-card-text>
          <div class="info-panel__header">
            <div>
              <div class="panel-label">Priority Lane</div>
              <div class="text-h6 mt-2">What to watch this shift</div>
            </div>
            <v-btn variant="outlined" size="small" to="/members">
              View More
            </v-btn>
          </div>

          <div class="hero-list mt-5">
            <div
              class="hero-list__item"
              style="display: flex; align-items: center; gap: 12px"
            >
              <div
                class="stat-card__icon stat-card__icon--amber"
                style="width: 38px; height: 38px; border-radius: 10px"
              >
                <Icon name="lucide:alert-circle" size="18" />
              </div>
              <div>
                <div style="font-weight: 600; font-size: 0.9rem">
                  Revenue Tracking
                </div>
                <div class="muted-text" style="font-size: 0.82rem">
                  Revenue is tracked from verified payments only.
                </div>
              </div>
            </div>
            <div
              class="hero-list__item"
              style="display: flex; align-items: center; gap: 12px"
            >
              <div
                class="stat-card__icon stat-card__icon--blue"
                style="width: 38px; height: 38px; border-radius: 10px"
              >
                <Icon name="lucide:scan-line" size="18" />
              </div>
              <div>
                <div style="font-weight: 600; font-size: 0.9rem">
                  Check-in Flow
                </div>
                <div class="muted-text" style="font-size: 0.82rem">
                  Attendance supports manual front-desk check-in.
                </div>
              </div>
            </div>
            <div
              class="hero-list__item"
              style="display: flex; align-items: center; gap: 12px"
            >
              <div
                class="stat-card__icon stat-card__icon--red"
                style="width: 38px; height: 38px; border-radius: 10px"
              >
                <Icon name="lucide:clock" size="18" />
              </div>
              <div>
                <div style="font-weight: 600; font-size: 0.9rem">
                  Expiring Subscriptions
                </div>
                <div class="muted-text" style="font-size: 0.82rem">
                  Follow up with members on expired plans.
                </div>
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-card class="content-panel">
        <v-card-text>
          <div class="panel-label">Quick Actions</div>
          <div class="text-h6 mt-2 mb-5">Navigate</div>
          <div style="display: grid; gap: 10px">
            <v-btn color="primary" block to="/members">
              <Icon name="lucide:users" size="18" class="mr-2" />
              Members
            </v-btn>
            <v-btn variant="outlined" block to="/subscriptions">
              <Icon name="lucide:repeat" size="18" class="mr-2" />
              Subscriptions
            </v-btn>
            <v-btn variant="outlined" block to="/plans">
              <Icon name="lucide:credit-card" size="18" class="mr-2" />
              Plans
            </v-btn>
            <v-btn variant="outlined" block to="/staff">
              <Icon name="lucide:user-cog" size="18" class="mr-2" />
              Staff
            </v-btn>
          </div>
        </v-card-text>
      </v-card>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { AuthUser } from "../../../types/api";

definePageMeta({
  middleware: ["auth", "can"],
  permission: "dashboard.view",
});

const user = useState<AuthUser | null>("auth.user", () => null);
const { data } = await useDashboard();

const stats = computed(
  () =>
    data.value?.stats ?? {
      active_members: 0,
      expired_members: 0,
      expired_subscriptions: 0,
      new_members_this_month: 0,
      today_checkins: 0,
      payments_today: 0,
      payments_this_month: 0,
      income_today: 0,
      monthly_revenue: 0,
      upcoming_renewals: 0,
    },
);

const formatCurrency = (value: number) =>
  new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    maximumFractionDigits: 0,
  }).format(value);

const cards = computed(() => [
  {
    label: "Active Members",
    value: stats.value.active_members,
    caption: "Members in good standing",
    icon: "lucide:users",
    iconClass: "stat-card__icon--green",
    barClass: "stat-card__bar-fill--green",
    barWidth: "72%",
  },
  {
    label: "New This Month",
    value: stats.value.new_members_this_month,
    caption: "New registrations this month",
    icon: "lucide:user-plus",
    iconClass: "stat-card__icon--green",
    barClass: "stat-card__bar-fill--green",
    barWidth: "42%",
  },
  {
    label: "Today Check-ins",
    value: stats.value.today_checkins,
    caption: "Front-desk activity today",
    icon: "lucide:scan-line",
    iconClass: "stat-card__icon--blue",
    barClass: "stat-card__bar-fill--blue",
    barWidth: "45%",
  },
  {
    label: "Payments Today",
    value: stats.value.payments_today,
    caption: "Paid transactions recorded today",
    icon: "lucide:receipt-text",
    iconClass: "stat-card__icon--blue",
    barClass: "stat-card__bar-fill--blue",
    barWidth: "54%",
  },
  {
    label: "Monthly Revenue",
    value: formatCurrency(stats.value.monthly_revenue),
    caption: "Verified payments this month",
    icon: "lucide:wallet",
    iconClass: "stat-card__icon--amber",
    barClass: "stat-card__bar-fill--amber",
    barWidth: "60%",
  },
  {
    label: "Upcoming Renewals",
    value: stats.value.upcoming_renewals,
    caption: "Subscriptions ending within 7 days",
    icon: "lucide:calendar-clock",
    iconClass: "stat-card__icon--amber",
    barClass: "stat-card__bar-fill--amber",
    barWidth: "38%",
  },
  {
    label: "Expired Members",
    value: stats.value.expired_members,
    caption: "Due for renewal follow-up",
    icon: "lucide:clock",
    iconClass: "stat-card__icon--red",
    barClass: "stat-card__bar-fill--red",
    barWidth: "30%",
  },
]);
</script>
