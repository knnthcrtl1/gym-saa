<template>
  <div class="auth-shell">
    <v-sheet class="auth-card app-surface" rounded="xl">
      <div
        style="
          display: flex;
          align-items: center;
          gap: 10px;
          margin-bottom: 24px;
        "
      >
        <div class="app-drawer__brand-icon">
          <Icon name="lucide:dumbbell" size="20" />
        </div>
        <span style="font-size: 1.15rem; font-weight: 800">Gym SaaS</span>
      </div>

      <div class="eyebrow">Admin access</div>
      <h1 class="auth-card__title">Sign in to your account</h1>
      <p class="muted-text mb-8">
        Enter your credentials to access the gym management dashboard.
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
      </v-form>
    </v-sheet>

    <v-sheet class="auth-side app-surface" rounded="xl">
      <div class="eyebrow">Demo accounts</div>
      <div class="text-h6 font-weight-bold mt-3 mb-4">
        Quick access for testing
      </div>
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
    await navigateTo("/dashboard");
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
