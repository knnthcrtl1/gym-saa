<template>
  <div class="app-page">
    <div class="page-header">
      <div>
        <div class="page-header__eyebrow">Operations dashboard</div>
        <h1 class="page-header__title">Daily gym pulse</h1>
        <p class="page-header__body">
          Live overview of member activity, subscriptions, and revenue.
        </p>
      </div>

      <div class="toolbar-actions">
        <v-btn color="accent" variant="outlined" to="/attendance">
          <Icon name="lucide:scan-line" size="18" class="mr-2" />
          Attendance
        </v-btn>
        <v-btn color="primary" to="/payments">
          <Icon name="lucide:wallet" size="18" class="mr-2" />
          Payments
        </v-btn>
      </div>
    </div>

    <div class="stat-grid">
      <v-card v-for="card in cards" :key="card.label" class="stat-card">
        <v-card-text>
          <div class="stat-card__label">{{ card.label }}</div>
          <div class="stat-card__value">{{ card.value }}</div>
          <div class="stat-card__delta">{{ card.caption }}</div>
        </v-card-text>
      </v-card>
    </div>

    <div class="section-grid">
      <v-card class="section-grid__half content-panel">
        <v-card-text>
          <div class="info-panel__header">
            <div>
              <div class="panel-label">Priority lane</div>
              <div class="text-h6 mt-2">What to watch this shift</div>
            </div>
          </div>

          <div class="hero-list mt-6">
            <div class="hero-list__item">
              Revenue is tracked from verified payments only.
            </div>
            <div class="hero-list__item">
              Attendance supports manual front-desk check-in flow.
            </div>
            <div class="hero-list__item">
              Expired subscriptions need follow-up for renewals.
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-card class="section-grid__half content-panel">
        <v-card-text>
          <div class="panel-label">Quick access</div>
          <div class="text-h6 mt-2 mb-6">Actions</div>
          <div class="toolbar-actions">
            <v-btn color="primary" to="/members">
              <Icon name="lucide:users" size="18" class="mr-2" />
              Members
            </v-btn>
            <v-btn color="accent" variant="outlined" to="/subscriptions">
              Subscriptions
            </v-btn>
            <v-btn color="accent" variant="outlined" to="/staff"> Staff </v-btn>
          </div>
        </v-card-text>
      </v-card>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ["auth"],
});

const { data } = await useDashboard();

const stats = computed(
  () =>
    data.value?.stats ?? {
      active_members: 0,
      expired_subscriptions: 0,
      today_checkins: 0,
      monthly_revenue: 0,
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
    label: "Active members",
    value: stats.value.active_members,
    caption: "Members in good standing",
  },
  {
    label: "Expired subscriptions",
    value: stats.value.expired_subscriptions,
    caption: "Due for renewal follow-up",
  },
  {
    label: "Today check-ins",
    value: stats.value.today_checkins,
    caption: "Front-desk activity today",
  },
  {
    label: "Monthly revenue",
    value: formatCurrency(stats.value.monthly_revenue),
    caption: "Verified payments this month",
  },
]);
</script>
