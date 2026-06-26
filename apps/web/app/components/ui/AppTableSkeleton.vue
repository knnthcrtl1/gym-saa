<template>
  <tbody>
    <tr v-for="row in rows" :key="row">
      <td v-for="col in columns" :key="col">
        <v-skeleton-loader type="text" />
      </td>
    </tr>
  </tbody>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    rows?: number;
    columns?: number;
  }>(),
  {
    rows: 5,
    columns: 5,
  },
);
</script>
