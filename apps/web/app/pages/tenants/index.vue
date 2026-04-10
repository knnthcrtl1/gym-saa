<template>
  <div class="app-page">
    <div class="page-header">
      <div>
        <div class="page-header__eyebrow">Multi-gym accounts</div>
        <h1 class="page-header__title">Tenants</h1>
        <p class="page-header__body">
          Super-admin view of all gym accounts on the platform.
        </p>
      </div>

      <div class="toolbar-actions">
        <v-chip color="accent" variant="tonal"
          >{{ tenants.length }} tenants</v-chip
        >
        <v-btn color="primary">
          <Icon name="lucide:plus" size="18" class="mr-2" />
          Add tenant
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
                <th>Slug</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tenant in tenants" :key="tenant.id">
                <td>{{ tenant.name }}</td>
                <td>{{ tenant.slug }}</td>
                <td>{{ tenant.email || "-" }}</td>
                <td>{{ tenant.phone || "-" }}</td>
                <td>
                  <span :class="statusClass(tenant.status)">
                    {{ tenant.status }}
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
const { data } = await useTenants();

const tenants = computed(() => data.value?.data ?? []);
const statusClass = (status: string) => `status-chip status-chip--${status}`;
</script>
