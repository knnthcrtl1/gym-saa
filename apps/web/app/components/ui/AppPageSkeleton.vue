<template>
  <div class="app-page">
    <div class="page-header">
      <div>
        <v-skeleton-loader type="text" width="120" />
        <v-skeleton-loader type="heading" width="280" class="mt-2" />
        <v-skeleton-loader type="text" width="400" class="mt-1" />
      </div>
    </div>

    <div v-if="showStats" class="stat-grid">
      <v-card v-for="n in statCount" :key="n" class="stat-card">
        <v-card-text>
          <v-skeleton-loader type="text" width="80" />
          <v-skeleton-loader type="heading" width="60" class="mt-2" />
        </v-card-text>
      </v-card>
    </div>

    <v-card class="content-panel" rounded="xl">
      <v-card-text>
        <v-skeleton-loader type="table-thead" />
        <v-skeleton-loader type="table-tbody" />
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    showStats?: boolean;
    statCount?: number;
  }>(),
  {
    showStats: true,
    statCount: 3,
  },
);
</script>
