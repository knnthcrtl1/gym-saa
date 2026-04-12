<template>
  <AppModalShell
    v-model="internalOpen"
    eyebrow="Payment flow"
    :title="dialogTitle"
    :description="dialogDescription"
    :max-width="920"
    @close="closeDialog"
  >
    <div class="section-stack">
      <div class="payment-form-hero">
        <div class="surface-avatar payment-form-hero__avatar">
          {{ memberInitials }}
        </div>
        <div class="payment-form-hero__copy">
          <div class="text-h6 font-weight-bold">{{ heroTitle }}</div>
          <div class="payment-form-hero__meta">
            <span class="surface-pill">
              <Icon name="lucide:wallet" size="16" />
              {{ formattedAmount }}
            </span>
            <span v-if="selectedSubscriptionLabel" class="surface-pill">
              <Icon name="lucide:credit-card" size="16" />
              {{ selectedSubscriptionLabel }}
            </span>
            <AppStatusTag :label="modeChip" />
          </div>
        </div>
      </div>

      <v-alert v-if="errorMessage" type="error" variant="tonal">
        {{ errorMessage }}
      </v-alert>

      <v-alert v-if="optionsError" type="warning" variant="tonal">
        {{ optionsError }}
      </v-alert>

      <section class="section-panel">
        <div class="section-panel__header">
          <div>
            <h2 class="section-panel__title">Payment mode</h2>
            <p class="section-panel__body">
              Choose hosted checkout for PayMongo, or record a manual settlement
              directly in the system.
            </p>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="6">
            <v-select
              v-model="form.mode"
              :items="modeOptions"
              item-title="label"
              item-value="value"
              label="Mode"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-select
              v-model="form.status"
              :items="statusOptions"
              label="Manual status"
              variant="outlined"
              :disabled="form.mode === 'online'"
              :error-messages="errors.status"
            />
          </v-col>
        </v-row>
      </section>

      <section class="section-panel">
        <div class="section-panel__header">
          <div>
            <h2 class="section-panel__title">Assignment</h2>
            <p class="section-panel__body">
              Attach the payment to a member and, when applicable, a specific
              subscription.
            </p>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="6">
            <v-select
              v-model="form.member_id"
              :items="memberOptions"
              item-title="label"
              item-value="id"
              label="Member"
              variant="outlined"
              :loading="optionsLoading"
              :error-messages="errors.member_id"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-select
              v-model="form.subscription_id"
              :items="subscriptionOptions"
              item-title="label"
              item-value="id"
              label="Subscription"
              variant="outlined"
              clearable
              :loading="optionsLoading"
              :error-messages="errors.subscription_id"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model.number="form.amount"
              label="Amount"
              type="number"
              min="0"
              step="0.01"
              variant="outlined"
              :error-messages="errors.amount"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.currency"
              label="Currency"
              variant="outlined"
              :disabled="form.mode === 'online'"
              :error-messages="errors.currency"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.payment_date"
              label="Payment date"
              type="date"
              variant="outlined"
              :disabled="form.mode === 'online'"
              :error-messages="errors.payment_date"
            />
          </v-col>
        </v-row>
      </section>

      <section class="section-panel">
        <div class="section-panel__header">
          <div>
            <h2 class="section-panel__title">Settlement details</h2>
            <p class="section-panel__body">
              Hosted checkout ignores manual settlement fields and redirects to
              PayMongo after the record is created.
            </p>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="6">
            <v-select
              v-model="form.payment_method"
              :items="paymentMethods"
              label="Payment method"
              variant="outlined"
              :disabled="form.mode === 'online'"
              :error-messages="errors.payment_method"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.reference_no"
              label="Reference number"
              variant="outlined"
              :disabled="form.mode === 'online'"
              :error-messages="errors.reference_no"
            />
          </v-col>

          <v-col cols="12">
            <v-textarea
              v-model="form.notes"
              label="Notes"
              variant="outlined"
              rows="3"
              :error-messages="errors.notes"
            />
          </v-col>
        </v-row>
      </section>
    </div>

    <template #footer>
      <AppButton tone="neutral" appearance="text" @click="closeDialog">
        Cancel
      </AppButton>
      <AppButton tone="primary" :loading="loading" @click="submitForm">
        {{ actionLabel }}
      </AppButton>
    </template>
  </AppModalShell>
</template>

<script setup lang="ts">
import AppButton from "../ui/AppButton.vue";
import AppModalShell from "../ui/AppModalShell.vue";
import AppStatusTag from "../ui/AppStatusTag.vue";
import type { Member, Payment, Subscription } from "../../../types/api";
import type {
  ManualPaymentPayload,
  PaymentIntentPayload,
} from "../../../composables/usePayments";
import { usePayments } from "../../../composables/usePayments";

type PaymentMode = "online" | "manual";

type PaymentDialogForm = PaymentIntentPayload &
  Pick<
    ManualPaymentPayload,
    "payment_date" | "payment_method" | "reference_no" | "status"
  > & {
    mode: PaymentMode;
  };

type PaymentDialogErrors = Record<keyof PaymentDialogForm, string[]>;
type ApiFormError = {
  data?: {
    message?: string;
    errors?: Record<string, string[]>;
  };
};

type SelectOption = {
  id: number;
  label: string;
};

const props = defineProps<{
  modelValue: boolean;
  subscription?: Subscription | null;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  saved: [];
}>();

const { user } = useAuth();
const { list: listMembers } = useMembers();
const { list: listSubscriptions } = useSubscriptions();
const { createIntent, recordManual } = usePayments();

const internalOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit("update:modelValue", value),
});

const loading = ref(false);
const optionsLoading = ref(false);
const errorMessage = ref("");
const optionsError = ref("");
const availableMembers = ref<Member[]>([]);
const availableSubscriptions = ref<Subscription[]>([]);
const memberOptions = ref<SelectOption[]>([]);

const modeOptions = [
  { label: "Hosted PayMongo checkout", value: "online" },
  { label: "Manual payment record", value: "manual" },
] as const;

const paymentMethods: Payment["payment_method"][] = [
  "cash",
  "gcash",
  "bank_transfer",
  "card",
  "online",
];

const statusOptions: Payment["status"][] = [
  "pending",
  "paid",
  "failed",
  "refunded",
];

const defaultForm = (): PaymentDialogForm => ({
  mode: "online",
  tenant_id: user.value?.tenant_id ?? 0,
  branch_id: user.value?.branch_id ?? 0,
  member_id: props.subscription?.member_id ?? 0,
  subscription_id: props.subscription?.id ?? null,
  amount: props.subscription ? Number(props.subscription.amount) : 0,
  currency: "PHP",
  notes: "",
  payment_date: new Date().toISOString().slice(0, 10),
  payment_method: "cash",
  reference_no: "",
  status: "paid",
});

const form = reactive<PaymentDialogForm>(defaultForm());

const createErrors = (): PaymentDialogErrors => ({
  mode: [],
  tenant_id: [],
  branch_id: [],
  member_id: [],
  subscription_id: [],
  amount: [],
  currency: [],
  notes: [],
  payment_date: [],
  payment_method: [],
  reference_no: [],
  status: [],
});

const errors = reactive<PaymentDialogErrors>(createErrors());

const clearErrors = () => {
  errorMessage.value = "";
  Object.assign(errors, createErrors());
};

const selectedMember = computed(
  () =>
    availableMembers.value.find((item) => item.id === form.member_id) ?? null,
);

const filteredSubscriptions = computed(() => {
  if (!form.member_id) {
    return availableSubscriptions.value;
  }

  return availableSubscriptions.value.filter(
    (item) => item.member_id === form.member_id,
  );
});

const formatCurrency = (value: string | number) =>
  new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    maximumFractionDigits: 2,
  }).format(Number(value || 0));

const subscriptionOptions = computed<SelectOption[]>(() =>
  filteredSubscriptions.value.map((item) => ({
    id: item.id,
    label: `${item.membership_plan?.name ?? `Subscription #${item.id}`} · ${formatCurrency(item.amount)}`,
  })),
);

const selectedSubscription = computed(
  () =>
    availableSubscriptions.value.find(
      (item) => item.id === form.subscription_id,
    ) ??
    props.subscription ??
    null,
);

const selectedSubscriptionLabel = computed(() => {
  const subscription = selectedSubscription.value;

  if (!subscription) {
    return "";
  }

  return (
    subscription.membership_plan?.name ?? `Subscription #${subscription.id}`
  );
});

const dialogTitle = computed(() =>
  form.mode === "online" ? "Create checkout payment" : "Record manual payment",
);

const dialogDescription = computed(() =>
  form.mode === "online"
    ? "Create a pending payment, attach PayMongo checkout metadata, and redirect the user to the hosted checkout page."
    : "Capture an already-settled payment and synchronize the linked subscription status immediately.",
);

const actionLabel = computed(() =>
  form.mode === "online" ? "Continue to checkout" : "Record payment",
);

const heroTitle = computed(() => {
  if (selectedMember.value) {
    return `${selectedMember.value.first_name} ${selectedMember.value.last_name}`;
  }

  return "Payment draft";
});

const memberInitials = computed(() => {
  const first = selectedMember.value?.first_name?.charAt(0) ?? "P";
  const last = selectedMember.value?.last_name?.charAt(0) ?? "Y";

  return `${first}${last}`.toUpperCase();
});

const formattedAmount = computed(() => formatCurrency(form.amount));
const modeChip = computed(() =>
  form.mode === "online" ? "Hosted checkout" : `Manual ${form.status}`,
);

const mapErrors = (apiErrors?: Record<string, string[]>) => {
  if (!apiErrors) {
    return;
  }

  for (const [key, value] of Object.entries(apiErrors)) {
    if (key in errors) {
      errors[key as keyof PaymentDialogErrors] = value;
    }
  }
};

const resetForm = () => {
  Object.assign(form, defaultForm());
  clearErrors();
};

const loadOptions = async () => {
  optionsLoading.value = true;
  optionsError.value = "";

  try {
    const [membersResponse, subscriptionsResponse] = await Promise.all([
      listMembers({ per_page: 100 }),
      listSubscriptions({ per_page: 100 }),
    ]);

    availableMembers.value = membersResponse.data;
    availableSubscriptions.value = subscriptionsResponse.data;
    memberOptions.value = availableMembers.value.map((member) => ({
      id: member.id,
      label: `${member.first_name} ${member.last_name}`,
    }));
  } catch (error) {
    const typedError = error as ApiFormError;

    optionsError.value =
      typedError.data?.message ?? "Unable to load payment options.";
  } finally {
    optionsLoading.value = false;
  }
};

const syncSubscriptionFields = () => {
  const subscription = selectedSubscription.value;

  if (!subscription) {
    return;
  }

  form.member_id = subscription.member_id;
  form.amount = Number(subscription.amount);
  form.tenant_id = subscription.tenant_id;
  form.branch_id = subscription.branch_id;
};

const closeDialog = () => {
  internalOpen.value = false;
};

const submitForm = async () => {
  loading.value = true;
  clearErrors();

  try {
    if (form.mode === "online") {
      const response = await createIntent({
        tenant_id: form.tenant_id,
        branch_id: form.branch_id,
        member_id: form.member_id,
        subscription_id: form.subscription_id,
        amount: Number(form.amount),
        currency: form.currency,
        notes: form.notes || null,
      });

      emit("saved");
      closeDialog();

      if (import.meta.client) {
        window.location.href = response.data.checkout_url;
      }

      return;
    }

    await recordManual({
      tenant_id: form.tenant_id,
      branch_id: form.branch_id,
      member_id: form.member_id,
      subscription_id: form.subscription_id,
      payment_date: form.payment_date,
      amount: Number(form.amount),
      payment_method: form.payment_method,
      reference_no: form.reference_no || null,
      notes: form.notes || null,
      status: form.status,
    });

    emit("saved");
    closeDialog();
  } catch (error) {
    const typedError = error as ApiFormError;

    errorMessage.value = typedError.data?.message ?? "Unable to save payment.";
    mapErrors(typedError.data?.errors);
  } finally {
    loading.value = false;
  }
};

watch(
  () => props.modelValue,
  async (isOpen) => {
    if (!isOpen) {
      resetForm();
      return;
    }

    resetForm();
    await loadOptions();
    syncSubscriptionFields();
  },
);

watch(
  () => props.subscription,
  () => {
    if (!internalOpen.value) {
      return;
    }

    Object.assign(form, defaultForm());
    syncSubscriptionFields();
  },
);

watch(
  () => form.member_id,
  () => {
    if (
      form.subscription_id &&
      !filteredSubscriptions.value.some(
        (item) => item.id === form.subscription_id,
      )
    ) {
      form.subscription_id = null;
    }
  },
);

watch(
  () => form.subscription_id,
  () => {
    syncSubscriptionFields();
  },
);

watch(
  () => form.mode,
  (mode) => {
    if (mode === "online") {
      form.payment_method = "online";
      form.status = "paid";
      return;
    }

    if (form.payment_method === "online") {
      form.payment_method = "cash";
    }
  },
);
</script>

<style scoped>
.payment-form-hero {
  display: flex;
  align-items: center;
  gap: 16px;
}

.payment-form-hero__avatar {
  width: 56px;
  height: 56px;
  font-size: 1rem;
}

.payment-form-hero__copy {
  display: grid;
  gap: 8px;
}

.payment-form-hero__meta {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
</style>
