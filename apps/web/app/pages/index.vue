<template>
  <div class="hero-grid">
    <v-sheet class="hero-panel app-surface">
      <div class="eyebrow">Premium dark admin</div>
      <h1 class="hero-panel__title">
        Run every desk, plan, and payment in one gym command center.
      </h1>
      <p class="hero-panel__body">
        This admin workspace is now styled for a dark-by-default gym SaaS:
        stronger contrast, tighter data density, and a shell that scales from
        phones to wide desktop screens without stretching the content into
        noise.
      </p>

      <div class="hero-panel__actions">
        <v-btn color="primary" size="large" :to="primaryRoute">
          {{ primaryAction }}
        </v-btn>
        <v-btn color="accent" size="large" variant="outlined" to="/members">
          Preview member ops
        </v-btn>
      </div>

      <div class="section-grid mt-8">
        <v-card class="section-grid__half content-panel" rounded="xl">
          <v-card-text>
            <div class="panel-label">Prepared modules</div>
            <div class="text-h6 mt-3 mb-4">Current rollout</div>
            <div class="hero-list">
              <div class="hero-list__item">
                Dashboard metrics for members, revenue, and check-ins
              </div>
              <div class="hero-list__item">
                Members, plans, subscriptions, branches, and tenants
              </div>
              <div class="hero-list__item">
                New dark shell plus login, payments, attendance, and staff pages
              </div>
            </div>
          </v-card-text>
        </v-card>

        <v-card class="section-grid__half content-panel" rounded="xl">
          <v-card-text>
            <div class="panel-label">Access for local testing</div>
            <div class="text-h6 mt-3 mb-4">Seeded accounts</div>
            <div class="text-body-1">superadmin@gymsaas.local</div>
            <div class="text-body-1">admin@demofitness.local</div>
            <div class="muted-text mt-3">Password: password</div>
            <div class="muted-text mt-5">
              Public self-registration is intentionally excluded from this MVP.
              Staff accounts are meant to be created by admins inside the app.
            </div>
          </v-card-text>
        </v-card>
      </div>
    </v-sheet>

    <v-sheet class="hero-panel app-surface">
      <div class="eyebrow">What changed</div>
      <div class="text-h5 font-weight-bold mt-3 mb-5">
        Theme direction implemented first
      </div>
      <div class="hero-list">
        <div class="hero-list__item">
          Energy red primary for actions and critical focus
        </div>
        <div class="hero-list__item">
          Muted mustard accent for premium, flatter CTA highlights
        </div>
        <div class="hero-list__item">
          Deep black shell with layered charcoal surfaces
        </div>
        <div class="hero-list__item">
          Desktop clamp around 1440px with mobile-friendly stacking
        </div>
      </div>
    </v-sheet>
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig();

const primaryRoute = config.public.dashboardPreviewMode
  ? "/dashboard"
  : "/login";
const primaryAction = config.public.dashboardPreviewMode
  ? "Open dashboard preview"
  : "Sign in to admin";
</script>
