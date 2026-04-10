<template>
  <div class="app-page">
    <div class="page-header">
      <div>
        <div class="page-header__eyebrow">Membership lifecycle</div>
        <h1 class="page-header__title">Subscriptions</h1>
        <p class="page-header__body">
          Track membership periods, payment state, and expirations.
        </p>
      </div>

      <div class="toolbar-actions">
        <v-chip color="accent" variant="tonal">{{ activeCount }} active</v-chip>
        <v-btn color="primary">
          <Icon name="lucide:plus" size="18" class="mr-2" />
          Add subscription
        </v-btn>
      </div>
    </div>

    <v-card class="table-panel">
      <v-card-text>
        <div class="table-toolbar mb-4">
          <div>
            <div class="panel-label">Subscription book</div>
            <div class="text-h6 mt-2">Current enrollment state</div>
          </div>
          <v-btn color="accent" variant="outlined" to="/payments"
            >Payments queue</v-btn
          >
        </div>

        <div class="table-scroll">
          <v-table>
            <thead>
              <tr>
                <th>Member</th>
                <th>Plan</th>
                <th>Start</th>
                <th>End</th>
                <th>Payment</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="subscription in subscriptions" :key="subscription.id">
                <td>
                  {{ subscription.member?.first_name }}
                  {{ subscription.member?.last_name }}
                </td>
                <td>{{ subscription.membership_plan?.name }}</td>
                <td>{{ subscription.start_date }}</td>
                <td>{{ subscription.end_date }}</td>
                <td>
                  <span :class="statusClass(subscription.payment_status)">
                    {{ subscription.payment_status }}
                  </span>
                </td>
                <td>
                  <span :class="statusClass(subscription.status)">
                    {{ subscription.status }}
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
const { data } = await useSubscriptions();

const subscriptions = computed(() => data.value?.data ?? []);

const activeCount = computed(
  () => subscriptions.value.filter((item) => item.status === "active").length,
);

const statusClass = (status: string) => `status-chip status-chip--${status}`;
</script>
