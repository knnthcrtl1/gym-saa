<template>
  <div class="app-page">
    <PageHeader
      eyebrow="Revenue desk"
      title="Payments"
      description="Track pending checkout links, manual settlements, and subscription payment state from one place."
    >
      <template #actions>
        <span class="surface-pill">
          <Icon name="lucide:receipt-text" size="16" />
          {{ pagination.total }} total
        </span>
        <AppButton tone="primary" :loading="loading" @click="dialogOpen = true">
          <Icon name="lucide:plus" size="18" class="mr-2" />
          Record payment
        </AppButton>
      </template>
    </PageHeader>

    <div class="metric-grid">
      <v-row>
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
              <div class="panel-label">Pending</div>
              <div class="stat-card__value">{{ pendingCount }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card class="content-panel">
            <v-card-text>
              <div class="panel-label">Captured value</div>
              <div class="stat-card__value">{{ capturedValue }}</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <TableShell
      eyebrow="Payment ledger"
      title="Settlements and checkout drafts"
      description="Hosted checkout links stay pending until the PayMongo webhook updates the payment row."
    >
      <template #notice>
        <v-alert v-if="noticeMessage" :type="noticeTone" variant="tonal">
          {{ noticeMessage }}
        </v-alert>
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
              <th>Subscription</th>
              <th>Date</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Status</th>
              <th class="text-right">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center py-6">Loading payments...</td>
            </tr>

            <tr v-else-if="payments.length === 0">
              <td colspan="7" class="text-center py-10">
                <div class="empty-state">
                  <div class="panel-label mb-2">No results</div>
                  Payment records will appear here once transactions are
                  created.
                </div>
              </td>
            </tr>

            <tr v-for="payment in payments" :key="payment.id">
              <td>
                <div class="table-primary-cell">
                  <div class="surface-avatar surface-avatar--sm">
                    {{ memberInitials(payment) }}
                  </div>
                  <div>
                    <div class="table-primary-cell__title">
                      {{ memberName(payment) }}
                    </div>
                    <div class="table-primary-cell__subtitle">
                      Payment #{{ payment.id }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="table-cell-muted">
                {{
                  payment.subscription?.membership_plan?.name ||
                  fallbackSubscription(payment)
                }}
              </td>
              <td class="table-cell-muted">
                {{ formatDate(payment.payment_date) }}
              </td>
              <td class="table-cell-muted">
                {{ formatCurrency(payment.amount) }}
              </td>
              <td class="table-cell-muted">{{ payment.payment_method }}</td>
              <td>
                <AppStatusTag :label="payment.status" />
              </td>
              <td class="text-right">
                <AppButton
                  v-if="payment.status === 'pending' && payment.checkout_url"
                  tone="primary"
                  appearance="outline"
                  @click="openCheckout(payment.checkout_url)"
                >
                  Continue checkout
                </AppButton>
                <span v-else class="table-cell-muted">-</span>
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>

      <template #footer>
        <div class="payments-pagination">
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

    <PaymentDialog v-model="dialogOpen" @saved="handleSaved" />
  </div>
</template>

<script setup lang="ts">
import type { Payment } from "../../../types/api";
import { usePayments } from "../../../composables/usePayments";
import PageHeader from "../../components/admin/PageHeader.vue";
import TableShell from "../../components/admin/TableShell.vue";
import PaymentDialog from "../../components/payments/PaymentDialog.vue";
import AppButton from "../../components/ui/AppButton.vue";
import AppStatusTag from "../../components/ui/AppStatusTag.vue";

type ApiPageError = {
  data?: {
    message?: string;
  };
};

definePageMeta({
  middleware: ["auth"],
});

const route = useRoute();
const { list } = usePayments();

const loading = ref(false);
const dialogOpen = ref(false);
const payments = ref<Payment[]>([]);
const errorMessage = ref("");
const noticeMessage = computed(() => {
  if (route.query.checkout === "success") {
    return "Returned from PayMongo checkout. Payment status will update after the webhook is processed.";
  }

  if (route.query.checkout === "cancelled") {
    return "Checkout was cancelled. The payment draft remains pending until you retry or record it manually.";
  }

  return "";
});

const noticeTone = computed(() =>
  route.query.checkout === "cancelled" ? "warning" : "success",
);

const pagination = reactive({
  total: 0,
  current_page: 1,
  last_page: 1,
  from: 0 as number | null,
  to: 0 as number | null,
});

const loadPayments = async (page = pagination.current_page) => {
  loading.value = true;
  errorMessage.value = "";

  try {
    const response = await list({ page, per_page: 10 });

    payments.value = response.data;
    pagination.total = response.total;
    pagination.current_page = response.current_page;
    pagination.last_page = response.last_page;
    pagination.from = response.from;
    pagination.to = response.to;
  } catch (error) {
    const typedError = error as ApiPageError;

    errorMessage.value = typedError.data?.message ?? "Unable to load payments.";
  } finally {
    loading.value = false;
  }
};

const handleSaved = async () => {
  await loadPayments(1);
};

const changePage = async (page: number) => {
  if (page < 1 || page > pagination.last_page || loading.value) {
    return;
  }

  await loadPayments(page);
};

const reloadCurrentPage = async () => {
  await loadPayments();
};

const memberName = (payment: Payment) => {
  if (!payment.member) {
    return `Member #${payment.member_id}`;
  }

  return `${payment.member.first_name} ${payment.member.last_name}`;
};

const memberInitials = (payment: Payment) => {
  const first = payment.member?.first_name?.charAt(0) ?? "P";
  const last = payment.member?.last_name?.charAt(0) ?? "Y";

  return `${first}${last}`.toUpperCase();
};

const fallbackSubscription = (payment: Payment) =>
  payment.subscription_id
    ? `Subscription #${payment.subscription_id}`
    : "Manual payment";

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

const openCheckout = (checkoutUrl: string) => {
  if (import.meta.client) {
    window.location.href = checkoutUrl;
  }
};

await loadPayments();

const paidCount = computed(
  () => payments.value.filter((item) => item.status === "paid").length,
);

const pendingCount = computed(
  () => payments.value.filter((item) => item.status === "pending").length,
);

const capturedValue = computed(() =>
  formatCurrency(
    payments.value
      .filter((item) => item.status === "paid")
      .reduce((sum, item) => sum + Number(item.amount), 0),
  ),
);
</script>

<style scoped>
.payments-pagination {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
  flex-wrap: wrap;
}
</style>
