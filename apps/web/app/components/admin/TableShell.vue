<template>
  <v-card class="table-panel app-table-shell" rounded="xl">
    <v-card-text class="app-table-shell__content">
      <slot name="notice" />

      <div
        class="table-toolbar app-table-shell__header"
        :class="{ 'mb-4': hasHeader }"
      >
        <div
          v-if="eyebrow || title || description"
          class="app-table-shell__copy"
        >
          <div v-if="eyebrow" class="panel-label">{{ eyebrow }}</div>
          <div v-if="title" class="text-h6 mt-2">{{ title }}</div>
          <p v-if="description" class="app-table-shell__description mt-2">
            {{ description }}
          </p>
        </div>

        <div
          v-if="$slots.actions"
          class="toolbar-actions app-table-shell__actions"
        >
          <slot name="actions" />
        </div>
      </div>

      <slot />

      <div v-if="$slots.footer" class="app-table-shell__footer">
        <slot name="footer" />
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
const props = defineProps<{
  eyebrow?: string;
  title?: string;
  description?: string;
}>();

const hasHeader = computed(() =>
  Boolean(
    props.eyebrow || props.title || props.description || useSlots().actions,
  ),
);
</script>

<style scoped>
.app-table-shell__content {
  display: grid;
  gap: 16px;
}

.app-table-shell__header {
  margin-bottom: 0;
}

.app-table-shell__copy {
  min-width: 0;
}

.app-table-shell__description {
  margin: 0;
  color: var(--gym-text-secondary);
  font-size: 0.92rem;
}

.app-table-shell__actions {
  justify-content: flex-end;
}

.app-table-shell__footer {
  border-top: 1px solid var(--gym-border);
  padding-top: 16px;
}
</style>
