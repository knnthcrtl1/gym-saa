<template>
  <v-dialog v-model="internalOpen" max-width="820">
    <v-card rounded="xl">
      <v-card-title class="d-flex justify-space-between align-center">
        <span class="text-h6 font-weight-bold">
          {{ isEdit ? "Edit Member" : "Add Member" }}
        </span>

        <v-btn icon="mdi-close" variant="text" @click="closeDialog" />
      </v-card-title>

      <v-divider />

      <v-card-text class="pt-4">
        <v-alert v-if="errorMessage" type="error" variant="tonal" class="mb-4">
          {{ errorMessage }}
        </v-alert>

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
              v-model="form.qr_code_value"
              label="QR Code Value"
              variant="outlined"
              :error-messages="errors.qr_code_value"
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

          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.joined_at"
              label="Joined At"
              type="date"
              variant="outlined"
              :error-messages="errors.joined_at"
            />
          </v-col>

          <v-col cols="12" md="3">
            <v-text-field
              v-model.number="form.tenant_id"
              label="Tenant ID"
              type="number"
              variant="outlined"
              :error-messages="errors.tenant_id"
            />
          </v-col>

          <v-col cols="12" md="3">
            <v-text-field
              v-model.number="form.branch_id"
              label="Branch ID"
              type="number"
              variant="outlined"
              :error-messages="errors.branch_id"
            />
          </v-col>
        </v-row>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
        <v-btn color="primary" :loading="loading" @click="submitForm">
          {{ isEdit ? "Update" : "Create" }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
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
}>();

const { user } = useAuth();
const { create, update } = useMembers();

const internalOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit("update:modelValue", value),
});

const isEdit = computed(() => Boolean(props.member?.id));
const loading = ref(false);
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
</script>
