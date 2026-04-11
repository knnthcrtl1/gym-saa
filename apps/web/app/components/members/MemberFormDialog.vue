<template>
  <AppModalShell
    v-model="internalOpen"
    :eyebrow="isEdit ? 'Edit member' : 'Add member'"
    :title="dialogTitle"
    :description="dialogDescription"
    :max-width="920"
    @close="closeDialog"
  >
    <div class="section-stack">
      <div class="member-form-hero">
        <div class="surface-avatar member-form-hero__avatar">
          {{ memberInitials }}
        </div>
        <div class="member-form-hero__copy">
          <div class="text-h6 font-weight-bold">{{ dialogHeroTitle }}</div>
          <div class="member-form-hero__meta">
            <span class="surface-pill">
              <Icon name="lucide:id-card" size="16" />
              {{ form.member_code || "Code pending" }}
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
            <h2 class="section-panel__title">Profile</h2>
            <p class="section-panel__body">
              Core member identity details and roster status.
            </p>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.member_code"
              label="Member Code"
              variant="outlined"
              :error-messages="errors.member_code"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.first_name"
              label="First Name"
              variant="outlined"
              :error-messages="errors.first_name"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.last_name"
              label="Last Name"
              variant="outlined"
              :error-messages="errors.last_name"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.birthdate"
              label="Birthdate"
              type="date"
              variant="outlined"
              :error-messages="errors.birthdate"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-select
              v-model="form.sex"
              :items="sexOptions"
              label="Sex"
              variant="outlined"
              :error-messages="errors.sex"
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
        </v-row>
      </section>

      <section class="section-panel">
        <div class="section-panel__header">
          <div>
            <h2 class="section-panel__title">Contact</h2>
            <p class="section-panel__body">
              Primary contact details and emergency contact information.
            </p>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.email"
              label="Email"
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
              variant="outlined"
              rows="2"
              :error-messages="errors.address"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.emergency_contact_name"
              label="Emergency Contact Name"
              variant="outlined"
              :error-messages="errors.emergency_contact_name"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.emergency_contact_phone"
              label="Emergency Contact Phone"
              variant="outlined"
              :error-messages="errors.emergency_contact_phone"
            />
          </v-col>
        </v-row>
      </section>

      <section class="section-panel">
        <div class="section-panel__header">
          <div>
            <h2 class="section-panel__title">Membership setup</h2>
            <p class="section-panel__body">
              Access references and tenant or branch ownership for this member
              record.
            </p>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.joined_at"
              label="Joined At"
              type="date"
              variant="outlined"
              :error-messages="errors.joined_at"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.qr_code_value"
              label="QR Code Value"
              variant="outlined"
              :error-messages="errors.qr_code_value"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model.number="form.tenant_id"
              label="Tenant ID"
              type="number"
              variant="outlined"
              :error-messages="errors.tenant_id"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model.number="form.branch_id"
              label="Branch ID"
              type="number"
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
        Delete member
      </AppButton>
    </template>

    <template #footer>
      <AppButton tone="neutral" appearance="text" @click="closeDialog">
        Cancel
      </AppButton>
      <AppButton tone="primary" :loading="loading" @click="submitForm">
        {{ isEdit ? "Save changes" : "Create member" }}
      </AppButton>
    </template>
  </AppModalShell>

  <AppConfirmDialog
    v-model="confirmDeleteOpen"
    title="Delete member"
    :message="deletePrompt"
    confirm-text="Delete"
    tone="danger"
    :loading="deleteLoading"
    @confirm="deleteMember"
  />
</template>

<script setup lang="ts">
import AppButton from "../ui/AppButton.vue";
import AppConfirmDialog from "../ui/AppConfirmDialog.vue";
import AppModalShell from "../ui/AppModalShell.vue";
import AppStatusTag from "../ui/AppStatusTag.vue";
import type { Member } from "../../../types/api";
import type { MemberPayload } from "../../../composables/useMembers";

type MemberFormErrors = Record<keyof MemberPayload, string[]>;
type ApiFormError = {
  data?: {
    message?: string;
    errors?: Record<string, string[]>;
  };
};

const props = defineProps<{
  modelValue: boolean;
  member?: Member | null;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  saved: [];
  deleted: [];
}>();

const { user } = useAuth();
const { create, update, remove } = useMembers();

const internalOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit("update:modelValue", value),
});

const isEdit = computed(() => Boolean(props.member?.id));
const loading = ref(false);
const deleteLoading = ref(false);
const confirmDeleteOpen = ref(false);
const errorMessage = ref("");

const statusOptions: MemberPayload["status"][] = [
  "active",
  "inactive",
  "blocked",
];
const sexOptions = ["Male", "Female"];
const nullableFields: Array<keyof MemberPayload> = [
  "email",
  "phone",
  "birthdate",
  "sex",
  "address",
  "emergency_contact_name",
  "emergency_contact_phone",
  "qr_code_value",
  "joined_at",
];

const defaultForm = (): MemberPayload => ({
  tenant_id: user.value?.tenant_id ?? 1,
  branch_id: user.value?.branch_id ?? 1,
  member_code: "",
  first_name: "",
  last_name: "",
  email: "",
  phone: "",
  birthdate: "",
  sex: "",
  address: "",
  emergency_contact_name: "",
  emergency_contact_phone: "",
  qr_code_value: "",
  status: "active",
  joined_at: "",
});

const form = reactive<MemberPayload>(defaultForm());

const createErrors = (): MemberFormErrors => ({
  tenant_id: [],
  branch_id: [],
  member_code: [],
  first_name: [],
  last_name: [],
  email: [],
  phone: [],
  birthdate: [],
  sex: [],
  address: [],
  emergency_contact_name: [],
  emergency_contact_phone: [],
  qr_code_value: [],
  status: [],
  joined_at: [],
});

const errors = reactive<MemberFormErrors>(createErrors());

const clearErrors = () => {
  errorMessage.value = "";
  Object.assign(errors, createErrors());
};

const resetForm = () => {
  Object.assign(form, defaultForm());
  clearErrors();
};

const fillForm = (member: Member) => {
  Object.assign(form, {
    tenant_id: member.tenant_id,
    branch_id: member.branch_id,
    member_code: member.member_code,
    first_name: member.first_name,
    last_name: member.last_name,
    email: member.email ?? "",
    phone: member.phone ?? "",
    birthdate: member.birthdate ?? "",
    sex: member.sex ?? "",
    address: member.address ?? "",
    emergency_contact_name: member.emergency_contact_name ?? "",
    emergency_contact_phone: member.emergency_contact_phone ?? "",
    qr_code_value: member.qr_code_value ?? "",
    status: member.status ?? "active",
    joined_at: member.joined_at ?? "",
  });
};

watch(
  () => props.modelValue,
  (open) => {
    if (!open) return;

    if (props.member) {
      clearErrors();
      fillForm(props.member);
      return;
    }

    resetForm();
  },
);

const closeDialog = () => {
  internalOpen.value = false;
};

const normalizePayload = (): MemberPayload => {
  const payload: MemberPayload = {
    ...form,
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
      errors[field as keyof MemberFormErrors] = messages;
    }
  }
};

const submitForm = async () => {
  loading.value = true;
  clearErrors();

  try {
    const payload = normalizePayload();

    if (isEdit.value && props.member) {
      await update(props.member.id, payload);
    } else {
      await create(payload);
    }

    emit("saved");
    closeDialog();
  } catch (error) {
    const typedError = error as ApiFormError;

    errorMessage.value = typedError.data?.message ?? "Unable to save member.";
    assignBackendErrors(typedError.data?.errors);
  } finally {
    loading.value = false;
  }
};

const deleteMember = async () => {
  if (!props.member) {
    confirmDeleteOpen.value = false;
    return;
  }

  deleteLoading.value = true;
  clearErrors();

  try {
    await remove(props.member.id);
    emit("deleted");
    confirmDeleteOpen.value = false;
    closeDialog();
  } catch (error) {
    const typedError = error as ApiFormError;

    errorMessage.value = typedError.data?.message ?? "Unable to delete member.";
  } finally {
    deleteLoading.value = false;
  }
};

const dialogTitle = computed(() =>
  isEdit.value ? "Edit member profile" : "Create member profile",
);

const dialogDescription = computed(() =>
  isEdit.value
    ? "Update member details without losing sight of save, cancel, or delete actions."
    : "Create a new member record using the shared modal layout and pinned action bar.",
);

const dialogHeroTitle = computed(() => {
  const fullName = `${form.first_name} ${form.last_name}`.trim();

  return fullName || (isEdit.value ? "Member profile" : "New member");
});

const memberInitials = computed(() => {
  const first = form.first_name.charAt(0);
  const last = form.last_name.charAt(0);
  const initials = `${first}${last}`.trim();

  return initials ? initials.toUpperCase() : "NM";
});

const deletePrompt = computed(() => {
  const fullName =
    `${form.first_name} ${form.last_name}`.trim() || "this member";

  return `Delete ${fullName}? This action cannot be undone.`;
});
</script>

<style scoped>
.member-form-hero {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  border: 1px solid var(--gym-border);
  border-radius: 20px;
  background: linear-gradient(
    135deg,
    rgba(79, 70, 229, 0.08),
    rgba(255, 255, 255, 0.92)
  );
}

.member-form-hero__copy {
  min-width: 0;
}

.member-form-hero__meta {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 10px;
}

@media (max-width: 640px) {
  .member-form-hero {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
