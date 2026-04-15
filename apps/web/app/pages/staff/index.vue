<template>
  <div class="app-page">
    <PageHeader
      eyebrow="Admin-managed access"
      title="Staff"
      description="Create and manage operator accounts, branch assignments, and per-module permissions."
    >
      <template #actions>
        <span class="surface-pill">
          <Icon name="lucide:shield-check" size="16" />
          {{ pagination.total }} total
        </span>
        <v-text-field
          v-model="searchInput"
          density="compact"
          variant="outlined"
          hide-details
          placeholder="Search staff name or email"
          class="members-search"
          prepend-inner-icon="mdi-magnify"
        />
        <v-select
          v-model="statusFilter"
          :items="statusOptions"
          density="compact"
          variant="outlined"
          hide-details
          item-title="label"
          item-value="value"
          class="members-filter"
        />
        <AppButton tone="primary" @click="openCreate">
          <Icon name="lucide:plus" size="18" class="mr-2" />
          Add staff account
        </AppButton>
      </template>
    </PageHeader>

    <div class="metric-grid">
      <v-row>
        <v-col cols="12" md="4">
          <v-card class="content-panel">
            <v-card-text>
              <div class="panel-label">Active</div>
              <div class="stat-card__value">{{ activeCount }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card class="content-panel">
            <v-card-text>
              <div class="panel-label">Inactive</div>
              <div class="stat-card__value">{{ inactiveCount }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card class="content-panel">
            <v-card-text>
              <div class="panel-label">Gym admins</div>
              <div class="stat-card__value">{{ adminCount }}</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <TableShell
      eyebrow="Team roster"
      title="Accounts and access"
      description="Use this table to review branch assignments, suspend access, or adjust permissions."
    >
      <template #notice>
        <v-alert v-if="errorMessage" type="error" variant="tonal">
          {{ errorMessage }}
        </v-alert>
      </template>

      <template #actions>
        <div class="toolbar-cluster toolbar-cluster--end">
          <span class="surface-pill"> {{ selectedIds.length }} selected </span>
          <AppButton
            v-if="selectedIds.length"
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
              <th class="table-checkbox-cell">
                <v-checkbox-btn
                  :model-value="allVisibleSelected"
                  :indeterminate="someVisibleSelected"
                  @update:model-value="toggleSelectAll"
                />
              </th>
              <th>Staff</th>
              <th>Role</th>
              <th>Branch</th>
              <th>Status</th>
              <th>Permissions</th>
              <th class="text-right">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center py-6">Loading staff...</td>
            </tr>

            <tr v-else-if="staff.length === 0">
              <td colspan="7" class="text-center py-10">
                <div class="empty-state">
                  <div class="panel-label mb-2">No staff found</div>
                  Create the first staff account to delegate gym operations.
                </div>
              </td>
            </tr>

            <tr v-for="member in staff" :key="member.id">
              <td class="table-checkbox-cell">
                <v-checkbox-btn
                  :model-value="selectedIds.includes(member.id)"
                  @update:model-value="toggleSelected(member.id, $event)"
                />
              </td>
              <td>
                <div class="table-primary-cell">
                  <div class="surface-avatar surface-avatar--sm">
                    {{ initials(member) }}
                  </div>
                  <div>
                    <div class="table-primary-cell__title">
                      {{ member.name }}
                    </div>
                    <div class="table-primary-cell__subtitle">
                      {{ member.email }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="table-cell-muted">
                {{
                  member.role === "gym_admin"
                    ? "Gym admin"
                    : formatRole(member.staff_role)
                }}
              </td>
              <td class="table-cell-muted">
                {{
                  member.branch?.name ||
                  (member.branch_id
                    ? `Branch #${member.branch_id}`
                    : "All branches")
                }}
              </td>
              <td>
                <AppStatusTag :label="member.status" />
              </td>
              <td class="table-cell-muted">
                {{ member.permissions.length }} modules
              </td>
              <td class="text-right">
                <div class="toolbar-cluster toolbar-cluster--end">
                  <AppButton
                    tone="primary"
                    appearance="outline"
                    @click="openEdit(member)"
                  >
                    Edit
                  </AppButton>
                  <AppButton
                    tone="danger"
                    appearance="outline"
                    @click="promptDelete([member])"
                  >
                    Delete
                  </AppButton>
                </div>
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>

      <template #footer>
        <div class="members-pagination">
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

    <StaffFormDialog
      v-model="dialogOpen"
      :staff="selectedStaff"
      @saved="handleSaved"
      @deleted="handleDeleted"
    />

    <AppConfirmDialog
      v-model="confirmDeleteOpen"
      title="Delete staff account"
      :message="confirmDeleteMessage"
      confirm-text="Delete"
      tone="danger"
      :loading="deleteLoading"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup lang="ts">
import { useStaff } from "../../../composables/useStaff";
import type { StaffUser } from "../../../types/api";
import type { StaffListParams } from "../../../composables/useStaff";
import PageHeader from "../../components/admin/PageHeader.vue";
import TableShell from "../../components/admin/TableShell.vue";
import StaffFormDialog from "../../components/staff/StaffFormDialog.vue";
import AppButton from "../../components/ui/AppButton.vue";
import AppConfirmDialog from "../../components/ui/AppConfirmDialog.vue";
import AppStatusTag from "../../components/ui/AppStatusTag.vue";

type ApiPageError = {
  data?: {
    message?: string;
  };
};

definePageMeta({
  middleware: ["auth", "can"],
  permission: "staff.view",
});

const { list, remove } = useStaff();

const loading = ref(false);
const deleteLoading = ref(false);
const staff = ref<StaffUser[]>([]);
const selectedStaff = ref<StaffUser | null>(null);
const dialogOpen = ref(false);
const errorMessage = ref("");
const selectedIds = ref<number[]>([]);
const searchInput = ref("");
const search = ref("");
const statusFilter = ref<StaffListParams["status"] | "all">("all");
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

const statusOptions = [
  { label: "All statuses", value: "all" },
  { label: "Active", value: "active" },
  { label: "Inactive", value: "inactive" },
] as const;

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const loadStaff = async (page = pagination.current_page) => {
  loading.value = true;
  errorMessage.value = "";

  try {
    const response = await list({
      page,
      per_page: 10,
      search: search.value || undefined,
      status: statusFilter.value === "all" ? undefined : statusFilter.value,
    });

    staff.value = response.data;
    pagination.total = response.total;
    pagination.current_page = response.current_page;
    pagination.last_page = response.last_page;
    pagination.from = response.from;
    pagination.to = response.to;
    selectedIds.value = selectedIds.value.filter((id) =>
      response.data.some((member) => member.id === id),
    );
  } catch (error) {
    const typedError = error as ApiPageError;

    errorMessage.value =
      typedError.data?.message ?? "Unable to load staff accounts.";
  } finally {
    loading.value = false;
  }
};

const initials = (member: StaffUser) =>
  member.name
    .split(" ")
    .map((part) => part[0])
    .join("")
    .toUpperCase()
    .slice(0, 2);

const formatRole = (value?: string | null) =>
  value ? value.replace(/_/g, " ") : "staff";

const activeCount = computed(
  () => staff.value.filter((item) => item.status === "active").length,
);
const inactiveCount = computed(
  () => staff.value.filter((item) => item.status === "inactive").length,
);
const adminCount = computed(
  () => staff.value.filter((item) => item.role === "gym_admin").length,
);

const allVisibleSelected = computed(
  () =>
    staff.value.length > 0 &&
    staff.value.every((member) => selectedIds.value.includes(member.id)),
);

const someVisibleSelected = computed(
  () => selectedIds.value.length > 0 && !allVisibleSelected.value,
);

const openCreate = () => {
  selectedStaff.value = null;
  dialogOpen.value = true;
};

const openEdit = (member: StaffUser) => {
  selectedStaff.value = member;
  dialogOpen.value = true;
};

const handleSaved = async () => {
  await loadStaff(selectedStaff.value ? pagination.current_page : 1);
};

const handleDeleted = async () => {
  await loadStaff(resolveReloadPage(1));
};

const promptDelete = (membersToDelete: StaffUser[]) => {
  confirmDeleteIds.value = membersToDelete.map((member) => member.id);
  confirmDeleteMessage.value =
    membersToDelete.length === 1
      ? `Delete ${membersToDelete[0]?.name}? This action cannot be undone.`
      : `Delete ${membersToDelete.length} selected staff accounts? This action cannot be undone.`;
  confirmDeleteOpen.value = true;
};

const promptBulkDelete = () => {
  const membersToDelete = staff.value.filter((member) =>
    selectedIds.value.includes(member.id),
  );

  if (!membersToDelete.length) {
    return;
  }

  promptDelete(membersToDelete);
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
    await loadStaff(resolveReloadPage(confirmDeleteIds.value.length));
  } catch (error) {
    const typedError = error as ApiPageError;
    errorMessage.value =
      typedError.data?.message ?? "Unable to delete staff account.";
  } finally {
    deleteLoading.value = false;
    confirmDeleteIds.value = [];
  }
};

const resolveReloadPage = (removedCount: number) => {
  const visibleCount = staff.value.length;
  const nextPage =
    visibleCount > 0 &&
    removedCount >= visibleCount &&
    pagination.current_page > 1
      ? pagination.current_page - 1
      : pagination.current_page;

  return Math.max(nextPage, 1);
};

const changePage = async (page: number) => {
  if (page < 1 || page > pagination.last_page || loading.value) {
    return;
  }

  await loadStaff(page);
};

const reloadCurrentPage = async () => {
  await loadStaff();
};

const toggleSelected = (staffId: number, checked: unknown) => {
  if (checked) {
    if (!selectedIds.value.includes(staffId)) {
      selectedIds.value = [...selectedIds.value, staffId];
    }

    return;
  }

  selectedIds.value = selectedIds.value.filter((id) => id !== staffId);
};

const toggleSelectAll = (checked: unknown) => {
  if (checked) {
    selectedIds.value = staff.value.map((member) => member.id);
    return;
  }

  selectedIds.value = [];
};

watch(searchInput, (value) => {
  if (searchTimer) {
    clearTimeout(searchTimer);
  }

  searchTimer = setTimeout(async () => {
    search.value = value.trim();
    await loadStaff(1);
  }, 250);
});

watch(statusFilter, async () => {
  await loadStaff(1);
});

await loadStaff();
</script>
