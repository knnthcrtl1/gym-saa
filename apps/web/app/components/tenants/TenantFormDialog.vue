<template>
  <AppModalShell
    v-model="internalOpen"
    :eyebrow="isEdit ? 'Edit tenant' : 'Add tenant'"
    :title="dialogTitle"
    :description="dialogDescription"
    :max-width="760"
    @close="closeDialog"
  >
    <div class="section-stack">
      <div class="entity-form-hero">
        <div class="surface-avatar entity-form-hero__avatar">
          {{ tenantInitials }}
        </div>
        <div class="entity-form-hero__copy">
          <div class="text-h6 font-weight-bold">{{ heroTitle }}</div>
          <div class="entity-form-hero__meta">
            <span class="surface-pill">
              <Icon name="lucide:building-2" size="16" />
              {{ form.slug || "Pending slug" }}
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
            <h2 class="section-panel__title">Tenant details</h2>
            <p class="section-panel__body">
              Configure the gym account identity and platform status.
            </p>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="8">
            <v-text-field
              v-model="form.name"
              label="Tenant name"
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

          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.slug"
              label="Slug"
              hint="Used in unique tenant URLs and identifiers."
              persistent-hint
              variant="outlined"
              :error-messages="errors.slug"
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
        Delete tenant
      </AppButton>
    </template>

    <template #footer>
      <AppButton tone="neutral" appearance="text" @click="closeDialog">
        Cancel
      </AppButton>
      <AppButton tone="primary" :loading="loading" @click="submitForm">
        {{ isEdit ? "Save changes" : "Create tenant" }}
      </AppButton>
    </template>
  </AppModalShell>

  <AppConfirmDialog
    v-model="confirmDeleteOpen"
    title="Delete tenant"
    :message="deletePrompt"
    confirm-text="Delete"
    tone="danger"
    :loading="deleteLoading"
    @confirm="deleteTenant"
  />
</template>

<script setup lang="ts">
import type { Tenant } from "../../../types/api";
import type { TenantPayload } from "../../../composables/useTenants";
import AppButton from "../ui/AppButton.vue";
import AppConfirmDialog from "../ui/AppConfirmDialog.vue";
import AppModalShell from "../ui/AppModalShell.vue";
import AppStatusTag from "../ui/AppStatusTag.vue";

type TenantFormErrors = Record<keyof TenantPayload, string[]>;
type ApiFormError = {
  data?: {
    message?: string;
    errors?: Record<string, string[]>;
  };
};

const props = defineProps<{
  modelValue: boolean;
  tenant?: Tenant | null;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  saved: [];
  deleted: [];
}>();

const { create, update, remove } = useTenants();

const internalOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit("update:modelValue", value),
});

const isEdit = computed(() => Boolean(props.tenant?.id));
const loading = ref(false);
const deleteLoading = ref(false);
const confirmDeleteOpen = ref(false);
const errorMessage = ref("");

const statusOptions: TenantPayload["status"][] = ["active", "inactive"];
const nullableFields: Array<keyof TenantPayload> = [
  "email",
  "phone",
  "address",
];

const defaultForm = (): TenantPayload => ({
  name: "",
  slug: "",
  email: "",
  phone: "",
  address: "",
  status: "active",
});

const form = reactive<TenantPayload>(defaultForm());

const createErrors = (): TenantFormErrors => ({
  name: [],
  slug: [],
  email: [],
  phone: [],
  address: [],
  status: [],
});

const errors = reactive<TenantFormErrors>(createErrors());

const clearErrors = () => {
  errorMessage.value = "";
  Object.assign(errors, createErrors());
};

const resetForm = () => {
  Object.assign(form, defaultForm());
  clearErrors();
};

const fillForm = (tenant: Tenant) => {
  Object.assign(form, {
    name: tenant.name,
    slug: tenant.slug,
    email: tenant.email ?? "",
    phone: tenant.phone ?? "",
    address: tenant.address ?? "",
    status: tenant.status,
  });
};

watch(
  () => props.modelValue,
  (open) => {
    if (!open) {
      return;
    }

    if (props.tenant) {
      clearErrors();
      fillForm(props.tenant);
      return;
    }

    resetForm();
  },
);

const closeDialog = () => {
  internalOpen.value = false;
};

const normalizePayload = (): TenantPayload => {
  const payload: TenantPayload = {
    ...form,
  };
  const mutablePayload = payload as Record<string, string | null>;

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
      errors[field as keyof TenantFormErrors] = messages;
    }
  }
};

const submitForm = async () => {
  loading.value = true;
  clearErrors();

  try {
    const payload = normalizePayload();

    if (props.tenant?.id) {
      await update(props.tenant.id, payload);
    } else {
      await create(payload);
    }

    emit("saved");
    closeDialog();
  } catch (error) {
    const typedError = error as ApiFormError;

    assignBackendErrors(typedError.data?.errors);
    errorMessage.value =
      typedError.data?.message ?? "Unable to save tenant details.";
  } finally {
    loading.value = false;
  }
};

const deleteTenant = async () => {
  if (!props.tenant?.id) {
    confirmDeleteOpen.value = false;
    return;
  }

  deleteLoading.value = true;
  errorMessage.value = "";

  try {
    await remove(props.tenant.id);
    confirmDeleteOpen.value = false;
    emit("deleted");
    closeDialog();
  } catch (error) {
    const typedError = error as ApiFormError;

    errorMessage.value = typedError.data?.message ?? "Unable to delete tenant.";
  } finally {
    deleteLoading.value = false;
  }
};

const dialogTitle = computed(() =>
  isEdit.value ? form.name || "Edit tenant" : "Create tenant",
);

const dialogDescription = computed(() =>
  isEdit.value
    ? "Update account identity, contact details, and current status."
    : "Add a new gym account to the platform and make it available for setup.",
);

const heroTitle = computed(() => form.name || "New tenant account");

const tenantInitials = computed(() => {
  const words = (form.name || "New Tenant").split(/\s+/).filter(Boolean);

  return words
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join("");
});

const deletePrompt = computed(() =>
  props.tenant
    ? `Delete ${props.tenant.name}? This action cannot be undone.`
    : "Delete this tenant? This action cannot be undone.",
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
    var(--gym-primary-soft),
    rgba(255, 255, 255, 0.9)
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
