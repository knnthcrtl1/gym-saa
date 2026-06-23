<template>
  <AppModalShell
    v-model="internalOpen"
    :eyebrow="isEdit ? 'Edit branch' : 'Add branch'"
    :title="dialogTitle"
    :description="dialogDescription"
    :max-width="820"
    @close="closeDialog"
  >
    <div class="section-stack">
      <div class="entity-form-hero">
        <div class="surface-avatar entity-form-hero__avatar">
          {{ branchInitials }}
        </div>
        <div class="entity-form-hero__copy">
          <div class="text-h6 font-weight-bold">{{ heroTitle }}</div>
          <div class="entity-form-hero__meta">
            <span class="surface-pill">
              <Icon name="lucide:map-pinned" size="16" />
              {{ form.code || "No branch code" }}
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
            <h2 class="section-panel__title">Branch details</h2>
            <p class="section-panel__body">
              Configure the location identity, tenant, and contact information.
            </p>
          </div>
        </div>

        <v-row>
          <v-col v-if="showTenantField" cols="12" md="6">
            <v-select
              v-model="form.tenant_id"
              :items="tenantOptions"
              item-title="label"
              item-value="value"
              label="Tenant"
              variant="outlined"
              :loading="tenantLoading"
              :error-messages="errors.tenant_id"
            />
          </v-col>

          <v-col cols="12" :md="showTenantField ? 6 : 8">
            <v-text-field
              v-model="form.name"
              label="Branch name"
              variant="outlined"
              :error-messages="errors.name"
            />
          </v-col>

          <v-col cols="12" :md="showTenantField ? 6 : 4">
            <v-select
              v-model="form.status"
              :items="statusOptions"
              label="Status"
              variant="outlined"
              :error-messages="errors.status"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.code"
              label="Branch code"
              variant="outlined"
              :error-messages="errors.code"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.email"
              label="Email"
              type="email"
              variant="outlined"
              :error-messages="errors.email"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.phone"
              label="Phone"
              variant="outlined"
              :error-messages="errors.phone"
            />
          </v-col>

          <v-col cols="12">
            <v-textarea
              v-model="form.address"
              label="Address"
              rows="3"
              variant="outlined"
              :error-messages="errors.address"
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
        Delete branch
      </AppButton>
    </template>

    <template #footer>
      <AppButton tone="neutral" appearance="text" @click="closeDialog">
        Cancel
      </AppButton>
      <AppButton tone="primary" :loading="loading" @click="submitForm">
        {{ isEdit ? "Save changes" : "Create branch" }}
      </AppButton>
    </template>
  </AppModalShell>

  <AppConfirmDialog
    v-model="confirmDeleteOpen"
    title="Delete branch"
    :message="deletePrompt"
    confirm-text="Delete"
    tone="danger"
    :loading="deleteLoading"
    @confirm="deleteBranch"
  />
</template>

<script setup lang="ts">
import { useTenants } from "../../../composables/useTenants";
import type { Branch, Tenant } from "../../../types/api";
import type { BranchPayload } from "../../../composables/useBranches";
import AppButton from "../ui/AppButton.vue";
import AppConfirmDialog from "../ui/AppConfirmDialog.vue";
import AppModalShell from "../ui/AppModalShell.vue";
import AppStatusTag from "../ui/AppStatusTag.vue";

type BranchFormErrors = Record<keyof BranchPayload, string[]>;
type ApiFormError = {
  data?: {
    message?: string;
    errors?: Record<string, string[]>;
  };
};

const props = defineProps<{
  modelValue: boolean;
  branch?: Branch | null;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  saved: [];
  deleted: [];
}>();

const { user } = useAuth();
const { list: listTenants } = useTenants();
const { create, update, remove } = useBranches();

const internalOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit("update:modelValue", value),
});

const isEdit = computed(() => Boolean(props.branch?.id));
const loading = ref(false);
const deleteLoading = ref(false);
const tenantLoading = ref(false);
const confirmDeleteOpen = ref(false);
const errorMessage = ref("");
const tenantOptions = ref<Array<{ label: string; value: number }>>([]);

const statusOptions: BranchPayload["status"][] = ["active", "inactive"];
const nullableFields: Array<keyof BranchPayload> = [
  "code",
  "email",
  "phone",
  "address",
];

const defaultForm = (): BranchPayload => ({
  tenant_id: user.value?.tenant_id ?? 0,
  name: "",
  code: "",
  email: "",
  phone: "",
  address: "",
  status: "active",
});

const form = reactive<BranchPayload>(defaultForm());

const createErrors = (): BranchFormErrors => ({
  tenant_id: [],
  name: [],
  code: [],
  email: [],
  phone: [],
  address: [],
  status: [],
});

const errors = reactive<BranchFormErrors>(createErrors());

const showTenantField = computed(() => user.value?.role === "super_admin");

const clearErrors = () => {
  errorMessage.value = "";
  Object.assign(errors, createErrors());
};

const resetForm = () => {
  Object.assign(form, defaultForm());
  clearErrors();
};

const fillForm = (branch: Branch) => {
  Object.assign(form, {
    tenant_id: branch.tenant_id,
    name: branch.name,
    code: branch.code ?? "",
    email: branch.email ?? "",
    phone: branch.phone ?? "",
    address: branch.address ?? "",
    status: branch.status,
  });
};

const loadTenantOptions = async () => {
  if (!showTenantField.value) {
    return;
  }

  tenantLoading.value = true;

  try {
    const response = await listTenants({ per_page: 100 });
    tenantOptions.value = response.data.map((tenant: Tenant) => ({
      label: tenant.name,
      value: tenant.id,
    }));
  } finally {
    tenantLoading.value = false;
  }
};

watch(
  () => props.modelValue,
  async (open) => {
    if (!open) {
      return;
    }

    await loadTenantOptions();

    if (props.branch) {
      clearErrors();
      fillForm(props.branch);
      return;
    }

    resetForm();
  },
);

const closeDialog = () => {
  internalOpen.value = false;
};

const normalizePayload = (): BranchPayload => {
  const payload: BranchPayload = {
    ...form,
    tenant_id: showTenantField.value
      ? Number(form.tenant_id)
      : Number(user.value?.tenant_id ?? form.tenant_id),
  };
  const mutablePayload = payload as Record<string, string | number | null>;

  for (const field of nullableFields) {
    if (payload[field] === "") {
      mutablePayload[field] = null;
    }
  }

  return payload;
};

const assignBackendErrors = (backendErrors?: Record<string, string[]>) => {
  if (!backendErrors) {
    return;
  }

  for (const [field, messages] of Object.entries(backendErrors)) {
    if (field in errors) {
      errors[field as keyof BranchFormErrors] = messages;
    }
  }
};

const submitForm = async () => {
  loading.value = true;
  clearErrors();

  try {
    const payload = normalizePayload();

    if (props.branch?.id) {
      await update(props.branch.id, payload);
    } else {
      await create(payload);
    }

    emit("saved");
    closeDialog();
  } catch (error) {
    const typedError = error as ApiFormError;

    assignBackendErrors(typedError.data?.errors);
    errorMessage.value =
      typedError.data?.message ?? "Unable to save branch details.";
  } finally {
    loading.value = false;
  }
};

const deleteBranch = async () => {
  if (!props.branch?.id) {
    confirmDeleteOpen.value = false;
    return;
  }

  deleteLoading.value = true;
  errorMessage.value = "";

  try {
    await remove(props.branch.id);
    confirmDeleteOpen.value = false;
    emit("deleted");
    closeDialog();
  } catch (error) {
    const typedError = error as ApiFormError;

    errorMessage.value = typedError.data?.message ?? "Unable to delete branch.";
  } finally {
    deleteLoading.value = false;
  }
};

const dialogTitle = computed(() =>
  isEdit.value ? form.name || "Edit branch" : "Create branch",
);

const dialogDescription = computed(() =>
  isEdit.value
    ? "Update branch identity, tenant assignment, and contact details."
    : "Add a branch and assign it to the correct tenant before staff start using it.",
);

const heroTitle = computed(() => form.name || "New branch");

const branchInitials = computed(() => {
  const words = (form.name || "New Branch").split(/\s+/).filter(Boolean);

  return words
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join("");
});

const deletePrompt = computed(() =>
  props.branch
    ? `Delete ${props.branch.name}? This action cannot be undone.`
    : "Delete this branch? This action cannot be undone.",
);
</script>

<style scoped>
.entity-form-hero {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  border-radius: 20px;
  background: linear-gradient(
    135deg,
    rgba(255, 159, 67, 0.12),
    rgba(255, 255, 255, 0.92)
  );
  border: 1px solid var(--gym-border);
}

.entity-form-hero__avatar {
  width: 60px;
  height: 60px;
  font-size: 1rem;
}

.entity-form-hero__copy {
  display: grid;
  gap: 8px;
}

.entity-form-hero__meta {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
</style>
