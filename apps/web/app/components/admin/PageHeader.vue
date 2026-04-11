<template>
  <div class="page-header admin-page-header">
    <div class="admin-page-header__copy">
      <div v-if="eyebrow" class="page-header__eyebrow">{{ eyebrow }}</div>
      <h1 class="page-header__title">{{ title }}</h1>
      <p v-if="description" class="page-header__body">
        {{ description }}
      </p>
    </div>

    <div
      v-if="$slots.actions"
      class="toolbar-actions admin-page-header__actions"
    >
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  eyebrow?: string;
  title: string;
  description?: string;
}>();
</script>

<style scoped>
.admin-page-header__copy {
  min-width: 0;
}

.admin-page-header__actions {
  justify-content: flex-end;
}
</style>
