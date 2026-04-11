<template>
  <v-btn
    v-bind="$attrs"
    :color="color"
    :variant="variant"
    :type="type"
    :class="buttonClass"
    rounded="lg"
  >
    <slot />
  </v-btn>
</template>

<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    tone?: "primary" | "neutral" | "danger";
    appearance?: "solid" | "outline" | "text";
    type?: "button" | "submit" | "reset";
  }>(),
  {
    tone: "primary",
    appearance: "solid",
    type: "button",
  },
);

const color = computed(() => {
  if (props.tone === "danger") {
    return "error";
  }

  if (props.tone === "primary") {
    return "primary";
  }

  return undefined;
});

const variant = computed(() => {
  if (props.appearance === "outline") {
    return "outlined";
  }

  if (props.appearance === "text") {
    return "text";
  }

  return "flat";
});

const buttonClass = computed(() => [
  "app-button",
  `app-button--${props.tone}`,
  `app-button--${props.appearance}`,
]);
</script>

<style scoped>
.app-button {
  text-transform: none;
  font-weight: 700;
  letter-spacing: 0;
}

.app-button--solid.app-button--neutral {
  background: var(--gym-surface-soft);
  color: var(--gym-text-primary);
}

.app-button--text.app-button--danger {
  color: #dc2626;
}
</style>
