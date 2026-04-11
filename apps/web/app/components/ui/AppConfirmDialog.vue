<template>
  <AppModalShell
    :model-value="modelValue"
    :title="title"
    :description="message"
    :max-width="520"
    eyebrow="Confirm action"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div class="app-confirm-dialog__body">
      <slot />
    </div>

    <template #footer>
      <AppButton
        tone="neutral"
        appearance="text"
        @click="emit('update:modelValue', false)"
      >
        {{ cancelText }}
      </AppButton>
      <AppButton
        :tone="tone === 'danger' ? 'danger' : 'primary'"
        :loading="loading"
        @click="emit('confirm')"
      >
        {{ confirmText }}
      </AppButton>
    </template>
  </AppModalShell>
</template>

<script setup lang="ts">
import AppButton from "./AppButton.vue";
import AppModalShell from "./AppModalShell.vue";

withDefaults(
  defineProps<{
    modelValue: boolean;
    title: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    tone?: "primary" | "danger";
    loading?: boolean;
  }>(),
  {
    confirmText: "Confirm",
    cancelText: "Cancel",
    tone: "primary",
    loading: false,
  },
);

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  confirm: [];
}>();
</script>

<style scoped>
.app-confirm-dialog__body {
  color: var(--gym-text-secondary);
  line-height: 1.6;
}
</style>
