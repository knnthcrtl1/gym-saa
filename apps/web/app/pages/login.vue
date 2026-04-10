<template>
  <div class="auth-shell">
    <v-sheet class="auth-card app-surface">
      <div class="eyebrow">Admin access</div>
      <h1 class="auth-card__title">Sign in to the gym control room.</h1>
      <p class="muted-text mb-8">
        Public registration is intentionally disabled for this MVP. Gym admins
        create staff accounts internally, and everyone else enters through the
        authenticated desk login.
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
        />

        <div class="toolbar-actions">
          <v-btn
            color="accent"
            variant="outlined"
            @click="showPassword = !showPassword"
          >
            {{ showPassword ? "Hide password" : "Show password" }}
          </v-btn>
          <v-spacer />
          <v-btn :loading="isSubmitting" color="primary" type="submit">
            Sign in
          </v-btn>
        </div>
      </v-form>
    </v-sheet>

    <v-sheet class="auth-side app-surface">
      <div class="eyebrow">Seeded access</div>
      <div class="text-h5 font-weight-bold mt-3 mb-4">
        Use local demo accounts
      </div>
      <div class="hero-list">
        <div class="hero-list__item">
          <div class="font-weight-bold">superadmin@gymsaas.local</div>
          <div class="muted-text mt-1">Platform-wide oversight</div>
        </div>
        <div class="hero-list__item">
          <div class="font-weight-bold">admin@demofitness.local</div>
          <div class="muted-text mt-1">Tenant-level gym administration</div>
        </div>
        <div class="hero-list__item">
          <div class="font-weight-bold">Password</div>
          <div class="muted-text mt-1">password</div>
        </div>
      </div>

      <div class="mt-8 muted-text">
        Width behavior suggestion implemented: the shell fills the screen, but
        content stays clamped for readability instead of pretending the whole
        product should be designed at exactly 1024px or 1920px.
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
