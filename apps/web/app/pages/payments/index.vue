<script setup lang="ts">
definePageMeta({
  middleware: ["auth"],
});
</script>

<template>
  <div class="app-page">
    <div class="page-header">
      <div>
        <div class="page-header__eyebrow">Revenue desk</div>
        <h1 class="page-header__title">Payments</h1>
        <p class="page-header__body">
          Record and verify member payments including GCash and manual
          transactions.
        </p>
      </div>

      <div class="toolbar-actions">
        <v-btn color="primary">
          <Icon name="lucide:plus" size="18" class="mr-2" />
          Record payment
        </v-btn>
      </div>
    </div>

    <v-card class="content-panel">
      <v-card-text>
        <div class="empty-state">
          <Icon
            name="lucide:wallet"
            size="48"
            class="mb-4"
            style="opacity: 0.3"
          />
          <div class="text-h6">No payments recorded yet</div>
          <div class="muted-text mt-2">
            Payment records will appear here once transactions are captured.
          </div>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>
