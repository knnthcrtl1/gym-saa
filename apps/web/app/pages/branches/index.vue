<template>
  <div class="app-page">
    <PageHeader
      eyebrow="Locations"
      title="Branches"
      description="Manage gym locations, tenant assignment, and contact information from one standardized workspace."
    >
      <template #actions>
        <span class="surface-pill">
          <Icon name="lucide:map-pinned" size="16" />
          {{ pagination.total }} total
        </span>
        <AppButton
          v-if="canManageBranches"
          tone="primary"
          :loading="loading"
          @click="openCreate"
        >
          <Icon name="lucide:plus" size="18" class="mr-2" />
          Add branch
        </AppButton>
      </template>
    </PageHeader>

    <TableShell
      eyebrow="Branch registry"
      title="Active and archived locations"
      description="Review branch ownership, contact channels, and clean up obsolete locations with bulk delete."
    >
      <template #notice>
        <v-alert v-if="errorMessage" type="error" variant="tonal">
          {{ errorMessage }}
        </v-alert>
      </template>

      <template #actions>
        <div class="toolbar-cluster toolbar-cluster--end">
          <span v-if="canManageBranches" class="surface-pill">
            {{ selectedIds.length }} selected
          </span>
          <AppButton
            v-if="canManageBranches && selectedIds.length"
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
              <th v-if="canManageBranches" class="table-checkbox-cell">
                <v-checkbox-btn
                  :model-value="allVisibleSelected"
                  :indeterminate="someVisibleSelected"
                  @update:model-value="toggleSelectAll"
                />
              </th>
              <th>Name</th>
              <th>Tenant</th>
              <th>Code</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Status</th>
              <th v-if="rowActions.length" class="text-right" />
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td :colspan="columnCount" class="text-center py-6">
                Loading branches...
              </td>
            </tr>

            <tr v-else-if="branches.length === 0">
              <td :colspan="columnCount" class="text-center py-10">
                <div class="empty-state">
                  <div class="panel-label mb-2">No branches found</div>
                  Create the first branch so tenant staff can be assigned
                  properly.
                </div>
              </td>
            </tr>

            <tr v-for="branch in branches" :key="branch.id">
              <td v-if="canManageBranches" class="table-checkbox-cell">
                <v-checkbox-btn
                  :model-value="selectedIds.includes(branch.id)"
                  @update:model-value="toggleSelected(branch.id, $event)"
                />
              </td>
              <td>
                <div class="table-primary-cell">
                  <div class="surface-avatar surface-avatar--sm">
                    {{ branchInitials(branch) }}
                  </div>
                  <div>
                    <div class="table-primary-cell__title">
                      {{ branch.name }}
                    </div>
                    <div class="table-primary-cell__subtitle">
                      {{ branch.address || "No recorded address" }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="table-cell-muted">Tenant #{{ branch.tenant_id }}</td>
              <td class="table-cell-muted">{{ branch.code || "-" }}</td>
              <td class="table-cell-muted">{{ branch.email || "-" }}</td>
              <td class="table-cell-muted">{{ branch.phone || "-" }}</td>
              <td>
                <AppStatusTag :label="branch.status" />
              </td>
              <td v-if="rowActions.length" class="text-right">
                <AppRowActions
                  :items="rowActions"
                  @select="handleRowAction($event, branch)"
                />
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>

      <template #footer>
        <div class="branches-pagination">
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

    <BranchFormDialog
      v-model="dialogOpen"
      :branch="selectedBranch"
      @saved="handleSaved"
      @deleted="handleDeleted"
    />

    <AppConfirmDialog
      v-model="confirmDeleteOpen"
      title="Delete branch"
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
import { useBranches } from "../../../composables/useBranches";
import type { Branch } from "../../../types/api";
import PageHeader from "../../components/admin/PageHeader.vue";
import TableShell from "../../components/admin/TableShell.vue";
import BranchFormDialog from "../../components/branches/BranchFormDialog.vue";
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
  permission: "branches.view",
});

const { hasPermission } = useAuthorization();
const { list, remove } = useBranches();

const loading = ref(false);
const deleteLoading = ref(false);
const branches = ref<Branch[]>([]);
const selectedBranch = ref<Branch | null>(null);
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

const canManageBranches = computed(() => hasPermission("branches.manage"));

const rowActions = computed<AppRowActionItem[]>(() => {
  if (!canManageBranches.value) {
    return [];
  }

  return [
    {
      key: "edit",
      label: "Edit branch",
      icon: "lucide:square-pen",
    },
    {
      key: "delete",
      label: "Delete branch",
      icon: "lucide:trash-2",
      tone: "danger",
    },
  ];
});

const columnCount = computed(() => {
  let count = 6;

  if (canManageBranches.value) {
    count += 1;
  }

  if (rowActions.value.length) {
    count += 1;
  }

  return count;
});

const loadBranches = async (page = pagination.current_page) => {
  loading.value = true;
  errorMessage.value = "";

  try {
    const response = await list({ page, per_page: 10 });

    branches.value = response.data;
    pagination.total = response.total;
    pagination.current_page = response.current_page;
    pagination.last_page = response.last_page;
    pagination.from = response.from;
    pagination.to = response.to;
    selectedIds.value = selectedIds.value.filter((id) =>
      response.data.some((branch) => branch.id === id),
    );
  } catch (error) {
    const typedError = error as ApiPageError;

    errorMessage.value = typedError.data?.message ?? "Unable to load branches.";
  } finally {
    loading.value = false;
  }
};

const openCreate = () => {
  selectedBranch.value = null;
  dialogOpen.value = true;
};

const openEdit = (branch: Branch) => {
  selectedBranch.value = branch;
  dialogOpen.value = true;
};

const handleSaved = async () => {
  await loadBranches();
};

const handleDeleted = async () => {
  await loadBranches(resolveReloadPage(1));
};

const promptDelete = (branchesToDelete: Branch[]) => {
  confirmDeleteIds.value = branchesToDelete.map((branch) => branch.id);
  confirmDeleteMessage.value =
    branchesToDelete.length === 1
      ? `Delete ${branchesToDelete[0]?.name}? This action cannot be undone.`
      : `Delete ${branchesToDelete.length} selected branches? This action cannot be undone.`;
  confirmDeleteOpen.value = true;
};

const promptBulkDelete = () => {
  const branchesToDelete = branches.value.filter((branch) =>
    selectedIds.value.includes(branch.id),
  );

  if (!branchesToDelete.length) {
    return;
  }

  promptDelete(branchesToDelete);
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
    await loadBranches(resolveReloadPage(confirmDeleteIds.value.length));
  } catch (error) {
    const typedError = error as ApiPageError;

    errorMessage.value = typedError.data?.message ?? "Unable to delete branch.";
  } finally {
    deleteLoading.value = false;
    confirmDeleteIds.value = [];
  }
};

const resolveReloadPage = (removedCount: number) => {
  const visibleCount = branches.value.length;
  const nextPage =
    visibleCount > 0 &&
    removedCount >= visibleCount &&
    pagination.current_page > 1
      ? pagination.current_page - 1
      : pagination.current_page;

  return Math.max(nextPage, 1);
};

const toggleSelected = (branchId: number, checked: unknown) => {
  if (checked) {
    if (!selectedIds.value.includes(branchId)) {
      selectedIds.value = [...selectedIds.value, branchId];
    }

    return;
  }

  selectedIds.value = selectedIds.value.filter((id) => id !== branchId);
};

const toggleSelectAll = (checked: unknown) => {
  if (checked) {
    selectedIds.value = branches.value.map((branch) => branch.id);
    return;
  }

  selectedIds.value = [];
};

const handleRowAction = (action: string, branch: Branch) => {
  if (action === "edit") {
    openEdit(branch);
    return;
  }

  if (action === "delete") {
    promptDelete([branch]);
  }
};

const changePage = async (page: number) => {
  if (page < 1 || page > pagination.last_page || loading.value) {
    return;
  }

  await loadBranches(page);
};

const reloadCurrentPage = async () => {
  await loadBranches();
};

const branchInitials = (branch: Branch) => {
  const words = branch.name.split(/\s+/).filter(Boolean);

  return words
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join("");
};

const allVisibleSelected = computed(
  () =>
    branches.value.length > 0 &&
    branches.value.every((branch) => selectedIds.value.includes(branch.id)),
);

const someVisibleSelected = computed(
  () => selectedIds.value.length > 0 && !allVisibleSelected.value,
);

await loadBranches();
</script>

<style scoped>
.branches-pagination {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
  flex-wrap: wrap;
}
</style>
