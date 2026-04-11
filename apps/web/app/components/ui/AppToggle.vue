<template>
  <v-switch
    :model-value="modelValue"
    color="primary"
    hide-details
    inset
    class="app-toggle"
    @update:model-value="emit('update:modelValue', Boolean($event))"
  >
    <template v-if="label" #label>
      <span class="app-toggle__label">{{ label }}</span>
    </template>
  </v-switch>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: boolean;
  label?: string;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
}>();
</script>

<style scoped>
.app-toggle__label {
  color: var(--gym-text-primary);
  font-weight: 600;
}
</style>
