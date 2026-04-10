<template>
  <div class="hero-grid" style="grid-template-columns: 1fr">
    <v-sheet class="hero-panel app-surface">
      <div class="eyebrow">Gym Management</div>
      <h1 class="hero-panel__title">
        Run every desk, plan, and payment in one command center.
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
        <v-btn color="accent" size="large" variant="outlined" to="/members">
          <Icon name="lucide:users" size="18" class="mr-2" />
          View members
        </v-btn>
      </div>

      <div class="section-grid mt-8">
        <v-card class="section-grid__half content-panel" rounded="xl">
          <v-card-text>
            <div class="panel-label">Modules</div>
            <div class="text-h6 mt-3 mb-4">Available now</div>
            <div class="hero-list">
              <div class="hero-list__item">
                Dashboard metrics for members, revenue, and check-ins
              </div>
              <div class="hero-list__item">
                Members, plans, subscriptions, branches, and tenants
              </div>
              <div class="hero-list__item">
                Payments, attendance, and staff management
              </div>
            </div>
          </v-card-text>
        </v-card>
      </div>
    </v-sheet>
  </div>
</template>

<script setup lang="ts"></script>
