<template>
  <div class="app-page">
    <PageHeader
      eyebrow="Multi-gym accounts"
      title="Tenants"
      description="Super-admin workspace for gym accounts, status tracking, and account cleanup."
    >
      <template #actions>
        <span class="surface-pill">
          <Icon name="lucide:building-2" size="16" />
          {{ pagination.total }} total
        </span>
        <AppButton
          v-if="canManageTenants"
          tone="primary"
          :loading="loading"
          @click="openCreate"
        >
          <Icon name="lucide:plus" size="18" class="mr-2" />
          Add tenant
        </AppButton>
      </template>
    </PageHeader>

    <TableShell
      eyebrow="Account registry"
      title="Platform gym accounts"
      description="Review tenant details, manage status, and bulk-delete unused accounts when needed."
    >
      <template #notice>
        <v-alert v-if="errorMessage" type="error" variant="tonal">
          {{ errorMessage }}
        </v-alert>
      </template>

      <template #actions>
        <div class="toolbar-cluster toolbar-cluster--end">
          <span v-if="canManageTenants" class="surface-pill">
            {{ selectedIds.length }} selected
          </span>
          <AppButton
            v-if="canManageTenants && selectedIds.length"
            tone="danger"
            appearance="outline"
            :loading="deleteLoading"
            @click="promptBulkDelete"
          >
            <Icon name="lucide:trash-2" size="16" class="mr-2" />
            Delete selected
          </AppButton>
          <AppButton
            tone="neutral"
            appearance="outline"
            :loading="loading"
            @click="reloadCurrentPage"
          >
            Refresh
          </AppButton>
        </div>
      </template>

      <div class="table-scroll">
        <v-table>
          <thead>
            <tr>
              <th v-if="canManageTenants" class="table-checkbox-cell">
                <v-checkbox-btn
                  :model-value="allVisibleSelected"
                  :indeterminate="someVisibleSelected"
                  @update:model-value="toggleSelectAll"
                />
              </th>
              <th>Name</th>
              <th>Slug</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Status</th>
              <th v-if="rowActions.length" class="text-right" />
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td :colspan="columnCount" class="text-center py-6">
                Loading tenants...
              </td>
            </tr>

            <tr v-else-if="tenants.length === 0">
              <td :colspan="columnCount" class="text-center py-10">
                <div class="empty-state">
                  <div class="panel-label mb-2">No tenants found</div>
                  Create the first tenant account to onboard a gym.
                </div>
              </td>
            </tr>

            <tr v-for="tenant in tenants" :key="tenant.id">
              <td v-if="canManageTenants" class="table-checkbox-cell">
                <v-checkbox-btn
                  :model-value="selectedIds.includes(tenant.id)"
                  @update:model-value="toggleSelected(tenant.id, $event)"
                />
              </td>
              <td>
                <div class="table-primary-cell">
                  <div class="surface-avatar surface-avatar--sm">
                    {{ tenantInitials(tenant) }}
                  </div>
                  <div>
                    <div class="table-primary-cell__title">
                      {{ tenant.name }}
                    </div>
                    <div class="table-primary-cell__subtitle">
                      {{ tenant.address || "No recorded address" }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="table-cell-muted">{{ tenant.slug }}</td>
              <td class="table-cell-muted">{{ tenant.email || "-" }}</td>
              <td class="table-cell-muted">{{ tenant.phone || "-" }}</td>
              <td>
                <AppStatusTag :label="tenant.status" />
              </td>
              <td v-if="rowActions.length" class="text-right">
                <AppRowActions
                  :items="rowActions"
                  @select="handleRowAction($event, tenant)"
                />
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>

      <template #footer>
        <div class="tenants-pagination">
          <div class="table-meta">
            <span>
              Showing
              <strong
                >{{ pagination.from || 0 }}-{{ pagination.to || 0 }}</strong
              >
              of <strong>{{ pagination.total }}</strong>
            </span>
            <span>
              Page <strong>{{ pagination.current_page }}</strong> of
              <strong>{{ pagination.last_page }}</strong>
            </span>
          </div>

          <div class="toolbar-cluster toolbar-cluster--end">
            <AppButton
              tone="neutral"
              appearance="outline"
              :disabled="pagination.current_page <= 1 || loading"
              @click="changePage(pagination.current_page - 1)"
            >
              Previous
            </AppButton>
            <AppButton
              tone="primary"
              appearance="outline"
              :disabled="
                pagination.current_page >= pagination.last_page || loading
              "
              @click="changePage(pagination.current_page + 1)"
            >
              Next
            </AppButton>
          </div>
        </div>
      </template>
    </TableShell>

    <TenantFormDialog
      v-model="dialogOpen"
      :tenant="selectedTenant"
      @saved="handleSaved"
      @deleted="handleDeleted"
    />

    <AppConfirmDialog
      v-model="confirmDeleteOpen"
      title="Delete tenant"
      :message="confirmDeleteMessage"
      confirm-text="Delete"
      tone="danger"
      :loading="deleteLoading"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup lang="ts">
import { useAuthorization } from "../../../composables/useAuthorization";
import { useTenants } from "../../../composables/useTenants";
import type { Tenant } from "../../../types/api";
import PageHeader from "../../components/admin/PageHeader.vue";
import TableShell from "../../components/admin/TableShell.vue";
import TenantFormDialog from "../../components/tenants/TenantFormDialog.vue";
import AppButton from "../../components/ui/AppButton.vue";
import AppConfirmDialog from "../../components/ui/AppConfirmDialog.vue";
import AppRowActions, {
  type AppRowActionItem,
} from "../../components/ui/AppRowActions.vue";
import AppStatusTag from "../../components/ui/AppStatusTag.vue";

type ApiPageError = {
  data?: {
    message?: string;
  };
};

definePageMeta({
  middleware: ["auth", "can"],
  permission: "tenants.view",
});

const { hasPermission } = useAuthorization();
const { list, remove } = useTenants();

const loading = ref(false);
const deleteLoading = ref(false);
const tenants = ref<Tenant[]>([]);
const selectedTenant = ref<Tenant | null>(null);
const dialogOpen = ref(false);
const errorMessage = ref("");
const selectedIds = ref<number[]>([]);
const confirmDeleteOpen = ref(false);
const confirmDeleteIds = ref<number[]>([]);
const confirmDeleteMessage = ref("This action cannot be undone.");
const pagination = reactive({
  total: 0,
  current_page: 1,
  last_page: 1,
  from: 0 as number | null,
  to: 0 as number | null,
});

const canManageTenants = computed(() => hasPermission("tenants.manage"));

const rowActions = computed<AppRowActionItem[]>(() => {
  if (!canManageTenants.value) {
    return [];
  }

  return [
    {
      key: "edit",
      label: "Edit tenant",
      icon: "lucide:square-pen",
    },
    {
      key: "delete",
      label: "Delete tenant",
      icon: "lucide:trash-2",
      tone: "danger",
    },
  ];
});

const columnCount = computed(() => {
  let count = 5;

  if (canManageTenants.value) {
    count += 1;
  }

  if (rowActions.value.length) {
    count += 1;
  }

  return count;
});

const loadTenants = async (page = pagination.current_page) => {
  loading.value = true;
  errorMessage.value = "";

  try {
    const response = await list({ page, per_page: 10 });

    tenants.value = response.data;
    pagination.total = response.total;
    pagination.current_page = response.current_page;
    pagination.last_page = response.last_page;
    pagination.from = response.from;
    pagination.to = response.to;
    selectedIds.value = selectedIds.value.filter((id) =>
      response.data.some((tenant) => tenant.id === id),
    );
  } catch (error) {
    const typedError = error as ApiPageError;

    errorMessage.value = typedError.data?.message ?? "Unable to load tenants.";
  } finally {
    loading.value = false;
  }
};

const openCreate = () => {
  selectedTenant.value = null;
  dialogOpen.value = true;
};

const openEdit = (tenant: Tenant) => {
  selectedTenant.value = tenant;
  dialogOpen.value = true;
};

const handleSaved = async () => {
  await loadTenants();
};

const handleDeleted = async () => {
  await loadTenants(resolveReloadPage(1));
};

const promptDelete = (tenantsToDelete: Tenant[]) => {
  confirmDeleteIds.value = tenantsToDelete.map((tenant) => tenant.id);
  confirmDeleteMessage.value =
    tenantsToDelete.length === 1
      ? `Delete ${tenantsToDelete[0]?.name}? This action cannot be undone.`
      : `Delete ${tenantsToDelete.length} selected tenants? This action cannot be undone.`;
  confirmDeleteOpen.value = true;
};

const promptBulkDelete = () => {
  const tenantsToDelete = tenants.value.filter((tenant) =>
    selectedIds.value.includes(tenant.id),
  );

  if (!tenantsToDelete.length) {
    return;
  }

  promptDelete(tenantsToDelete);
};

const confirmDelete = async () => {
  if (!confirmDeleteIds.value.length) {
    confirmDeleteOpen.value = false;
    return;
  }

  deleteLoading.value = true;
  errorMessage.value = "";

  try {
    await Promise.all(confirmDeleteIds.value.map((id) => remove(id)));
    selectedIds.value = selectedIds.value.filter(
      (id) => !confirmDeleteIds.value.includes(id),
    );
    confirmDeleteOpen.value = false;
    await loadTenants(resolveReloadPage(confirmDeleteIds.value.length));
  } catch (error) {
    const typedError = error as ApiPageError;

    errorMessage.value = typedError.data?.message ?? "Unable to delete tenant.";
  } finally {
    deleteLoading.value = false;
    confirmDeleteIds.value = [];
  }
};

const resolveReloadPage = (removedCount: number) => {
  const visibleCount = tenants.value.length;
  const nextPage =
    visibleCount > 0 &&
    removedCount >= visibleCount &&
    pagination.current_page > 1
      ? pagination.current_page - 1
      : pagination.current_page;

  return Math.max(nextPage, 1);
};

const toggleSelected = (tenantId: number, checked: unknown) => {
  if (checked) {
    if (!selectedIds.value.includes(tenantId)) {
      selectedIds.value = [...selectedIds.value, tenantId];
    }

    return;
  }

  selectedIds.value = selectedIds.value.filter((id) => id !== tenantId);
};

const toggleSelectAll = (checked: unknown) => {
  if (checked) {
    selectedIds.value = tenants.value.map((tenant) => tenant.id);
    return;
  }

  selectedIds.value = [];
};

const handleRowAction = (action: string, tenant: Tenant) => {
  if (action === "edit") {
    openEdit(tenant);
    return;
  }

  if (action === "delete") {
    promptDelete([tenant]);
  }
};

const changePage = async (page: number) => {
  if (page < 1 || page > pagination.last_page || loading.value) {
    return;
  }

  await loadTenants(page);
};

const reloadCurrentPage = async () => {
  await loadTenants();
};

const tenantInitials = (tenant: Tenant) => {
  const words = tenant.name.split(/\s+/).filter(Boolean);

  return words
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join("");
};

const allVisibleSelected = computed(
  () =>
    tenants.value.length > 0 &&
    tenants.value.every((tenant) => selectedIds.value.includes(tenant.id)),
);

const someVisibleSelected = computed(
  () => selectedIds.value.length > 0 && !allVisibleSelected.value,
);

await loadTenants();
</script>

<style scoped>
.tenants-pagination {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
  flex-wrap: wrap;
}
</style>
