<template>
  <v-menu location="bottom end">
    <template #activator="{ props: menuProps }">
      <v-btn
        v-bind="menuProps"
        icon
        size="small"
        variant="text"
        class="app-row-actions__trigger"
      >
        <Icon name="lucide:ellipsis" size="18" />
      </v-btn>
    </template>

    <v-list density="compact" class="app-row-actions__menu">
      <v-list-item
        v-for="item in items"
        :key="item.key"
        :disabled="item.disabled"
        :class="item.tone === 'danger' ? 'text-error' : undefined"
        @click="emit('select', item.key)"
      >
        <template v-if="item.icon" #prepend>
          <Icon :name="item.icon" size="16" />
        </template>

        <v-list-item-title>{{ item.label }}</v-list-item-title>
      </v-list-item>
    </v-list>
  </v-menu>
</template>

<script setup lang="ts">
export type AppRowActionKey = string;

export type AppRowActionItem = {
  key: AppRowActionKey;
  label: string;
  icon?: string;
  tone?: "default" | "danger";
  disabled?: boolean;
};

defineProps<{
  items: AppRowActionItem[];
}>();

const emit = defineEmits<{
  select: [key: AppRowActionKey];
}>();
</script>

<style scoped>
.app-row-actions__trigger {
  color: var(--gym-text-secondary);
}

.app-row-actions__menu {
  min-width: 180px;
}
</style>
