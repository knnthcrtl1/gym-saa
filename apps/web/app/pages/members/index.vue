<template>
  <div class="app-page">
    <PageHeader
      eyebrow="Member records"
      title="All members"
      description="Manage member profiles, status, and actions from one standard workspace."
    >
      <template #actions>
        <span class="surface-pill">
          <Icon name="lucide:users" size="16" />
          {{ pagination.total }} total
        </span>
        <v-text-field
          v-model="searchInput"
          density="compact"
          variant="outlined"
          hide-details
          placeholder="Search name, code, or email"
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
          prepend-inner-icon="mdi-tune-variant"
        />
        <AppButton
          v-if="canManageMembers"
          tone="primary"
          :loading="loading"
          @click="openCreate"
        >
          <Icon name="lucide:plus" size="18" class="mr-2" />
          Add member
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
              <div class="panel-label">Blocked</div>
              <div class="stat-card__value">{{ blockedCount }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card class="content-panel">
            <v-card-text>
              <div class="panel-label">With email</div>
              <div class="stat-card__value">{{ contactableCount }}</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <TableShell
      eyebrow="Roster table"
      title="Current member list"
      description="Three-dot row actions, checkbox multi-select, and previous or next pagination are standardized here."
    >
      <template #notice>
        <v-alert v-if="errorMessage" type="error" variant="tonal">
          {{ errorMessage }}
        </v-alert>
      </template>

      <template #actions>
        <div class="toolbar-cluster toolbar-cluster--end">
          <span v-if="canManageMembers" class="surface-pill">
            {{ selectedIds.length }} selected
          </span>
          <AppButton
            v-if="canManageMembers && selectedIds.length"
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
              <th v-if="canManageMembers" class="table-checkbox-cell">
                <v-checkbox-btn
                  :model-value="allVisibleSelected"
                  :indeterminate="someVisibleSelected"
                  @update:model-value="toggleSelectAll"
                />
              </th>
              <th>
                <button
                  type="button"
                  class="table-sort-button"
                  :class="sortButtonClass('member_code')"
                  @click="toggleSort('member_code')"
                >
                  Member code
                  <Icon
                    name="lucide:arrow-up"
                    size="14"
                    class="table-sort-button__icon"
                  />
                </button>
              </th>
              <th>
                <button
                  type="button"
                  class="table-sort-button"
                  :class="sortButtonClass('name')"
                  @click="toggleSort('name')"
                >
                  Member
                  <Icon
                    name="lucide:arrow-up"
                    size="14"
                    class="table-sort-button__icon"
                  />
                </button>
              </th>
              <th>Contact</th>
              <th>
                <button
                  type="button"
                  class="table-sort-button"
                  :class="sortButtonClass('status')"
                  @click="toggleSort('status')"
                >
                  Status
                  <Icon
                    name="lucide:arrow-up"
                    size="14"
                    class="table-sort-button__icon"
                  />
                </button>
              </th>
              <th>
                <button
                  type="button"
                  class="table-sort-button"
                  :class="sortButtonClass('created_at')"
                  @click="toggleSort('created_at')"
                >
                  Date added
                  <Icon
                    name="lucide:arrow-up"
                    size="14"
                    class="table-sort-button__icon"
                  />
                </button>
              </th>
              <th v-if="rowActions.length" class="text-right" />
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td
                :colspan="
                  rowActions.length
                    ? canManageMembers
                      ? 7
                      : 6
                    : canManageMembers
                      ? 6
                      : 5
                "
                class="text-center py-6"
              >
                Loading members...
              </td>
            </tr>

            <tr v-else-if="members.length === 0">
              <td
                :colspan="
                  rowActions.length
                    ? canManageMembers
                      ? 7
                      : 6
                    : canManageMembers
                      ? 6
                      : 5
                "
                class="text-center py-10"
              >
                <div class="empty-state">
                  <div class="panel-label mb-2">No results</div>
                  No members matched the current search and filters.
                </div>
              </td>
            </tr>

            <tr v-for="member in members" :key="member.id">
              <td v-if="canManageMembers" class="table-checkbox-cell">
                <v-checkbox-btn
                  :model-value="selectedIds.includes(member.id)"
                  @update:model-value="toggleSelected(member.id, $event)"
                />
              </td>
              <td>
                <div class="table-primary-cell__title">
                  {{ member.member_code }}
                </div>
                <div class="table-primary-cell__subtitle">
                  Branch #{{ member.branch_id }}
                </div>
              </td>
              <td>
                <div class="table-primary-cell">
                  <div class="surface-avatar surface-avatar--sm">
                    {{ memberInitials(member) }}
                  </div>
                  <div>
                    <div class="table-primary-cell__title">
                      {{ member.first_name }} {{ member.last_name }}
                    </div>
                    <div class="table-primary-cell__subtitle">
                      {{ member.email || member.phone || "No direct contact" }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="table-cell-muted">
                <div>{{ member.email || "-" }}</div>
                <div>{{ member.phone || "-" }}</div>
              </td>
              <td>
                <AppStatusTag :label="member.status" />
              </td>
              <td class="table-cell-muted">
                {{ formatDate(member.created_at) }}
              </td>
              <td v-if="rowActions.length" class="text-right">
                <AppRowActions
                  :items="rowActions"
                  @select="handleRowAction($event, member)"
                />
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
              of
              <strong>{{ pagination.total }}</strong>
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

    <MemberFormDialog
      v-model="dialogOpen"
      :member="selectedMember"
      @saved="handleSaved"
      @deleted="handleDeleted"
    />

    <SubscriptionFormDialog
      v-model="subscriptionDialogOpen"
      :member="selectedSubscriptionMember"
      @saved="handleSubscriptionSaved"
    />

    <AppConfirmDialog
      v-model="confirmDeleteOpen"
      :title="confirmDeleteTitle"
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
import PageHeader from "../../components/admin/PageHeader.vue";
import TableShell from "../../components/admin/TableShell.vue";
import MemberFormDialog from "../../components/members/MemberFormDialog.vue";
import SubscriptionFormDialog from "../../components/subscriptions/SubscriptionFormDialog.vue";
import AppButton from "../../components/ui/AppButton.vue";
import AppConfirmDialog from "../../components/ui/AppConfirmDialog.vue";
import AppRowActions, {
  type AppRowActionItem,
} from "../../components/ui/AppRowActions.vue";
import AppStatusTag from "../../components/ui/AppStatusTag.vue";
import type { Member } from "../../../types/api";
import type {
  MemberListParams,
  MemberSortField,
} from "../../../composables/useMembers";

type ApiPageError = {
  data?: {
    message?: string;
  };
};

definePageMeta({
  middleware: ["auth", "can"],
  permission: "members.view",
});

const { hasPermission } = useAuthorization();
const { list, remove, bulkRemove } = useMembers();

const loading = ref(false);
const members = ref<Member[]>([]);
const searchInput = ref("");
const search = ref("");
const statusFilter = ref<MemberListParams["status"] | "all">("all");
const sortBy = ref<MemberSortField>("created_at");
const direction = ref<"asc" | "desc">("desc");
const dialogOpen = ref(false);
const subscriptionDialogOpen = ref(false);
const selectedMember = ref<Member | null>(null);
const selectedSubscriptionMember = ref<Member | null>(null);
const errorMessage = ref("");
const selectedIds = ref<number[]>([]);
const deleteLoading = ref(false);
const confirmDeleteOpen = ref(false);
const confirmDeleteTitle = ref("Delete member");
const confirmDeleteMessage = ref("This action cannot be undone.");
const confirmDeleteIds = ref<number[]>([]);
const pagination = reactive({
  total: 0,
  current_page: 1,
  last_page: 1,
  from: 0 as number | null,
  to: 0 as number | null,
});

const canManageMembers = computed(() => hasPermission("members.manage"));
const canManageSubscriptions = computed(() =>
  hasPermission("subscriptions.manage"),
);

const rowActions = computed<AppRowActionItem[]>(() => {
  const actions: AppRowActionItem[] = [];

  if (canManageSubscriptions.value) {
    actions.push({
      key: "subscription",
      label: "Create subscription",
      icon: "lucide:shield-plus",
    });
  }

  if (canManageMembers.value) {
    actions.push(
      {
        key: "edit",
        label: "Edit member",
        icon: "lucide:square-pen",
      },
      {
        key: "delete",
        label: "Delete member",
        icon: "lucide:trash-2",
        tone: "danger",
      },
    );
  }

  return actions;
});

const statusOptions = [
  { label: "All statuses", value: "all" },
  { label: "Active", value: "active" },
  { label: "Inactive", value: "inactive" },
  { label: "Blocked", value: "blocked" },
] as const;

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const loadMembers = async (page = pagination.current_page) => {
  loading.value = true;
  errorMessage.value = "";

  try {
    const response = await list({
      page,
      per_page: 10,
      search: search.value || undefined,
      status: statusFilter.value === "all" ? undefined : statusFilter.value,
      sort_by: sortBy.value,
      direction: direction.value,
    });

    members.value = response.data;
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

    errorMessage.value = typedError.data?.message ?? "Unable to load members.";
  } finally {
    loading.value = false;
  }
};

const openCreate = () => {
  selectedMember.value = null;
  dialogOpen.value = true;
};

const openEdit = (member: Member) => {
  selectedMember.value = member;
  dialogOpen.value = true;
};

const openSubscriptionCreate = (member: Member) => {
  selectedSubscriptionMember.value = member;
  subscriptionDialogOpen.value = true;
};

const handleSaved = async () => {
  await loadMembers();
};

const handleDeleted = async () => {
  await loadMembers(resolveReloadPage(1));
};

const handleSubscriptionSaved = async () => {
  await loadMembers();
};

const changePage = async (page: number) => {
  if (page < 1 || page > pagination.last_page || loading.value) {
    return;
  }

  await loadMembers(page);
};

const reloadCurrentPage = async () => {
  await loadMembers();
};

const promptDelete = (membersToDelete: Member[]) => {
  confirmDeleteIds.value = membersToDelete.map((member) => member.id);
  confirmDeleteTitle.value =
    membersToDelete.length === 1 ? "Delete member" : "Delete selected members";
  confirmDeleteMessage.value =
    membersToDelete.length === 1
      ? `Delete ${membersToDelete[0]?.first_name} ${membersToDelete[0]?.last_name}? This action cannot be undone.`
      : `Delete ${membersToDelete.length} selected members? This action cannot be undone.`;
  confirmDeleteOpen.value = true;
};

const promptBulkDelete = () => {
  const membersToDelete = members.value.filter((member) =>
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
    if (confirmDeleteIds.value.length === 1) {
      const memberId = confirmDeleteIds.value[0];

      if (memberId === undefined) {
        confirmDeleteOpen.value = false;
        return;
      }

      await remove(memberId);
    } else {
      await bulkRemove(confirmDeleteIds.value);
    }

    selectedIds.value = selectedIds.value.filter(
      (id) => !confirmDeleteIds.value.includes(id),
    );
    confirmDeleteOpen.value = false;
    await loadMembers(resolveReloadPage(confirmDeleteIds.value.length));
  } catch (error) {
    const typedError = error as ApiPageError;

    errorMessage.value = typedError.data?.message ?? "Unable to delete member.";
  } finally {
    deleteLoading.value = false;
    confirmDeleteIds.value = [];
  }
};

const resolveReloadPage = (removedCount: number) => {
  const visibleCount = members.value.length;
  const nextPage =
    visibleCount > 0 &&
    removedCount >= visibleCount &&
    pagination.current_page > 1
      ? pagination.current_page - 1
      : pagination.current_page;

  return Math.max(nextPage, 1);
};

const toggleSort = async (field: MemberSortField) => {
  if (sortBy.value === field) {
    direction.value = direction.value === "asc" ? "desc" : "asc";
  } else {
    sortBy.value = field;
    direction.value = field === "name" ? "asc" : "desc";
  }

  await loadMembers(1);
};

const sortButtonClass = (field: MemberSortField) => ({
  "table-sort-button--active": sortBy.value === field,
  "table-sort-button--desc":
    sortBy.value === field && direction.value === "desc",
});

const toggleSelected = (memberId: number, checked: unknown) => {
  if (checked) {
    if (!selectedIds.value.includes(memberId)) {
      selectedIds.value = [...selectedIds.value, memberId];
    }

    return;
  }

  selectedIds.value = selectedIds.value.filter((id) => id !== memberId);
};

const toggleSelectAll = (checked: unknown) => {
  if (checked) {
    selectedIds.value = members.value.map((member) => member.id);
    return;
  }

  selectedIds.value = [];
};

const handleRowAction = (action: string, member: Member) => {
  if (action === "subscription") {
    openSubscriptionCreate(member);
    return;
  }

  if (action === "edit") {
    openEdit(member);
    return;
  }

  if (action === "delete") {
    promptDelete([member]);
  }
};

const memberInitials = (member: Member) =>
  `${member.first_name.charAt(0)}${member.last_name.charAt(0)}`.toUpperCase();

const formatDate = (value?: string | null) => {
  if (!value) {
    return "-";
  }

  return new Intl.DateTimeFormat("en", {
    month: "short",
    day: "numeric",
    year: "numeric",
  }).format(new Date(value));
};

await loadMembers();

const activeCount = computed(
  () => members.value.filter((member) => member.status === "active").length,
);

const blockedCount = computed(
  () => members.value.filter((member) => member.status === "blocked").length,
);

const contactableCount = computed(
  () => members.value.filter((member) => Boolean(member.email)).length,
);

const allVisibleSelected = computed(
  () =>
    members.value.length > 0 &&
    members.value.every((member) => selectedIds.value.includes(member.id)),
);

const someVisibleSelected = computed(
  () => selectedIds.value.length > 0 && !allVisibleSelected.value,
);

watch(searchInput, (value) => {
  if (searchTimer) {
    clearTimeout(searchTimer);
  }

  searchTimer = setTimeout(() => {
    search.value = value.trim();
    loadMembers(1);
  }, 300);
});

watch(statusFilter, () => {
  loadMembers(1);
});

onBeforeUnmount(() => {
  if (searchTimer) {
    clearTimeout(searchTimer);
  }
});
</script>

<style scoped>
.members-search {
  min-width: 240px;
}

.members-filter {
  min-width: 180px;
}

.members-pagination {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
  flex-wrap: wrap;
}

@media (max-width: 959px) {
  .members-search,
  .members-filter {
    width: 100%;
  }
}
</style>
