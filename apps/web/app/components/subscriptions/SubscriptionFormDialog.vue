<template>
  <AppModalShell
    v-model="internalOpen"
    :eyebrow="isEdit ? 'Edit subscription' : 'Add subscription'"
    :title="dialogTitle"
    :description="dialogDescription"
    :max-width="920"
    @close="closeDialog"
  >
    <div class="section-stack">
      <div class="subscription-form-hero">
        <div class="surface-avatar subscription-form-hero__avatar">
          {{ memberInitials }}
        </div>
        <div class="subscription-form-hero__copy">
          <div class="text-h6 font-weight-bold">{{ heroTitle }}</div>
          <div class="subscription-form-hero__meta">
            <span class="surface-pill">
              <Icon name="lucide:badge-dollar-sign" size="16" />
              {{ formattedAmount }}
            </span>
            <span v-if="selectedPlanName" class="surface-pill">
              <Icon name="lucide:shield-check" size="16" />
              {{ selectedPlanName }}
            </span>
            <AppStatusTag :label="form.status" />
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
            <h2 class="section-panel__title">Assignment</h2>
            <p class="section-panel__body">
              Connect the selected member to a plan and track the active period.
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
              :disabled="memberLocked"
              :error-messages="errors.member_id"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-select
              v-model="form.membership_plan_id"
              :items="planOptions"
              item-title="label"
              item-value="id"
              label="Membership plan"
              variant="outlined"
              :loading="optionsLoading"
              :error-messages="errors.membership_plan_id"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.start_date"
              label="Start date"
              type="date"
              variant="outlined"
              :error-messages="errors.start_date"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.end_date"
              label="End date"
              type="date"
              variant="outlined"
              readonly
              hint="Calculated from the selected plan and start date."
              persistent-hint
              :error-messages="errors.end_date"
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
        </v-row>
      </section>

      <section class="section-panel">
        <div class="section-panel__header">
          <div>
            <h2 class="section-panel__title">Lifecycle</h2>
            <p class="section-panel__body">
              Control the payment state, subscription status, and session
              balance.
            </p>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="4">
            <v-select
              v-model="form.payment_status"
              :items="paymentStatuses"
              label="Payment status"
              variant="outlined"
              :error-messages="errors.payment_status"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-select
              v-model="form.status"
              :items="subscriptionStatuses"
              label="Subscription status"
              variant="outlined"
              :error-messages="errors.status"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model.number="form.sessions_remaining"
              label="Sessions remaining"
              type="number"
              min="0"
              hint="Auto-filled from the plan when applicable, but still editable."
              persistent-hint
              variant="outlined"
              :error-messages="errors.sessions_remaining"
            />
          </v-col>
        </v-row>
      </section>

      <section v-if="showScopeFields" class="section-panel">
        <div class="section-panel__header">
          <div>
            <h2 class="section-panel__title">Scope</h2>
            <p class="section-panel__body">
              Tenant and branch default to the signed-in account and only need
              manual input when unavailable.
            </p>
          </div>
        </div>

        <v-row>
          <v-col v-if="showTenantField" cols="12" md="6">
            <v-text-field
              v-model.number="form.tenant_id"
              label="Tenant ID"
              type="number"
              min="1"
              variant="outlined"
              :error-messages="errors.tenant_id"
            />
          </v-col>

          <v-col v-if="showBranchField" cols="12" md="6">
            <v-text-field
              v-model.number="form.branch_id"
              label="Branch ID"
              type="number"
              min="1"
              variant="outlined"
              :error-messages="errors.branch_id"
            />
          </v-col>
        </v-row>
      </section>
    </div>

    <template #footer-prepend>
      <AppButton
        v-if="isEdit"
        tone="danger"
        appearance="text"
        :loading="deleteLoading"
        @click="confirmDeleteOpen = true"
      >
        <Icon name="lucide:trash-2" size="16" class="mr-2" />
        Delete subscription
      </AppButton>
    </template>

    <template #footer>
      <AppButton tone="neutral" appearance="text" @click="closeDialog">
        Cancel
      </AppButton>
      <AppButton tone="primary" :loading="loading" @click="submitForm">
        {{ isEdit ? "Save changes" : "Create subscription" }}
      </AppButton>
    </template>
  </AppModalShell>

  <AppConfirmDialog
    v-model="confirmDeleteOpen"
    title="Delete subscription"
    :message="deletePrompt"
    confirm-text="Delete"
    tone="danger"
    :loading="deleteLoading"
    @confirm="deleteSubscription"
  />
</template>

<script setup lang="ts">
import AppButton from "../ui/AppButton.vue";
import AppConfirmDialog from "../ui/AppConfirmDialog.vue";
import AppModalShell from "../ui/AppModalShell.vue";
import AppStatusTag from "../ui/AppStatusTag.vue";
import type { Member, MembershipPlan, Subscription } from "../../../types/api";
import type { SubscriptionPayload } from "../../../composables/useSubscriptions";

type SubscriptionFormValues = SubscriptionPayload & {
  end_date: string;
};

type SubscriptionFormErrors = Record<keyof SubscriptionFormValues, string[]>;
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
  member?: Member | null;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  saved: [];
  deleted: [];
}>();

const { user } = useAuth();
const { list: listMembers } = useMembers();
const { list: listPlans } = usePlans();
const { create, update, remove } = useSubscriptions();

const internalOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit("update:modelValue", value),
});

const isEdit = computed(() => Boolean(props.subscription?.id));
const memberLocked = computed(() =>
  Boolean(props.member && !props.subscription),
);
const loading = ref(false);
const deleteLoading = ref(false);
const optionsLoading = ref(false);
const confirmDeleteOpen = ref(false);
const errorMessage = ref("");
const optionsError = ref("");
const memberOptions = ref<SelectOption[]>([]);
const planOptions = ref<SelectOption[]>([]);
const availableMembers = ref<Member[]>([]);
const availablePlans = ref<MembershipPlan[]>([]);
const syncingForm = ref(false);

const paymentStatuses: SubscriptionPayload["payment_status"][] = [
  "unpaid",
  "partial",
  "paid",
];
const subscriptionStatuses: SubscriptionPayload["status"][] = [
  "pending",
  "active",
  "expired",
  "frozen",
  "cancelled",
];

const defaultForm = (): SubscriptionFormValues => ({
  tenant_id: user.value?.tenant_id ?? 0,
  branch_id: user.value?.branch_id ?? 0,
  member_id: props.member?.id ?? 0,
  membership_plan_id: 0,
  start_date: "",
  end_date: "",
  amount: 0,
  sessions_remaining: null,
  payment_status: "paid",
  status: "active",
});

const form = reactive<SubscriptionFormValues>(defaultForm());

const createErrors = (): SubscriptionFormErrors => ({
  tenant_id: [],
  branch_id: [],
  member_id: [],
  membership_plan_id: [],
  start_date: [],
  end_date: [],
  amount: [],
  sessions_remaining: [],
  payment_status: [],
  status: [],
});

const errors = reactive<SubscriptionFormErrors>(createErrors());

const clearErrors = () => {
  errorMessage.value = "";
  Object.assign(errors, createErrors());
};

const resetForm = () => {
  syncingForm.value = true;
  Object.assign(form, defaultForm());
  clearErrors();
  nextTick(() => {
    syncingForm.value = false;
  });
};

const fillForm = (subscription: Subscription) => {
  syncingForm.value = true;
  Object.assign(form, {
    tenant_id: subscription.tenant_id,
    branch_id: subscription.branch_id,
    member_id: subscription.member_id,
    membership_plan_id: subscription.membership_plan_id,
    start_date: subscription.start_date,
    end_date: subscription.end_date,
    amount: Number(subscription.amount),
    sessions_remaining: subscription.sessions_remaining ?? null,
    payment_status: subscription.payment_status,
    status: subscription.status,
  });
  clearErrors();
  nextTick(() => {
    syncingForm.value = false;
  });
};

const calculateEndDatePreview = (
  startDateValue: string,
  plan?: MembershipPlan | null,
) => {
  if (!startDateValue || !plan) {
    return "";
  }

  const startDate = new Date(`${startDateValue}T00:00:00`);

  if (Number.isNaN(startDate.getTime())) {
    return "";
  }

  const endDate = new Date(startDate);

  switch (plan.duration_type) {
    case "day":
      endDate.setDate(endDate.getDate() + plan.duration_value);
      break;
    case "week":
      endDate.setDate(endDate.getDate() + plan.duration_value * 7);
      break;
    case "month":
      endDate.setMonth(endDate.getMonth() + plan.duration_value);
      break;
    case "year":
      endDate.setFullYear(endDate.getFullYear() + plan.duration_value);
      break;
    case "session":
    default:
      endDate.setMonth(endDate.getMonth() + 1);
      break;
  }

  return endDate.toISOString().slice(0, 10);
};

const syncDerivedPlanFields = () => {
  if (syncingForm.value) {
    return;
  }

  const plan = selectedPlan.value;

  if (!plan) {
    form.end_date = "";
    return;
  }

  form.amount = Number(plan.price);
  form.sessions_remaining =
    plan.duration_type === "session" ? (plan.session_limit ?? null) : null;
  form.end_date = calculateEndDatePreview(form.start_date, plan);
};

const appendUniqueMember = (member?: Member | null) => {
  if (!member) return;
  if (availableMembers.value.some((item) => item.id === member.id)) return;
  availableMembers.value = [member, ...availableMembers.value];
};

const appendUniquePlan = (plan?: MembershipPlan | null) => {
  if (!plan) return;
  if (availablePlans.value.some((item) => item.id === plan.id)) return;
  availablePlans.value = [plan, ...availablePlans.value];
};

const fetchAllMembers = async () => {
  const aggregated: Member[] = [];
  let page = 1;
  let lastPage = 1;

  do {
    const response = await listMembers({ page, per_page: 100 });
    aggregated.push(...response.data);
    lastPage = response.last_page;
    page += 1;
  } while (page <= lastPage);

  availableMembers.value = aggregated;
};

const fetchAllPlans = async () => {
  const aggregated: MembershipPlan[] = [];
  let page = 1;
  let lastPage = 1;

  do {
    const response = await listPlans({ page, per_page: 100 });
    aggregated.push(...response.data);
    lastPage = response.last_page;
    page += 1;
  } while (page <= lastPage);

  availablePlans.value = aggregated;
};

const syncOptions = () => {
  appendUniqueMember(props.member);
  appendUniqueMember(props.subscription?.member ?? null);
  appendUniquePlan(props.subscription?.membership_plan ?? null);

  memberOptions.value = availableMembers.value.map((member) => ({
    id: member.id,
    label: `${member.first_name} ${member.last_name}`,
  }));

  planOptions.value = availablePlans.value.map((plan) => ({
    id: plan.id,
    label: plan.name,
  }));
};

const loadOptions = async () => {
  optionsLoading.value = true;
  optionsError.value = "";

  try {
    await Promise.all([fetchAllMembers(), fetchAllPlans()]);
    syncOptions();
  } catch (error) {
    const typedError = error as ApiFormError;

    optionsError.value =
      typedError.data?.message ??
      "Unable to load members and plans for subscriptions.";
  } finally {
    optionsLoading.value = false;
  }
};

watch(
  () => props.modelValue,
  async (open) => {
    if (!open) return;

    await loadOptions();

    if (props.subscription) {
      fillForm(props.subscription);
      return;
    }

    resetForm();
  },
);

watch(
  () => props.member,
  (member) => {
    appendUniqueMember(member);
    syncOptions();

    if (!isEdit.value && member?.id) {
      form.member_id = member.id;
    }
  },
);

watch(
  () => form.membership_plan_id,
  () => {
    if (!form.membership_plan_id) {
      if (!syncingForm.value) {
        form.end_date = "";
      }
      return;
    }

    syncDerivedPlanFields();
  },
);

watch(
  () => form.start_date,
  () => {
    if (!form.start_date || !selectedPlan.value) {
      if (!syncingForm.value) {
        form.end_date = "";
      }
      return;
    }

    if (!syncingForm.value) {
      form.end_date = calculateEndDatePreview(
        form.start_date,
        selectedPlan.value,
      );
    }
  },
);

const closeDialog = () => {
  internalOpen.value = false;
};

const normalizePayload = (): SubscriptionPayload => ({
  tenant_id: Number(form.tenant_id),
  branch_id: Number(form.branch_id),
  member_id: Number(form.member_id),
  membership_plan_id: Number(form.membership_plan_id),
  start_date: form.start_date,
  amount: Number(form.amount),
  sessions_remaining:
    form.sessions_remaining === null || form.sessions_remaining === undefined
      ? null
      : Number(form.sessions_remaining),
  payment_status: form.payment_status,
  status: form.status,
});

const assignBackendErrors = (backendErrors?: Record<string, string[]>) => {
  if (!backendErrors) return;

  for (const [field, messages] of Object.entries(backendErrors)) {
    if (field in errors) {
      errors[field as keyof SubscriptionFormErrors] = messages;
    }
  }
};

const submitForm = async () => {
  loading.value = true;
  clearErrors();

  try {
    const payload = normalizePayload();

    if (isEdit.value && props.subscription) {
      await update(props.subscription.id, payload);
    } else {
      await create(payload);
    }

    emit("saved");
    closeDialog();
  } catch (error) {
    const typedError = error as ApiFormError;

    errorMessage.value =
      typedError.data?.message ?? "Unable to save subscription.";
    assignBackendErrors(typedError.data?.errors);
  } finally {
    loading.value = false;
  }
};

const deleteSubscription = async () => {
  if (!props.subscription) {
    confirmDeleteOpen.value = false;
    return;
  }

  deleteLoading.value = true;
  clearErrors();

  try {
    await remove(props.subscription.id);
    emit("deleted");
    confirmDeleteOpen.value = false;
    closeDialog();
  } catch (error) {
    const typedError = error as ApiFormError;

    errorMessage.value =
      typedError.data?.message ?? "Unable to delete subscription.";
  } finally {
    deleteLoading.value = false;
  }
};

const selectedMember = computed(
  () =>
    availableMembers.value.find((member) => member.id === form.member_id) ??
    null,
);

const selectedPlan = computed(
  () =>
    availablePlans.value.find((plan) => plan.id === form.membership_plan_id) ??
    null,
);

const showTenantField = computed(() => !user.value?.tenant_id);
const showBranchField = computed(() => !user.value?.branch_id);
const showScopeFields = computed(
  () => showTenantField.value || showBranchField.value,
);

const dialogTitle = computed(() =>
  isEdit.value ? "Edit member subscription" : "Create member subscription",
);

const dialogDescription = computed(() =>
  isEdit.value
    ? "Update lifecycle details, payment state, and plan assignment from one shared modal."
    : "Assign a membership plan to a member and capture the initial subscription state.",
);

const heroTitle = computed(() => {
  if (selectedMember.value) {
    return `${selectedMember.value.first_name} ${selectedMember.value.last_name}`;
  }

  return isEdit.value ? "Subscription profile" : "New subscription";
});

const memberInitials = computed(() => {
  const member = selectedMember.value;

  if (!member) {
    return "NS";
  }

  return `${member.first_name.charAt(0)}${member.last_name.charAt(0)}`.toUpperCase();
});

const selectedPlanName = computed(() => selectedPlan.value?.name ?? "");

const formattedAmount = computed(() =>
  new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    maximumFractionDigits: 2,
  }).format(Number(form.amount || 0)),
);

const deletePrompt = computed(() => {
  const memberName =
    heroTitle.value.toLowerCase() === "subscription profile"
      ? "this subscription"
      : heroTitle.value;

  return `Delete ${memberName}? This action cannot be undone.`;
});
</script>

<style scoped>
.subscription-form-hero {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  border: 1px solid var(--gym-border);
  border-radius: 20px;
  background: linear-gradient(
    135deg,
    rgba(14, 165, 233, 0.08),
    rgba(255, 255, 255, 0.94)
  );
}

.subscription-form-hero__copy {
  min-width: 0;
}

.subscription-form-hero__meta {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 10px;
}

@media (max-width: 640px) {
  .subscription-form-hero {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
