<template>
  <div>
    <div class="d-flex justify-space-between align-center mb-4">
      <h1 class="text-h5 font-weight-bold">Membership Plans</h1>
      <v-btn color="primary">Add Plan</v-btn>
    </div>

    <v-card rounded="xl">
      <v-table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Duration</th>
            <th>Price</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="plan in plans" :key="plan.id">
            <td>{{ plan.name }}</td>
            <td>{{ plan.duration_value }} {{ plan.duration_type }}</td>
            <td>PHP {{ plan.price }}</td>
            <td>{{ plan.status }}</td>
          </tr>
        </tbody>
      </v-table>
    </v-card>
  </div>
</template>

<script setup lang="ts">
const { data } = await usePlans();

const plans = computed(() => data.value?.data ?? []);
</script>
