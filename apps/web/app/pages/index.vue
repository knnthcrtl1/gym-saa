<template>
  <div>
    <h1 class="text-h4 font-weight-bold mb-4">Dashboard</h1>

    <v-row>
      <v-col cols="12" md="3">
        <v-card rounded="xl">
          <v-card-text>
            <div class="text-subtitle-2">Active Members</div>
            <div class="text-h5 mt-2">{{ stats.active_members }}</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3">
        <v-card rounded="xl">
          <v-card-text>
            <div class="text-subtitle-2">Expired Subscriptions</div>
            <div class="text-h5 mt-2">{{ stats.expired_subscriptions }}</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3">
        <v-card rounded="xl">
          <v-card-text>
            <div class="text-subtitle-2">Today Check-ins</div>
            <div class="text-h5 mt-2">{{ stats.today_checkins }}</div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3">
        <v-card rounded="xl">
          <v-card-text>
            <div class="text-subtitle-2">Monthly Revenue</div>
            <div class="text-h5 mt-2">PHP {{ stats.monthly_revenue }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup lang="ts">
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
</script>
