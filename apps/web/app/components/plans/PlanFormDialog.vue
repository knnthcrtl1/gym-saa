<template>
  <AppModalShell
    v-model="internalOpen"
    :eyebrow="isEdit ? 'Edit plan' : 'Add plan'"
    :title="dialogTitle"
    :description="dialogDescription"
    :max-width="840"
    @close="closeDialog"
  >
    <div class="section-stack">
      <div class="plan-form-hero">
        <div class="surface-avatar plan-form-hero__avatar">
          {{ planInitials }}
        </div>
        <div class="plan-form-hero__copy">
          <div class="text-h6 font-weight-bold">{{ heroTitle }}</div>
          <div class="plan-form-hero__meta">
            <span class="surface-pill">
              <Icon name="lucide:clock-3" size="16" />
              {{ durationLabel }}
            </span>
            <span class="surface-pill">
              <Icon name="lucide:wallet" size="16" />
              {{ formattedPrice }}
            </span>
            <AppStatusTag :label="form.status" />
          </div>
        </div>
      </div>

      <v-alert v-if="errorMessage" type="error" variant="tonal">
        {{ errorMessage }}
      </v-alert>

      <section class="section-panel">
        <div class="section-panel__header">
          <div>
            <h2 class="section-panel__title">Offer details</h2>
            <p class="section-panel__body">
              Define the membership name, pricing, and how long the plan lasts.
            </p>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="8">
            <v-text-field
              v-model="form.name"
              label="Plan name"
              variant="outlined"
              :error-messages="errors.name"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-select
              v-model="form.status"
              :items="statusOptions"
              label="Status"
              variant="outlined"
              :error-messages="errors.status"
            />
          </v-col>

          <v-col cols="12">
            <v-textarea
              v-model="form.description"
              label="Description"
              variant="outlined"
              rows="3"
              :error-messages="errors.description"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-select
              v-model="form.duration_type"
              :items="durationTypes"
              label="Duration type"
              variant="outlined"
              :error-messages="errors.duration_type"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model.number="form.duration_value"
              label="Duration value"
              type="number"
              min="1"
              variant="outlined"
              :error-messages="errors.duration_value"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model.number="form.price"
              label="Price"
              type="number"
              min="0"
              step="0.01"
              variant="outlined"
              :error-messages="errors.price"
            />
          </v-col>
        </v-row>
      </section>

      <section class="section-panel">
        <div class="section-panel__header">
          <div>
            <h2 class="section-panel__title">Limits</h2>
            <p class="section-panel__body">
              Configure optional session and freeze limits for this plan.
            </p>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="6">
            <v-text-field
              v-model.number="form.session_limit"
              label="Session limit"
              type="number"
              min="1"
              hint="Leave empty for unlimited access."
              persistent-hint
              variant="outlined"
              :error-messages="errors.session_limit"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model.number="form.freeze_limit_days"
              label="Freeze limit days"
              type="number"
              min="0"
              hint="Optional number of days a member can freeze this plan."
              persistent-hint
              variant="outlined"
              :error-messages="errors.freeze_limit_days"
            />
          </v-col>
        </v-row>
      </section>

      <section v-if="showScopeFields" class="section-panel">
        <div class="section-panel__header">
          <div>
            <h2 class="section-panel__title">Scope</h2>
            <p class="section-panel__body">
              Tenant and branch are prefilled from the signed-in account when
              available.
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
        Delete plan
      </AppButton>
    </template>

    <template #footer>
      <AppButton tone="neutral" appearance="text" @click="closeDialog">
        Cancel
      </AppButton>
      <AppButton tone="primary" :loading="loading" @click="submitForm">
        {{ isEdit ? "Save changes" : "Create plan" }}
      </AppButton>
    </template>
  </AppModalShell>

  <AppConfirmDialog
    v-model="confirmDeleteOpen"
    title="Delete plan"
    :message="deletePrompt"
    confirm-text="Delete"
    tone="danger"
    :loading="deleteLoading"
    @confirm="deletePlan"
  />
</template>

<script setup lang="ts">
import AppButton from "../ui/AppButton.vue";
import AppConfirmDialog from "../ui/AppConfirmDialog.vue";
import AppModalShell from "../ui/AppModalShell.vue";
import AppStatusTag from "../ui/AppStatusTag.vue";
import type { MembershipPlan } from "../../../types/api";
import type { PlanPayload } from "../../../composables/usePlans";

type PlanFormErrors = Record<keyof PlanPayload, string[]>;
type ApiFormError = {
  data?: {
    message?: string;
    errors?: Record<string, string[]>;
  };
};

const props = defineProps<{
  modelValue: boolean;
  plan?: MembershipPlan | null;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  saved: [];
  deleted: [];
}>();

const { user } = useAuth();
const { create, update, remove } = usePlans();

const internalOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit("update:modelValue", value),
});

const isEdit = computed(() => Boolean(props.plan?.id));
const loading = ref(false);
const deleteLoading = ref(false);
const confirmDeleteOpen = ref(false);
const errorMessage = ref("");

const statusOptions: PlanPayload["status"][] = ["active", "inactive"];
const durationTypes: PlanPayload["duration_type"][] = [
  "day",
  "week",
  "month",
  "year",
  "session",
];
const nullableFields: Array<keyof PlanPayload> = [
  "branch_id",
  "description",
  "session_limit",
  "freeze_limit_days",
];

const defaultForm = (): PlanPayload => ({
  tenant_id: user.value?.tenant_id ?? 0,
  branch_id: user.value?.branch_id ?? null,
  name: "",
  description: "",
  duration_type: "month",
  duration_value: 1,
  price: 0,
  session_limit: null,
  freeze_limit_days: null,
  status: "active",
});

const form = reactive<PlanPayload>(defaultForm());

const createErrors = (): PlanFormErrors => ({
  tenant_id: [],
  branch_id: [],
  name: [],
  description: [],
  duration_type: [],
  duration_value: [],
  price: [],
  session_limit: [],
  freeze_limit_days: [],
  status: [],
});

const errors = reactive<PlanFormErrors>(createErrors());

const clearErrors = () => {
  errorMessage.value = "";
  Object.assign(errors, createErrors());
};

const resetForm = () => {
  Object.assign(form, defaultForm());
  clearErrors();
};

const fillForm = (plan: MembershipPlan) => {
  Object.assign(form, {
    tenant_id: plan.tenant_id,
    branch_id: plan.branch_id ?? null,
    name: plan.name,
    description: plan.description ?? "",
    duration_type: plan.duration_type,
    duration_value: plan.duration_value,
    price: Number(plan.price),
    session_limit: plan.session_limit ?? null,
    freeze_limit_days: plan.freeze_limit_days ?? null,
    status: plan.status,
  });
};

watch(
  () => props.modelValue,
  (open) => {
    if (!open) return;

    if (props.plan) {
      clearErrors();
      fillForm(props.plan);
      return;
    }

    resetForm();
  },
);

const closeDialog = () => {
  internalOpen.value = false;
};

const normalizePayload = (): PlanPayload => {
  const payload: PlanPayload = {
    ...form,
    tenant_id: Number(form.tenant_id),
    branch_id: form.branch_id ? Number(form.branch_id) : null,
    duration_value: Number(form.duration_value),
    price: Number(form.price),
    session_limit: form.session_limit ? Number(form.session_limit) : null,
    freeze_limit_days: form.freeze_limit_days
      ? Number(form.freeze_limit_days)
      : null,
  };
  const mutablePayload = payload as Record<
    string,
    string | number | null | undefined
  >;

  for (const field of nullableFields) {
    if (payload[field] === "") {
      mutablePayload[field] = null;
    }
  }

  return payload;
};

const assignBackendErrors = (backendErrors?: Record<string, string[]>) => {
  if (!backendErrors) return;

  for (const [field, messages] of Object.entries(backendErrors)) {
    if (field in errors) {
      errors[field as keyof PlanFormErrors] = messages;
    }
  }
};

const submitForm = async () => {
  loading.value = true;
  clearErrors();

  try {
    const payload = normalizePayload();

    if (isEdit.value && props.plan) {
      await update(props.plan.id, payload);
    } else {
      await create(payload);
    }

    emit("saved");
    closeDialog();
  } catch (error) {
    const typedError = error as ApiFormError;

    errorMessage.value = typedError.data?.message ?? "Unable to save plan.";
    assignBackendErrors(typedError.data?.errors);
  } finally {
    loading.value = false;
  }
};

const deletePlan = async () => {
  if (!props.plan) {
    confirmDeleteOpen.value = false;
    return;
  }

  deleteLoading.value = true;
  clearErrors();

  try {
    await remove(props.plan.id);
    emit("deleted");
    confirmDeleteOpen.value = false;
    closeDialog();
  } catch (error) {
    const typedError = error as ApiFormError;

    errorMessage.value = typedError.data?.message ?? "Unable to delete plan.";
  } finally {
    deleteLoading.value = false;
  }
};

const showTenantField = computed(() => !user.value?.tenant_id);
const showBranchField = computed(() => !user.value?.branch_id);
const showScopeFields = computed(
  () => showTenantField.value || showBranchField.value,
);

const dialogTitle = computed(() =>
  isEdit.value ? "Edit membership plan" : "Create membership plan",
);

const dialogDescription = computed(() =>
  isEdit.value
    ? "Refine pricing and duration without losing access to save or delete actions."
    : "Create a sellable membership offer using the shared CRUD modal pattern.",
);

const heroTitle = computed(
  () => form.name || (isEdit.value ? "Plan profile" : "New plan"),
);

const planInitials = computed(() => {
  const words = form.name.trim().split(/\s+/).filter(Boolean);

  if (!words.length) {
    return "NP";
  }

  return words
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join("");
});

const durationLabel = computed(
  () => `${form.duration_value} ${form.duration_type}`,
);

const formattedPrice = computed(() =>
  new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    maximumFractionDigits: 2,
  }).format(Number(form.price || 0)),
);

const deletePrompt = computed(() => {
  const planName = form.name.trim() || "this plan";

  return `Delete ${planName}? This action cannot be undone.`;
});
</script>

<style scoped>
.plan-form-hero {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  border: 1px solid var(--gym-border);
  border-radius: 20px;
  background: linear-gradient(
    135deg,
    rgba(16, 185, 129, 0.08),
    rgba(255, 255, 255, 0.94)
  );
}

.plan-form-hero__copy {
  min-width: 0;
}

.plan-form-hero__meta {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 10px;
}

@media (max-width: 640px) {
  .plan-form-hero {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
