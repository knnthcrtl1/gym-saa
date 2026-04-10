<template>
  <div class="app-page">
    <div class="page-header">
      <div>
        <div class="page-header__eyebrow">Revenue desk</div>
        <h1 class="page-header__title">Payments</h1>
        <p class="page-header__body">
          The page shell is live and aligned with the new design system. Backend
          CRUD and verification flows are the next implementation layer for
          GCash and manual payment approval.
        </p>
      </div>

      <div class="toolbar-actions">
        <v-chip color="accent" variant="tonal">GCash ready later</v-chip>
        <v-btn color="primary">Record payment</v-btn>
      </div>
    </div>

    <div class="section-grid">
      <v-card class="section-grid__half content-panel">
        <v-card-text>
          <div class="panel-label">Implementation queue</div>
          <div class="text-h6 mt-2 mb-5">Planned payment actions</div>
          <div class="hero-list">
            <div class="hero-list__item">
              Manual payment capture with reference numbers
            </div>
            <div class="hero-list__item">
              Verification states for unpaid, partial, and paid
            </div>
            <div class="hero-list__item">
              Revenue linkage back to member subscriptions
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-card class="section-grid__half content-panel">
        <v-card-text>
          <div class="panel-label">Suggested layout</div>
          <div class="text-h6 mt-2 mb-5">UI prepared for</div>
          <div class="hero-list">
            <div class="hero-list__item">Verification queue</div>
            <div class="hero-list__item">
              Method tags for cash, GCash, and transfer
            </div>
            <div class="hero-list__item">
              Operator attribution and audit trail
            </div>
          </div>
        </v-card-text>
      </v-card>
    </div>
  </div>
</template>
