<template>
  <div>
    <h1 class="text-h4 font-weight-bold mb-4">Gym SaaS Dashboard</h1>

    <v-row>
      <v-col cols="12" md="3" v-for="card in cards" :key="card.label">
        <v-card rounded="xl">
          <v-card-text>
            <div class="text-subtitle-2 text-medium-emphasis">
              {{ card.label }}
            </div>
            <div class="text-h5 mt-2">{{ card.value }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup lang="ts">
const cards = [
  { label: "Active Members", value: 0 },
  { label: "Expired Members", value: 0 },
  { label: "Today Check-ins", value: 0 },
  { label: "Monthly Revenue", value: "₱0" },
];
</script>
