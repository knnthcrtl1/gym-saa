<template>
  <div class="app-slider">
    <div v-if="label" class="app-slider__header">
      <span class="app-slider__label">{{ label }}</span>
      <span class="app-slider__value">{{ modelValue }}</span>
    </div>

    <v-slider
      :model-value="modelValue"
      color="primary"
      track-color="rgba(79, 70, 229, 0.16)"
      thumb-color="primary"
      hide-details
      @update:model-value="emit('update:modelValue', Number($event))"
    />
  </div>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: number;
  label?: string;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: number];
}>();
</script>

<style scoped>
.app-slider {
  display: grid;
  gap: 8px;
}

.app-slider__header {
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.app-slider__label {
  color: var(--gym-text-primary);
  font-weight: 600;
}

.app-slider__value {
  color: var(--gym-text-secondary);
  font-size: 0.9rem;
}
</style>
