<template>
  <div class="auth-shell auth-shell--centered">
    <v-sheet class="auth-card app-surface" rounded="xl">
      <div class="auth-card__brand">
        <div class="app-drawer__brand-icon">
          <Icon name="lucide:dumbbell" size="20" />
        </div>
        <span class="auth-card__brand-text">Gym SaaS</span>
      </div>

      <div class="eyebrow">Admin access</div>
      <h1 class="auth-card__title">Sign in to your account</h1>
      <p class="muted-text mb-8">
        Enter your credentials to access the gym management dashboard, or
        request a manual onboarding demo if your gym account has not been set up
        yet.
      </p>

      <v-alert v-if="errorMessage" class="mb-6" type="error" variant="tonal">
        {{ errorMessage }}
      </v-alert>

      <v-form class="form-stack" @submit.prevent="submit">
        <v-text-field
          v-model="form.email"
          autocomplete="email"
          label="Email"
          placeholder="admin@demofitness.local"
          required
        />
        <v-text-field
          v-model="form.password"
          :type="showPassword ? 'text' : 'password'"
          autocomplete="current-password"
          label="Password"
          required
        >
          <template #append-inner>
            <v-btn
              icon
              variant="text"
              size="small"
              @click="showPassword = !showPassword"
            >
              <Icon
                :name="showPassword ? 'lucide:eye-off' : 'lucide:eye'"
                size="18"
              />
            </v-btn>
          </template>
        </v-text-field>

        <v-btn
          :loading="isSubmitting"
          color="primary"
          type="submit"
          size="large"
          block
        >
          Sign in
        </v-btn>

        <v-btn variant="outlined" size="large" block :href="requestDemoLink">
          <Icon name="lucide:messages-square" size="18" class="mr-2" />
          Request demo
        </v-btn>
      </v-form>

      <div class="auth-actions-note">
        Need access for a new gym? The super admin currently creates owner
        accounts and initial setup manually for faster MVP onboarding.
      </div>
    </v-sheet>

    <v-sheet class="auth-side app-surface" rounded="xl">
      <div class="eyebrow">Manual onboarding</div>
      <div class="text-h6 font-weight-bold mt-3 mb-4">
        Concierge setup for early gyms
      </div>
      <p class="muted-text auth-side__body">
        For now, gym owners do not self-register or check out on the website.
        The super admin creates the tenant, owner account, and first branch,
        then shares login details or an invite later.
      </p>
      <div class="hero-list">
        <div
          class="hero-list__item"
          style="display: flex; align-items: center; gap: 12px"
        >
          <div
            class="stat-card__icon stat-card__icon--purple"
            style="width: 38px; height: 38px; border-radius: 10px"
          >
            <Icon name="lucide:shield" size="18" />
          </div>
          <div>
            <div style="font-weight: 600; font-size: 0.88rem">
              superadmin@gymsaas.local
            </div>
            <div class="muted-text" style="font-size: 0.8rem">
              Platform-wide oversight
            </div>
          </div>
        </div>
        <div
          class="hero-list__item"
          style="display: flex; align-items: center; gap: 12px"
        >
          <div
            class="stat-card__icon stat-card__icon--blue"
            style="width: 38px; height: 38px; border-radius: 10px"
          >
            <Icon name="lucide:user" size="18" />
          </div>
          <div>
            <div style="font-weight: 600; font-size: 0.88rem">
              admin@demofitness.local
            </div>
            <div class="muted-text" style="font-size: 0.8rem">
              Tenant-level administration
            </div>
          </div>
        </div>
        <div
          class="hero-list__item"
          style="display: flex; align-items: center; gap: 12px"
        >
          <div
            class="stat-card__icon stat-card__icon--green"
            style="width: 38px; height: 38px; border-radius: 10px"
          >
            <Icon name="lucide:key" size="18" />
          </div>
          <div>
            <div style="font-weight: 600; font-size: 0.88rem">Password</div>
            <div class="muted-text" style="font-size: 0.8rem">password</div>
          </div>
        </div>
      </div>
    </v-sheet>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ["guest"],
  layout: false,
});

const requestDemoLink =
  "mailto:superadmin@gymsaas.local?subject=Gym%20SaaS%20Demo%20Request";
const route = useRoute();

function isSafeRedirectTarget(value: unknown): value is string {
  return (
    typeof value === "string" &&
    value.startsWith("/") &&
    !value.startsWith("//")
  );
}

function getRedirectTarget() {
  return isSafeRedirectTarget(route.query.redirect)
    ? route.query.redirect
    : "/dashboard";
}

const form = reactive({
  email: "admin@demofitness.local",
  password: "password",
});

const showPassword = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref("");

const submit = async () => {
  errorMessage.value = "";
  isSubmitting.value = true;

  try {
    const { login } = useAuth();
    await login({ ...form });
    await navigateTo(getRedirectTarget());
  } catch (error) {
    const typedError = error as {
      data?: { message?: string; errors?: Record<string, string[]> };
    };

    errorMessage.value =
      typedError.data?.errors?.email?.[0] ||
      typedError.data?.message ||
      "Unable to sign in with those credentials.";
  } finally {
    isSubmitting.value = false;
  }
};
</script>
