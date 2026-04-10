<template>
  <div class="app-page">
    <div class="page-header">
      <div>
        <div class="page-header__eyebrow">Admin-managed access</div>
        <h1 class="page-header__title">Staff management</h1>
        <p class="page-header__body">
          Public signup is intentionally excluded. This area is prepared for gym
          admins to create and manage operator accounts, branch assignment, and
          role status.
        </p>
      </div>

      <div class="toolbar-actions">
        <v-chip color="accent" variant="tonal">No public register</v-chip>
        <v-btn color="primary">Add staff account</v-btn>
      </div>
    </div>

    <div class="section-grid">
      <v-card class="section-grid__half content-panel">
        <v-card-text>
          <div class="panel-label">Roles in scope</div>
          <div class="text-h6 mt-2 mb-5">Current role model</div>
          <div class="hero-list">
            <div class="hero-list__item">
              Super admin for platform-wide oversight
            </div>
            <div class="hero-list__item">
              Gym admin for tenant-level control
            </div>
            <div class="hero-list__item">Staff for branch-bound operation</div>
          </div>
        </v-card-text>
      </v-card>

      <v-card class="section-grid__half content-panel">
        <v-card-text>
          <div class="panel-label">Planned controls</div>
          <div class="text-h6 mt-2 mb-5">CRUD flow to add next</div>
          <div class="hero-list">
            <div class="hero-list__item">Create and suspend staff accounts</div>
            <div class="hero-list__item">
              Assign tenant and branch ownership
            </div>
            <div class="hero-list__item">
              Track payment and attendance verification operators
            </div>
          </div>
        </v-card-text>
      </v-card>
    </div>
  </div>
</template>
