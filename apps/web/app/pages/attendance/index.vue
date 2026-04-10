<template>
  <div class="app-page">
    <div class="page-header">
      <div>
        <div class="page-header__eyebrow">Front desk flow</div>
        <h1 class="page-header__title">Attendance</h1>
        <p class="page-header__body">
          Track member check-ins and manage front-desk attendance.
        </p>
      </div>

      <div class="toolbar-actions">
        <v-btn color="primary">
          <Icon name="lucide:plus" size="18" class="mr-2" />
          Manual check-in
        </v-btn>
      </div>
    </div>

    <v-card class="content-panel">
      <v-card-text>
        <div class="empty-state">
          <Icon
            name="lucide:scan-line"
            size="48"
            class="mb-4"
            style="opacity: 0.3"
          />
          <div class="text-h6">No check-ins today</div>
          <div class="muted-text mt-2">
            Attendance records will appear here as members check in.
          </div>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>
