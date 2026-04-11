<template>
  <span :class="classes">
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup lang="ts">
const props = defineProps<{
  label?: string;
  tone?: string;
}>();

const classes = computed(() => [
  "status-chip",
  `status-chip--${(props.tone ?? props.label ?? "default").toLowerCase()}`,
]);
</script>
