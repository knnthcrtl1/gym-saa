<template>
  <div class="app-page">
    <PageHeader
      eyebrow="Membership lifecycle"
      title="Subscriptions"
      description="Create, edit, and retire subscriptions with member and plan context already loaded into the workspace."
    >
      <template #actions>
        <span class="surface-pill">
          <Icon name="lucide:users-round" size="16" />
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
          class="subscriptions-filter"
          prepend-inner-icon="mdi-tune-variant"
        />
        <AppButton tone="primary" :loading="loading" @click="openCreate">
          <Icon name="lucide:plus" size="18" class="mr-2" />
          Add subscription
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
              <div class="panel-label">Paid</div>
              <div class="stat-card__value">{{ paidCount }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card class="content-panel">
            <v-card-text>
              <div class="panel-label">Ending soon</div>
              <div class="stat-card__value">{{ endingSoonCount }}</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <TableShell
      eyebrow="Subscription book"
      title="Current enrollment state"
      description="Use row actions for edits and delete confirmations, or paginate through the latest subscriptions."
    >
      <template #notice>
        <v-alert v-if="errorMessage" type="error" variant="tonal">
          {{ errorMessage }}
        </v-alert>
      </template>

      <template #actions>
        <div class="toolbar-cluster toolbar-cluster--end">
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
              <th>Member</th>
              <th>Plan</th>
              <th>Start</th>
              <th>End</th>
              <th>Amount</th>
              <th>Payment</th>
              <th>Status</th>
              <th class="text-right" />
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="text-center py-6">
                Loading subscriptions...
              </td>
            </tr>

            <tr v-else-if="subscriptions.length === 0">
              <td colspan="8" class="text-center py-10">
                <div class="empty-state">
                  <div class="panel-label mb-2">No results</div>
                  No subscriptions matched the current filter.
                </div>
              </td>
            </tr>

            <tr v-for="subscription in subscriptions" :key="subscription.id">
              <td>
                <div class="table-primary-cell">
                  <div class="surface-avatar surface-avatar--sm">
                    {{ memberInitials(subscription) }}
                  </div>
                  <div>
                    <div class="table-primary-cell__title">
                      {{ memberName(subscription) }}
                    </div>
                    <div class="table-primary-cell__subtitle">
                      Member #{{ subscription.member_id }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="table-cell-muted">
                {{
                  subscription.membership_plan?.name ||
                  `Plan #${subscription.membership_plan_id}`
                }}
              </td>
              <td class="table-cell-muted">
                {{ formatDate(subscription.start_date) }}
              </td>
              <td class="table-cell-muted">
                {{ formatDate(subscription.end_date) }}
              </td>
              <td class="table-cell-muted">
                {{ formatCurrency(subscription.amount) }}
              </td>
              <td>
                <AppStatusTag :label="subscription.payment_status" />
              </td>
              <td>
                <AppStatusTag :label="subscription.status" />
              </td>
              <td class="text-right">
                <AppRowActions
                  :items="rowActions"
                  @select="handleRowAction($event, subscription)"
                />
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>

      <template #footer>
        <div class="subscriptions-pagination">
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

    <SubscriptionFormDialog
      v-model="dialogOpen"
      :subscription="selectedSubscription"
      @saved="handleSaved"
      @deleted="handleDeleted"
    />

    <PaymentDialog
      v-model="paymentDialogOpen"
      :subscription="paymentSubscription"
      @saved="handleSaved"
    />

    <AppConfirmDialog
      v-model="confirmDeleteOpen"
      title="Delete subscription"
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
import PaymentDialog from "../../components/payments/PaymentDialog.vue";
import TableShell from "../../components/admin/TableShell.vue";
import SubscriptionFormDialog from "../../components/subscriptions/SubscriptionFormDialog.vue";
import AppButton from "../../components/ui/AppButton.vue";
import AppConfirmDialog from "../../components/ui/AppConfirmDialog.vue";
import AppRowActions, {
  type AppRowActionItem,
} from "../../components/ui/AppRowActions.vue";
import AppStatusTag from "../../components/ui/AppStatusTag.vue";
import type { Subscription } from "../../../types/api";
import type { SubscriptionPayload } from "../../../composables/useSubscriptions";

type ApiPageError = {
  data?: {
    message?: string;
  };
};

definePageMeta({
  middleware: ["auth"],
});

const { list, remove } = useSubscriptions();

const loading = ref(false);
const deleteLoading = ref(false);
const subscriptions = ref<Subscription[]>([]);
const selectedSubscription = ref<Subscription | null>(null);
const paymentSubscription = ref<Subscription | null>(null);
const statusFilter = ref<SubscriptionPayload["status"] | "all">("all");
const dialogOpen = ref(false);
const paymentDialogOpen = ref(false);
const confirmDeleteOpen = ref(false);
const errorMessage = ref("");
const confirmDeleteId = ref<number | null>(null);
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
    key: "pay",
    label: "Record payment",
    icon: "lucide:wallet",
  },
  {
    key: "edit",
    label: "Edit subscription",
    icon: "lucide:square-pen",
  },
  {
    key: "delete",
    label: "Delete subscription",
    icon: "lucide:trash-2",
    tone: "danger",
  },
];

const statusOptions = [
  { label: "All statuses", value: "all" },
  { label: "Active", value: "active" },
  { label: "Pending", value: "pending" },
  { label: "Expired", value: "expired" },
  { label: "Frozen", value: "frozen" },
  { label: "Cancelled", value: "cancelled" },
] as const;

const loadSubscriptions = async (page = pagination.current_page) => {
  loading.value = true;
  errorMessage.value = "";

  try {
    const response = await list({
      page,
      per_page: 10,
      status: statusFilter.value === "all" ? undefined : statusFilter.value,
    });

    subscriptions.value = response.data;
    pagination.total = response.total;
    pagination.current_page = response.current_page;
    pagination.last_page = response.last_page;
    pagination.from = response.from;
    pagination.to = response.to;
  } catch (error) {
    const typedError = error as ApiPageError;

    errorMessage.value =
      typedError.data?.message ?? "Unable to load subscriptions.";
  } finally {
    loading.value = false;
  }
};

const openCreate = () => {
  selectedSubscription.value = null;
  dialogOpen.value = true;
};

const openEdit = (subscription: Subscription) => {
  selectedSubscription.value = subscription;
  dialogOpen.value = true;
};

const openPayment = (subscription: Subscription) => {
  paymentSubscription.value = subscription;
  paymentDialogOpen.value = true;
};

const handleSaved = async () => {
  await loadSubscriptions();
};

const handleDeleted = async () => {
  await loadSubscriptions(resolveReloadPage(1));
};

const promptDelete = (subscription: Subscription) => {
  confirmDeleteId.value = subscription.id;
  confirmDeleteMessage.value = `Delete ${memberName(subscription)}'s subscription? This action cannot be undone.`;
  confirmDeleteOpen.value = true;
};

const confirmDelete = async () => {
  if (confirmDeleteId.value === null) {
    confirmDeleteOpen.value = false;
    return;
  }

  deleteLoading.value = true;
  errorMessage.value = "";

  try {
    await remove(confirmDeleteId.value);
    confirmDeleteOpen.value = false;
    await loadSubscriptions(resolveReloadPage(1));
  } catch (error) {
    const typedError = error as ApiPageError;

    errorMessage.value =
      typedError.data?.message ?? "Unable to delete subscription.";
  } finally {
    deleteLoading.value = false;
    confirmDeleteId.value = null;
  }
};

const resolveReloadPage = (removedCount: number) => {
  const visibleCount = subscriptions.value.length;
  const nextPage =
    visibleCount > 0 &&
    removedCount >= visibleCount &&
    pagination.current_page > 1
      ? pagination.current_page - 1
      : pagination.current_page;

  return Math.max(nextPage, 1);
};

const handleRowAction = (action: string, subscription: Subscription) => {
  if (action === "pay") {
    openPayment(subscription);
    return;
  }

  if (action === "edit") {
    openEdit(subscription);
    return;
  }

  if (action === "delete") {
    promptDelete(subscription);
  }
};

const changePage = async (page: number) => {
  if (page < 1 || page > pagination.last_page || loading.value) {
    return;
  }

  await loadSubscriptions(page);
};

const reloadCurrentPage = async () => {
  await loadSubscriptions();
};

const memberName = (subscription: Subscription) => {
  if (!subscription.member) {
    return `Member #${subscription.member_id}`;
  }

  return `${subscription.member.first_name} ${subscription.member.last_name}`;
};

const memberInitials = (subscription: Subscription) => {
  const first = subscription.member?.first_name?.charAt(0) ?? "M";
  const last = subscription.member?.last_name?.charAt(0) ?? "S";

  return `${first}${last}`.toUpperCase();
};

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

const formatCurrency = (value: string | number) =>
  new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    maximumFractionDigits: 2,
  }).format(Number(value));

const isEndingSoon = (value: string) => {
  const endDate = new Date(value);

  if (Number.isNaN(endDate.getTime())) {
    return false;
  }

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const nextWeek = new Date(today);
  nextWeek.setDate(today.getDate() + 7);

  return endDate >= today && endDate <= nextWeek;
};

await loadSubscriptions();

const activeCount = computed(
  () => subscriptions.value.filter((item) => item.status === "active").length,
);

const paidCount = computed(
  () =>
    subscriptions.value.filter((item) => item.payment_status === "paid").length,
);

const endingSoonCount = computed(
  () =>
    subscriptions.value.filter(
      (item) => item.status === "active" && isEndingSoon(item.end_date),
    ).length,
);

watch(statusFilter, () => {
  loadSubscriptions(1);
});
</script>

<style scoped>
.subscriptions-filter {
  min-width: 180px;
}

.subscriptions-pagination {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
  flex-wrap: wrap;
}

@media (max-width: 959px) {
  .subscriptions-filter {
    width: 100%;
  }
}
</style>
