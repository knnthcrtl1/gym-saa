<template>
  <div>
    <div class="d-flex justify-space-between align-center mb-4">
      <h1 class="text-h5 font-weight-bold">Subscriptions</h1>
      <v-btn color="primary">Add Subscription</v-btn>
    </div>

    <v-card rounded="xl">
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
            <td>{{ subscription.payment_status }}</td>
            <td>{{ subscription.status }}</td>
          </tr>
        </tbody>
      </v-table>
    </v-card>
  </div>
</template>

<script setup lang="ts">
const { data } = await useSubscriptions();

const subscriptions = computed(() => data.value?.data ?? []);
</script>
