<template>
  <v-dialog v-model="internalOpen" :max-width="maxWidth" scrollable>
    <v-card class="app-modal-shell" rounded="xl">
      <div class="app-modal-shell__header">
        <div class="app-modal-shell__copy">
          <div v-if="eyebrow" class="panel-label">{{ eyebrow }}</div>
          <div class="text-h5 font-weight-bold mt-2">{{ title }}</div>
          <p v-if="description" class="app-modal-shell__description">
            {{ description }}
          </p>
        </div>

        <div class="app-modal-shell__header-actions">
          <slot name="header-actions" />
          <v-btn v-if="closable" icon variant="text" @click="closeDialog">
            <Icon name="lucide:x" size="18" />
          </v-btn>
        </div>
      </div>

      <div class="app-modal-shell__body">
        <slot />
      </div>

      <div v-if="hasFooter" class="app-modal-shell__footer">
        <div class="app-modal-shell__footer-prepend">
          <slot name="footer-prepend" />
        </div>
        <div class="app-modal-shell__footer-actions">
          <slot name="footer" />
        </div>
      </div>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    modelValue: boolean;
    title: string;
    eyebrow?: string;
    description?: string;
    maxWidth?: number | string;
    closable?: boolean;
  }>(),
  {
    eyebrow: undefined,
    description: undefined,
    maxWidth: 860,
    closable: true,
  },
);

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  close: [];
}>();

const slots = useSlots();

const internalOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit("update:modelValue", value),
});

const hasFooter = computed(() =>
  Boolean(slots.footer || slots["footer-prepend"]),
);

const closeDialog = () => {
  emit("close");
  internalOpen.value = false;
};
</script>

<style scoped>
.app-modal-shell {
  display: flex;
  flex-direction: column;
  max-height: min(88vh, 920px);
  overflow: hidden;
}

.app-modal-shell__header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 24px 24px 20px;
  border-bottom: 1px solid var(--gym-border);
}

.app-modal-shell__copy {
  min-width: 0;
}

.app-modal-shell__description {
  margin: 8px 0 0;
  color: var(--gym-text-secondary);
  line-height: 1.6;
}

.app-modal-shell__header-actions {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.app-modal-shell__body {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

.app-modal-shell__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 16px 24px;
  border-top: 1px solid var(--gym-border);
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(12px);
}

.app-modal-shell__footer-prepend,
.app-modal-shell__footer-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

@media (max-width: 640px) {
  .app-modal-shell__header,
  .app-modal-shell__body,
  .app-modal-shell__footer {
    padding-left: 16px;
    padding-right: 16px;
  }

  .app-modal-shell__footer {
    flex-direction: column;
    align-items: stretch;
  }

  .app-modal-shell__footer-prepend,
  .app-modal-shell__footer-actions {
    width: 100%;
    justify-content: space-between;
  }
}
</style>
