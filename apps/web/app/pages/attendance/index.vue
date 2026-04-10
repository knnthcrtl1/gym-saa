<template>
  <div class="app-page">
    <div class="page-header">
      <div>
        <div class="page-header__eyebrow">Front desk flow</div>
        <h1 class="page-header__title">Attendance logs</h1>
        <p class="page-header__body">
          This page is prepared for the attendance module, with space for manual
          check-in handling now and QR scanning later without changing the
          overall screen structure.
        </p>
      </div>

      <div class="toolbar-actions">
        <v-chip color="accent" variant="tonal">QR-ready later</v-chip>
        <v-btn color="primary">Manual check-in</v-btn>
      </div>
    </div>

    <div class="section-grid">
      <v-card class="section-grid__half content-panel">
        <v-card-text>
          <div class="panel-label">Desk lanes</div>
          <div class="text-h6 mt-2 mb-5">Prepared interactions</div>
          <div class="hero-list">
            <div class="hero-list__item">Current shift check-ins</div>
            <div class="hero-list__item">
              Late verification by staff operator
            </div>
            <div class="hero-list__item">
              Subscription and branch context per entry
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-card class="section-grid__half content-panel">
        <v-card-text>
          <div class="panel-label">Mobile behavior</div>
          <div class="text-h6 mt-2 mb-5">Why this shell works on phones</div>
          <div class="hero-list">
            <div class="hero-list__item">
              Primary actions stay top-level and tappable
            </div>
            <div class="hero-list__item">
              Dense tables can scroll horizontally without collapsing
            </div>
            <div class="hero-list__item">
              Temporary drawer keeps navigation off-canvas on small screens
            </div>
          </div>
        </v-card-text>
      </v-card>
    </div>
  </div>
</template>
