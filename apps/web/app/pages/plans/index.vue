<template>
  <div class="app-page">
    <PageHeader
      eyebrow="Commercial offers"
      title="Membership plans"
      description="Manage pricing, duration, and availability using the same CRUD workspace pattern as members."
    >
      <template #actions>
        <span class="surface-pill">
          <Icon name="lucide:badge-dollar-sign" size="16" />
          {{ pagination.total }} total
        </span>
        <v-select
          v-model="statusFilter"
          :items="statusOptions"
          density="compact"
          variant="outlined"
          hide-details
          item-title="label"
          item-value="value"
          class="plans-filter"
          prepend-inner-icon="mdi-tune-variant"
        />
        <AppButton tone="primary" :loading="loading" @click="openCreate">
          <Icon name="lucide:plus" size="18" class="mr-2" />
          Add plan
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
              <div class="panel-label">Session plans</div>
              <div class="stat-card__value">{{ sessionPlanCount }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card class="content-panel">
            <v-card-text>
              <div class="panel-label">Branch scoped</div>
              <div class="stat-card__value">{{ branchScopedCount }}</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <TableShell
      eyebrow="Plan library"
      title="Offers ready for sale"
      description="Edit or remove plans from the row action menu, then page through the full catalog."
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
              <th>Name</th>
              <th>Duration</th>
              <th>Price</th>
              <th>Sessions</th>
              <th>Status</th>
              <th class="text-right" />
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center py-6">Loading plans...</td>
            </tr>

            <tr v-else-if="plans.length === 0">
              <td colspan="7" class="text-center py-10">
                <div class="empty-state">
                  <div class="panel-label mb-2">No results</div>
                  No membership plans matched the current filter.
                </div>
              </td>
            </tr>

            <tr v-for="plan in plans" :key="plan.id">
              <td class="table-checkbox-cell">
                <v-checkbox-btn
                  :model-value="selectedIds.includes(plan.id)"
                  @update:model-value="toggleSelected(plan.id, $event)"
                />
              </td>
              <td>
                <div class="table-primary-cell">
                  <div class="surface-avatar surface-avatar--sm">
                    {{ planInitials(plan) }}
                  </div>
                  <div>
                    <div class="table-primary-cell__title">{{ plan.name }}</div>
                    <div class="table-primary-cell__subtitle">
                      {{ plan.description || "No internal description" }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="table-cell-muted">{{ durationLabel(plan) }}</td>
              <td class="table-cell-muted">{{ formatCurrency(plan.price) }}</td>
              <td class="table-cell-muted">
                {{ plan.session_limit ?? "Unlimited" }}
              </td>
              <td>
                <AppStatusTag :label="plan.status" />
              </td>
              <td class="text-right">
                <AppRowActions
                  :items="rowActions"
                  @select="handleRowAction($event, plan)"
                />
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>

      <template #footer>
        <div class="plans-pagination">
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

    <PlanFormDialog
      v-model="dialogOpen"
      :plan="selectedPlan"
      @saved="handleSaved"
      @deleted="handleDeleted"
    />

    <AppConfirmDialog
      v-model="confirmDeleteOpen"
      title="Delete plan"
      :message="confirmDeleteMessage"
      confirm-text="Delete"
      tone="danger"
      :loading="deleteLoading"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup lang="ts">
import PageHeader from "../../components/admin/PageHeader.vue";
import TableShell from "../../components/admin/TableShell.vue";
import PlanFormDialog from "../../components/plans/PlanFormDialog.vue";
import AppButton from "../../components/ui/AppButton.vue";
import AppConfirmDialog from "../../components/ui/AppConfirmDialog.vue";
import AppRowActions, {
  type AppRowActionItem,
} from "../../components/ui/AppRowActions.vue";
import AppStatusTag from "../../components/ui/AppStatusTag.vue";
import type { MembershipPlan } from "../../../types/api";
import type { PlanPayload } from "../../../composables/usePlans";

type ApiPageError = {
  data?: {
    message?: string;
  };
};

definePageMeta({
  middleware: ["auth", "can"],
  permission: "plans.view",
});

const { list, remove } = usePlans();

const loading = ref(false);
const deleteLoading = ref(false);
const plans = ref<MembershipPlan[]>([]);
const selectedPlan = ref<MembershipPlan | null>(null);
const selectedIds = ref<number[]>([]);
const statusFilter = ref<PlanPayload["status"] | "all">("all");
const dialogOpen = ref(false);
const confirmDeleteOpen = ref(false);
const errorMessage = ref("");
const confirmDeleteIds = ref<number[]>([]);
const confirmDeleteMessage = ref("This action cannot be undone.");
const pagination = reactive({
  total: 0,
  current_page: 1,
  last_page: 1,
  from: 0 as number | null,
  to: 0 as number | null,
});

const rowActions: AppRowActionItem[] = [
  {
    key: "edit",
    label: "Edit plan",
    icon: "lucide:square-pen",
  },
  {
    key: "delete",
    label: "Delete plan",
    icon: "lucide:trash-2",
    tone: "danger",
  },
];

const statusOptions = [
  { label: "All statuses", value: "all" },
  { label: "Active", value: "active" },
  { label: "Inactive", value: "inactive" },
] as const;

const loadPlans = async (page = pagination.current_page) => {
  loading.value = true;
  errorMessage.value = "";

  try {
    const response = await list({
      page,
      per_page: 10,
      status: statusFilter.value === "all" ? undefined : statusFilter.value,
    });

    plans.value = response.data;
    pagination.total = response.total;
    pagination.current_page = response.current_page;
    pagination.last_page = response.last_page;
    pagination.from = response.from;
    pagination.to = response.to;
    selectedIds.value = selectedIds.value.filter((id) =>
      response.data.some((plan) => plan.id === id),
    );
  } catch (error) {
    const typedError = error as ApiPageError;

    errorMessage.value = typedError.data?.message ?? "Unable to load plans.";
  } finally {
    loading.value = false;
  }
};

const openCreate = () => {
  selectedPlan.value = null;
  dialogOpen.value = true;
};

const openEdit = (plan: MembershipPlan) => {
  selectedPlan.value = plan;
  dialogOpen.value = true;
};

const handleSaved = async () => {
  await loadPlans();
};

const handleDeleted = async () => {
  await loadPlans(resolveReloadPage(1));
};

const promptDelete = (plansToDelete: MembershipPlan[]) => {
  confirmDeleteIds.value = plansToDelete.map((plan) => plan.id);
  confirmDeleteMessage.value =
    plansToDelete.length === 1
      ? `Delete ${plansToDelete[0]?.name}? This action cannot be undone.`
      : `Delete ${plansToDelete.length} selected plans? This action cannot be undone.`;
  confirmDeleteOpen.value = true;
};

const promptBulkDelete = () => {
  const plansToDelete = plans.value.filter((plan) =>
    selectedIds.value.includes(plan.id),
  );

  if (!plansToDelete.length) {
    return;
  }

  promptDelete(plansToDelete);
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
    await loadPlans(resolveReloadPage(confirmDeleteIds.value.length));
  } catch (error) {
    const typedError = error as ApiPageError;

    errorMessage.value = typedError.data?.message ?? "Unable to delete plan.";
  } finally {
    deleteLoading.value = false;
    confirmDeleteIds.value = [];
  }
};

const resolveReloadPage = (removedCount: number) => {
  const visibleCount = plans.value.length;
  const nextPage =
    visibleCount > 0 &&
    removedCount >= visibleCount &&
    pagination.current_page > 1
      ? pagination.current_page - 1
      : pagination.current_page;

  return Math.max(nextPage, 1);
};

const handleRowAction = (action: string, plan: MembershipPlan) => {
  if (action === "edit") {
    openEdit(plan);
    return;
  }

  if (action === "delete") {
    promptDelete([plan]);
  }
};

const toggleSelected = (planId: number, checked: unknown) => {
  if (checked) {
    if (!selectedIds.value.includes(planId)) {
      selectedIds.value = [...selectedIds.value, planId];
    }

    return;
  }

  selectedIds.value = selectedIds.value.filter((id) => id !== planId);
};

const toggleSelectAll = (checked: unknown) => {
  if (checked) {
    selectedIds.value = plans.value.map((plan) => plan.id);
    return;
  }

  selectedIds.value = [];
};

const changePage = async (page: number) => {
  if (page < 1 || page > pagination.last_page || loading.value) {
    return;
  }

  await loadPlans(page);
};

const reloadCurrentPage = async () => {
  await loadPlans();
};

const planInitials = (plan: MembershipPlan) => {
  const words = plan.name.split(/\s+/).filter(Boolean);

  return words
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join("");
};

const durationLabel = (plan: MembershipPlan) =>
  `${plan.duration_value} ${plan.duration_type}`;

const formatCurrency = (value: string | number) =>
  new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    maximumFractionDigits: 2,
  }).format(Number(value));

await loadPlans();

const activeCount = computed(
  () => plans.value.filter((plan) => plan.status === "active").length,
);

const sessionPlanCount = computed(
  () => plans.value.filter((plan) => plan.duration_type === "session").length,
);

const branchScopedCount = computed(
  () => plans.value.filter((plan) => plan.branch_id !== null).length,
);

const allVisibleSelected = computed(
  () =>
    plans.value.length > 0 &&
    plans.value.every((plan) => selectedIds.value.includes(plan.id)),
);

const someVisibleSelected = computed(
  () => selectedIds.value.length > 0 && !allVisibleSelected.value,
);

watch(statusFilter, () => {
  loadPlans(1);
});
</script>

<style scoped>
.plans-filter {
  min-width: 180px;
}

.plans-pagination {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
  flex-wrap: wrap;
}

@media (max-width: 959px) {
  .plans-filter {
    width: 100%;
  }
}
</style>
