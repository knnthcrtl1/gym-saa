<template>
  <div class="hero-grid">
    <v-sheet class="hero-panel app-surface" rounded="xl">
      <div class="eyebrow">Gym Management Platform</div>
      <h1 class="hero-panel__title">
        Run every desk, plan, and payment in one place.
      </h1>
      <p class="hero-panel__body">
        Manage members, subscriptions, payments, and attendance from a single
        admin workspace built for gym operators.
      </p>

      <div class="hero-panel__actions">
        <v-btn color="primary" size="large" to="/dashboard">
          <Icon name="lucide:layout-dashboard" size="18" class="mr-2" />
          Open dashboard
        </v-btn>
        <v-btn size="large" variant="outlined" to="/login">
          <Icon name="lucide:log-in" size="18" class="mr-2" />
          Sign in
        </v-btn>
      </div>

      <div
        style="
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
          gap: 16px;
          margin-top: 40px;
        "
      >
        <v-card
          class="content-panel"
          rounded="xl"
          v-for="mod in modules"
          :key="mod.title"
        >
          <v-card-text>
            <div
              :class="['stat-card__icon', mod.iconClass]"
              style="margin-bottom: 12px"
            >
              <Icon :name="mod.icon" size="22" />
            </div>
            <div style="font-weight: 700; font-size: 1rem; margin-bottom: 4px">
              {{ mod.title }}
            </div>
            <div
              class="muted-text"
              style="font-size: 0.88rem; line-height: 1.5"
            >
              {{ mod.description }}
            </div>
          </v-card-text>
        </v-card>
      </div>
    </v-sheet>
  </div>
</template>

<script setup lang="ts">
const modules = [
  {
    title: "Dashboard",
    description: "Real-time metrics for members, revenue, and check-ins.",
    icon: "lucide:layout-dashboard",
    iconClass: "stat-card__icon--purple",
  },
  {
    title: "Members & Plans",
    description: "Manage members, plans, subscriptions, and branches.",
    icon: "lucide:users",
    iconClass: "stat-card__icon--blue",
  },
  {
    title: "Payments",
    description: "Track payments, verify transactions, and manage billing.",
    icon: "lucide:wallet",
    iconClass: "stat-card__icon--green",
  },
  {
    title: "Attendance",
    description: "Front-desk check-in flow and attendance tracking.",
    icon: "lucide:scan-line",
    iconClass: "stat-card__icon--amber",
  },
];
</script>
