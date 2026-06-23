<template>
  <AppModalShell
    v-model="internalOpen"
    eyebrow="Front desk flow"
    title="Record check-in"
    description="Search a member, confirm the active subscription, and record entry in one step."
    :max-width="920"
    @close="closeDialog"
  >
    <div class="section-stack">
      <v-alert v-if="errorMessage" type="error" variant="tonal">
        {{ errorMessage }}
      </v-alert>

      <section class="section-panel">
        <div class="section-panel__header">
          <div>
            <h2 class="section-panel__title">Member selection</h2>
            <p class="section-panel__body">
              Pick a member first. The API will validate active membership and
              unpaid balances before allowing entry.
            </p>
          </div>
        </div>

        <v-row>
          <v-col cols="12">
            <v-autocomplete
              v-model="form.member_id"
              :items="memberOptions"
              item-title="label"
              item-value="value"
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
              item-value="value"
              label="Subscription"
              variant="outlined"
              clearable
              :error-messages="errors.subscription_id"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-select
              v-model="form.source"
              :items="sourceOptions"
              label="Source"
              variant="outlined"
              :error-messages="errors.source"
            />
          </v-col>
        </v-row>
      </section>

      <section class="section-panel">
        <div class="section-panel__header">
          <div>
            <h2 class="section-panel__title">Selected member</h2>
            <p class="section-panel__body">
              This helps the front desk confirm they are checking in the right
              person before saving.
            </p>
          </div>
        </div>

        <div class="hero-list">
          <div class="hero-list__item">
            {{ selectedMemberLabel || "No member selected yet." }}
          </div>
          <div class="hero-list__item">
            {{
              selectedSubscriptionLabel ||
              "Backend will auto-select the latest valid subscription if none is chosen."
            }}
          </div>
        </div>
      </section>
    </div>

    <template #footer>
      <AppButton tone="neutral" appearance="text" @click="closeDialog">
        Cancel
      </AppButton>
      <AppButton tone="primary" :loading="loading" @click="submitForm">
        Record check-in
      </AppButton>
    </template>
  </AppModalShell>
</template>

<script setup lang="ts">
import AppButton from "../ui/AppButton.vue";
import AppModalShell from "../ui/AppModalShell.vue";
import type {
  Member,
  PaginatedResponse,
  Subscription,
} from "../../../types/api";
import {
  useCheckins,
  type CheckinPayload,
} from "../../../composables/useCheckins";

type CheckinErrors = {
  tenant_id: string[];
  branch_id: string[];
  member_id: string[];
  subscription_id: string[];
  source: string[];
};

type ApiFormError = {
  data?: {
    message?: string;
    errors?: Record<string, string[]>;
  };
};

type SelectOption = {
  label: string;
  value: number;
};

const props = defineProps<{
  modelValue: boolean;
}>();

const emit = defineEmits<{
  "update:modelValue": [value: boolean];
  saved: [];
}>();

const { user } = useAuth();
const { list: listMembers } = useMembers();
const { list: listSubscriptions } = useSubscriptions();
const { create } = useCheckins();

const internalOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit("update:modelValue", value),
});

const loading = ref(false);
const optionsLoading = ref(false);
const errorMessage = ref("");
const members = ref<Member[]>([]);
const subscriptions = ref<Subscription[]>([]);

const sourceOptions: CheckinPayload["source"][] = ["manual", "qr", "kiosk"];

const defaultForm = (): CheckinPayload => ({
  tenant_id: user.value?.tenant_id ?? 0,
  branch_id: user.value?.branch_id ?? 0,
  member_id: 0,
  subscription_id: null,
  source: "manual",
});

const form = reactive(defaultForm());

const createErrors = (): CheckinErrors => ({
  tenant_id: [],
  branch_id: [],
  member_id: [],
  subscription_id: [],
  source: [],
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

const memberOptions = computed<SelectOption[]>(() =>
  members.value.map((member) => ({
    label: `${member.member_code} · ${member.first_name} ${member.last_name}`,
    value: member.id,
  })),
);

const filteredSubscriptions = computed(() =>
  subscriptions.value.filter(
    (subscription) => subscription.member_id === form.member_id,
  ),
);

const subscriptionOptions = computed<SelectOption[]>(() =>
  filteredSubscriptions.value.map((subscription) => ({
    label: `${subscription.membership_plan?.name || `Subscription #${subscription.id}`} · ${subscription.status}`,
    value: subscription.id,
  })),
);

const selectedMemberLabel = computed(
  () =>
    memberOptions.value.find((option) => option.value === form.member_id)
      ?.label,
);

const selectedSubscriptionLabel = computed(
  () =>
    subscriptionOptions.value.find(
      (option) => option.value === form.subscription_id,
    )?.label,
);

const loadOptions = async () => {
  optionsLoading.value = true;

  try {
    const [memberResponse, subscriptionResponse] = await Promise.all([
      listMembers({ per_page: 100, status: "active" }),
      listSubscriptions({ per_page: 100 }),
    ]);

    members.value = memberResponse.data;
    subscriptions.value = subscriptionResponse.data;
  } finally {
    optionsLoading.value = false;
  }
};

const applyApiErrors = (apiError: ApiFormError) => {
  errorMessage.value = apiError.data?.message ?? "Unable to record check-in.";

  for (const [key, messages] of Object.entries(apiError.data?.errors ?? {})) {
    const normalizedKey = key as keyof CheckinErrors;

    if (normalizedKey in errors) {
      errors[normalizedKey] = messages;
    }
  }
};

const submitForm = async () => {
  clearErrors();
  loading.value = true;

  try {
    await create({
      tenant_id: form.tenant_id,
      branch_id: form.branch_id,
      member_id: form.member_id,
      subscription_id: form.subscription_id,
      source: form.source,
    });

    emit("saved");
    closeDialog();
  } catch (error) {
    applyApiErrors(error as ApiFormError);
  } finally {
    loading.value = false;
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

    await loadOptions();
  },
  { immediate: true },
);

watch(
  () => form.member_id,
  () => {
    form.subscription_id = null;
  },
);
</script>
