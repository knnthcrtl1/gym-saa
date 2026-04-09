<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-4">
      <h1 class="text-h5 font-weight-bold">Members</h1>
      <v-btn color="primary">Add Member</v-btn>
    </div>

    <v-card rounded="xl">
      <v-table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="member in members" :key="member.id">
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
type Member = {
  id: number;
  first_name: string;
  last_name: string;
  email?: string | null;
  phone?: string | null;
  status: string;
};

const config = useRuntimeConfig();

const { data } = await useFetch<{ data: Member[] }>(
  `${config.public.apiBase}/members`,
  {
    credentials: "include",
  },
);

const members = computed(() => data.value?.data ?? []);
</script>