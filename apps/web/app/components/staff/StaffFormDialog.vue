<template>
  <AppModalShell
    v-model="internalOpen"
    :eyebrow="isEdit ? 'Edit staff account' : 'Add staff account'"
    :title="dialogTitle"
    :description="dialogDescription"
    :max-width="960"
    @close="closeDialog"
  >
    <div class="section-stack">
      <div class="member-form-hero">
        <div class="surface-avatar member-form-hero__avatar">
          {{ initials }}
        </div>
        <div class="member-form-hero__copy">
          <div class="text-h6 font-weight-bold">{{ heroTitle }}</div>
          <div class="member-form-hero__meta">
            <span class="surface-pill">
              <Icon name="lucide:shield-check" size="16" />
              {{
                form.role === "gym_admin"
                  ? "Gym admin"
                  : roleLabel(form.staff_role)
              }}
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
            <h2 class="section-panel__title">Account profile</h2>
            <p class="section-panel__body">
              Identity and login details for this staff account.
            </p>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.name"
              label="Full name"
              variant="outlined"
              :error-messages="errors.name"
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
              v-model="form.password"
              :label="isEdit ? 'Reset password' : 'Password'"
              type="password"
              variant="outlined"
              :hint="
                isEdit ? 'Leave blank to keep the current password.' : undefined
              "
              persistent-hint
              :error-messages="errors.password"
            />
          </v-col>

          <v-col cols="12" md="3">
            <v-select
              v-model="form.status"
              :items="statusOptions"
              label="Status"
              variant="outlined"
              :error-messages="errors.status"
            />
          </v-col>

          <v-col cols="12" md="3">
            <v-select
              v-model="form.branch_id"
              :items="branchOptions"
              item-title="label"
              item-value="value"
              label="Branch"
              variant="outlined"
              clearable
              :loading="optionsLoading"
              :error-messages="errors.branch_id"
            />
          </v-col>
        </v-row>
      </section>

      <section class="section-panel">
        <div class="section-panel__header">
          <div>
            <h2 class="section-panel__title">Role and access</h2>
            <p class="section-panel__body">
              Set the staff role and tune which modules this account can use.
            </p>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="4">
            <v-select
              v-model="form.role"
              :items="roleOptions"
              item-title="label"
              item-value="value"
              label="System role"
              variant="outlined"
              :error-messages="errors.role"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-select
              v-model="form.staff_role"
              :items="staffRoleOptions"
              item-title="label"
              item-value="value"
              label="Staff role"
              variant="outlined"
              :error-messages="errors.staff_role"
            />
          </v-col>

          <v-col cols="12" md="4">
            <AppButton
              tone="neutral"
              appearance="outline"
              class="staff-form__reset-button"
              @click="resetPermissionsToDefault"
            >
              Reset default permissions
            </AppButton>
          </v-col>

          <v-col cols="12">
            <v-select
              v-model="form.permissions"
              :items="permissionOptions"
              item-title="label"
              item-value="value"
              label="Permissions"
              variant="outlined"
              chips
              multiple
              closable-chips
              :error-messages="errors.permissions"
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
        Delete account
      </AppButton>
    </template>

    <template #footer>
      <AppButton tone="neutral" appearance="text" @click="closeDialog">
        Cancel
      </AppButton>
      <AppButton tone="primary" :loading="loading" @click="submitForm">
        {{ isEdit ? "Save changes" : "Create staff account" }}
      </AppButton>
    </template>
  </AppModalShell>

  <AppConfirmDialog
    v-model="confirmDeleteOpen"
    title="Delete staff account"
    :message="deletePrompt"
    confirm-text="Delete"
    tone="danger"
    :loading="deleteLoading"
    @confirm="deleteStaff"
  />
</template>

<script setup lang="ts">
import AppButton from "../ui/AppButton.vue";
import AppConfirmDialog from "../ui/AppConfirmDialog.vue";
import AppModalShell from "../ui/AppModalShell.vue";
import AppStatusTag from "../ui/AppStatusTag.vue";
import type { Branch, PaginatedResponse, StaffUser } from "../../../types/api";
import { useStaff, type StaffPayload } from "../../../composables/useStaff";
import {
  permissionOptions,
  resolveDefaultPermissions,
} from "../../../composables/useAuthorization";

type StaffFormErrors = {
  tenant_id: string[];
  branch_id: string[];
  name: string[];
  email: string[];
  password: string[];
  role: string[];
  staff_role: string[];
  status: string[];
  permissions: string[];
};

type ApiFormError = {
  data?: {
    message?: string;
    errors?: Record<string, string[]>;
  };
};

type BranchOption = {
  label: string;
  value: number;
};

const props = defineProps<{
  modelValue: boolean;
  staff?: StaffUser | null;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  saved: [];
  deleted: [];
}>();

const { user } = useAuth();
const { api } = useApi();
const { create, update, remove } = useStaff();

const internalOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit("update:modelValue", value),
});

const isEdit = computed(() => Boolean(props.staff?.id));
const loading = ref(false);
const deleteLoading = ref(false);
const optionsLoading = ref(false);
const errorMessage = ref("");
const confirmDeleteOpen = ref(false);
const branchOptions = ref<BranchOption[]>([]);

const roleOptions = [
  { label: "Gym admin", value: "gym_admin" },
  { label: "Staff", value: "staff" },
] as const;

const staffRoleOptions = [
  { label: "Owner", value: "owner" },
  { label: "Manager", value: "manager" },
  { label: "Front desk", value: "front_desk" },
  { label: "Trainer", value: "trainer" },
] as const;

const statusOptions: StaffPayload["status"][] = ["active", "inactive"];

const defaultForm = (): StaffPayload & { password: string } => ({
  tenant_id: user.value?.tenant_id ?? 0,
  branch_id: user.value?.branch_id ?? null,
  name: "",
  email: "",
  password: "",
  role: "staff",
  staff_role: "front_desk",
  status: "active",
  permissions: resolveDefaultPermissions("staff", "front_desk"),
});

const form = reactive(defaultForm());

const createErrors = (): StaffFormErrors => ({
  tenant_id: [],
  branch_id: [],
  name: [],
  email: [],
  password: [],
  role: [],
  staff_role: [],
  status: [],
  permissions: [],
});

const errors = reactive(createErrors());

const clearErrors = () => {
  errorMessage.value = "";
  Object.assign(errors, createErrors());
};

const resetForm = () => {
  Object.assign(form, defaultForm());
  clearErrors();
};

const roleLabel = (value?: string | null) =>
  value ? value.replace(/_/g, " ") : "staff";

const resetPermissionsToDefault = () => {
  form.permissions = resolveDefaultPermissions(form.role, form.staff_role);
};

const fillForm = (staff: StaffUser) => {
  Object.assign(form, {
    tenant_id: staff.tenant_id ?? user.value?.tenant_id ?? 0,
    branch_id: staff.branch_id ?? null,
    name: staff.name,
    email: staff.email,
    password: "",
    role: staff.role === "gym_admin" ? "gym_admin" : "staff",
    staff_role:
      staff.role === "gym_admin" ? "owner" : (staff.staff_role ?? "front_desk"),
    status: staff.status,
    permissions: [
      ...(staff.permissions ??
        resolveDefaultPermissions(staff.role, staff.staff_role)),
    ],
  });
  clearErrors();
};

const loadBranches = async () => {
  optionsLoading.value = true;

  try {
    const response = await api<PaginatedResponse<Branch>>("/branches", {
      query: { per_page: 100 },
    });

    branchOptions.value = response.data.map((branch) => ({
      label: branch.name,
      value: branch.id,
    }));
  } finally {
    optionsLoading.value = false;
  }
};

const dialogTitle = computed(() =>
  isEdit.value ? "Update staff access" : "Create a new staff account",
);

const dialogDescription = computed(() =>
  isEdit.value
    ? "Adjust branch assignment, account status, and permissions without leaving the roster."
    : "Create a login for a manager, front desk operator, trainer, or another admin.",
);

const heroTitle = computed(() => form.name || "New team member");

const initials = computed(() => {
  const source = form.name || "SA";
  return source
    .split(" ")
    .map((part) => part[0])
    .join("")
    .toUpperCase()
    .slice(0, 2);
});

const deletePrompt = computed(
  () =>
    `Remove ${props.staff?.name || "this staff account"}? This action cannot be undone.`,
);

const applyApiErrors = (apiError: ApiFormError) => {
  errorMessage.value =
    apiError.data?.message ?? "Unable to save staff account.";

  for (const [key, messages] of Object.entries(apiError.data?.errors ?? {})) {
    const normalizedKey = key as keyof StaffFormErrors;

    if (normalizedKey in errors) {
      errors[normalizedKey] = messages;
    }
  }
};

const submitForm = async () => {
  clearErrors();
  loading.value = true;

  try {
    const payload: StaffPayload = {
      tenant_id: form.tenant_id,
      branch_id: form.branch_id,
      name: form.name,
      email: form.email,
      password: form.password || undefined,
      role: form.role,
      staff_role: form.role === "gym_admin" ? "owner" : form.staff_role,
      status: form.status,
      permissions: form.permissions,
    };

    if (isEdit.value && props.staff?.id) {
      await update(props.staff.id, payload);
    } else {
      await create(payload);
    }

    emit("saved");
    closeDialog();
  } catch (error) {
    applyApiErrors(error as ApiFormError);
  } finally {
    loading.value = false;
  }
};

const deleteStaff = async () => {
  if (!props.staff?.id) {
    return;
  }

  deleteLoading.value = true;

  try {
    await remove(props.staff.id);
    emit("deleted");
    confirmDeleteOpen.value = false;
    closeDialog();
  } catch (error) {
    applyApiErrors(error as ApiFormError);
  } finally {
    deleteLoading.value = false;
  }
};

const closeDialog = () => {
  internalOpen.value = false;
};

watch(
  () => props.modelValue,
  async (open) => {
    if (!open) {
      resetForm();
      return;
    }

    await loadBranches();

    if (props.staff) {
      fillForm(props.staff);
      return;
    }

    resetForm();
  },
  { immediate: true },
);

watch(
  () => [form.role, form.staff_role],
  () => {
    if (!isEdit.value) {
      resetPermissionsToDefault();
    }
  },
);
</script>

<style scoped>
.staff-form__reset-button {
  margin-top: 10px;
}
</style>
