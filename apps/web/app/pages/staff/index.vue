<template>
  <div class="app-page">
    <div class="page-header">
      <div>
        <div class="page-header__eyebrow">Admin-managed access</div>
        <h1 class="page-header__title">Staff</h1>
        <p class="page-header__body">
          Create and manage operator accounts, branch assignments, and roles.
        </p>
      </div>

      <div class="toolbar-actions">
        <v-btn color="primary">
          <Icon name="lucide:plus" size="18" class="mr-2" />
          Add staff account
        </v-btn>
      </div>
    </div>

    <v-card class="content-panel">
      <v-card-text>
        <div class="empty-state">
          <Icon
            name="lucide:user-cog"
            size="48"
            class="mb-4"
            style="opacity: 0.3"
          />
          <div class="text-h6">No staff accounts yet</div>
          <div class="muted-text mt-2">
            Staff accounts created by admins will appear here.
          </div>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>
