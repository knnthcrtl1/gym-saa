<template>
  <v-app>
    <v-layout class="rounded-lg">
      <v-navigation-drawer v-if="showAppShell" permanent>
        <v-list nav>
          <v-list-item title="Dashboard" to="/dashboard" />
          <v-list-item title="Tenants" to="/tenants" />
          <v-list-item title="Branches" to="/branches" />
          <v-list-item title="Members" to="/members" />
          <v-list-item title="Plans" to="/plans" />
          <v-list-item title="Subscriptions" to="/subscriptions" />
        </v-list>
      </v-navigation-drawer>

      <v-app-bar v-if="showAppShell" flat>
        <v-app-bar-title>Gym SaaS</v-app-bar-title>
      </v-app-bar>

      <v-main>
        <v-container :class="showAppShell ? 'py-6' : 'pa-0 fill-height'" fluid>
          <slot />
        </v-container>
      </v-main>
    </v-layout>
  </v-app>
</template>

<script setup lang="ts">
const route = useRoute();

const showAppShell = computed(() => route.path !== "/");
</script>
