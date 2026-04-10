<template>
  <div class="app-page">
    <div class="page-header">
      <div>
        <div class="page-header__eyebrow">Locations</div>
        <h1 class="page-header__title">Branches</h1>
        <p class="page-header__body">
          Manage gym locations and their contact details.
        </p>
      </div>

      <div class="toolbar-actions">
        <v-chip color="accent" variant="tonal"
          >{{ branches.length }} branches</v-chip
        >
        <v-btn color="primary">
          <Icon name="lucide:plus" size="18" class="mr-2" />
          Add branch
        </v-btn>
      </div>
    </div>

    <v-card class="table-panel">
      <v-card-text>
        <div class="table-scroll">
          <v-table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="branch in branches" :key="branch.id">
                <td>{{ branch.name }}</td>
                <td>{{ branch.code || "-" }}</td>
                <td>{{ branch.email || "-" }}</td>
                <td>{{ branch.phone || "-" }}</td>
                <td>
                  <span :class="statusClass(branch.status)">
                    {{ branch.status }}
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
const { data } = await useBranches();

const branches = computed(() => data.value?.data ?? []);
const statusClass = (status: string) => `status-chip status-chip--${status}`;
</script>
