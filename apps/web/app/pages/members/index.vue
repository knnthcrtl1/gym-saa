<template>
  <div>
    <div class="d-flex justify-space-between align-center mb-4">
      <h1 class="text-h5 font-weight-bold">Members</h1>
      <v-btn color="primary">Add Member</v-btn>
    </div>

    <v-card rounded="xl">
      <v-table>
        <thead>
          <tr>
            <th>Member Code</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="member in members" :key="member.id">
            <td>{{ member.member_code }}</td>
            <td>{{ member.first_name }} {{ member.last_name }}</td>
            <td>{{ member.email || "-" }}</td>
            <td>{{ member.phone || "-" }}</td>
            <td>{{ member.status }}</td>
          </tr>
        </tbody>
      </v-table>
    </v-card>
  </div>
</template>

<script setup lang="ts">
const { data } = await useMembers();

const members = computed(() => data.value?.data ?? []);
</script>
