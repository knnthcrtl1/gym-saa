<template>
  <div class="app-page">
    <div class="page-header">
      <div>
        <div class="page-header__eyebrow">Commercial offers</div>
        <h1 class="page-header__title">Membership plans</h1>
        <p class="page-header__body">
          Manage pricing, duration, and availability for all membership offers.
        </p>
      </div>

      <div class="toolbar-actions">
        <v-chip color="primary" variant="tonal"
          >{{ activePlans }} active</v-chip
        >
        <v-btn color="primary">
          <Icon name="lucide:plus" size="18" class="mr-2" />
          Add plan
        </v-btn>
      </div>
    </div>

    <v-card class="table-panel">
      <v-card-text>
        <div class="table-toolbar mb-4">
          <div>
            <div class="panel-label">Plan library</div>
            <div class="text-h6 mt-2">Offers ready for sale</div>
          </div>
          <v-btn variant="outlined">Pricing tools next</v-btn>
        </div>

        <div class="table-scroll">
          <v-table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Duration</th>
                <th>Price</th>
                <th>Session limit</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="plan in plans" :key="plan.id">
                <td>{{ plan.name }}</td>
                <td>{{ plan.duration_value }} {{ plan.duration_type }}</td>
                <td>{{ formatCurrency(plan.price) }}</td>
                <td>{{ plan.session_limit ?? "Unlimited" }}</td>
                <td>
                  <span :class="statusClass(plan.status)">
                    {{ plan.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </v-table>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ["auth"],
});

const { data } = await usePlans();

const plans = computed(() => data.value?.data ?? []);

const activePlans = computed(
  () => plans.value.filter((plan) => plan.status === "active").length,
);

const statusClass = (status: string) => `status-chip status-chip--${status}`;

const formatCurrency = (value: string | number) =>
  new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    maximumFractionDigits: 0,
  }).format(Number(value));
</script>
