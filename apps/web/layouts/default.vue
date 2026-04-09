<template>
  <v-app>
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
