<template>
  <v-app>
    <v-navigation-drawer permanent>
      <v-list nav>
        <v-list-item title="Dashboard" to="/" />
        <v-list-item title="Tenants" to="/tenants" />
        <v-list-item title="Branches" to="/branches" />
        <v-list-item title="Members" to="/members" />
        <v-list-item title="Plans" to="/plans" />
        <v-list-item title="Subscriptions" to="/subscriptions" />
      </v-list>
    </v-navigation-drawer>

    <v-app-bar flat>
      <v-app-bar-title>Gym SaaS</v-app-bar-title>
    </v-app-bar>

    <v-main>
      <v-container class="py-6">
        <slot />
      </v-container>
    </v-main>
  </v-app>
</template>
