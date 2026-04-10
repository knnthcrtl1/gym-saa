<template>
  <div class="app-page">
    <div class="page-header">
      <div>
        <div class="page-header__eyebrow">Operations dashboard</div>
        <h1 class="page-header__title">Daily gym pulse</h1>
        <p class="page-header__body">
          Track live member activity, subscriptions drifting out of cycle, and
          the revenue picture for the current month from one high-contrast
          dashboard.
        </p>
      </div>

      <div class="toolbar-actions">
        <v-chip v-if="preview.isPreview" color="accent" variant="tonal">
          Preview mode
        </v-chip>
        <v-btn color="accent" variant="outlined" to="/attendance"
          >Attendance flow</v-btn
        >
        <v-btn color="primary" to="/payments">Review payments</v-btn>
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
            <v-chip color="accent" variant="tonal">Desk ready</v-chip>
          </div>

          <div class="hero-list mt-6">
            <div class="hero-list__item">
              Revenue is tracked from verified payments only, so manual
              verification affects this card.
            </div>
            <div class="hero-list__item">
              Attendance logs are staged for future QR scan support but usable
              for manual front-desk flow.
            </div>
            <div class="hero-list__item">
              The shell now clamps wide-screen content instead of stretching
              cards edge to edge.
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-card class="section-grid__half content-panel">
        <v-card-text>
          <div class="panel-label">Team controls</div>
          <div class="text-h6 mt-2 mb-6">Fast actions</div>
          <div class="toolbar-actions">
            <v-btn color="primary" to="/members">Members</v-btn>
            <v-btn color="accent" variant="outlined" to="/subscriptions"
              >Subscriptions</v-btn
            >
            <v-btn color="accent" variant="outlined" to="/staff"
              >Staff roster</v-btn
            >
          </div>
        </v-card-text>
      </v-card>
    </div>
  </div>
</template>

<script setup lang="ts">
const { data, preview } = await useDashboard();

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
    caption: "Members currently in good standing",
  },
  {
    label: "Expired subscriptions",
    value: stats.value.expired_subscriptions,
    caption: "Follow-up queue for renewals",
  },
  {
    label: "Today check-ins",
    value: stats.value.today_checkins,
    caption: "Front-desk activity for the current day",
  },
  {
    label: "Monthly revenue",
    value: formatCurrency(stats.value.monthly_revenue),
    caption: preview.value.isPreview
      ? "Preview dataset while API auth is offline"
      : "Paid transactions recorded this month",
  },
]);
</script>
