<template>
  <div class="app-page">
    <div class="page-header">
      <div>
        <div class="page-header__eyebrow">Member records</div>
        <h1 class="page-header__title">Members</h1>
        <p class="page-header__body">
          Active roster with status and contact details.
        </p>
      </div>

      <div class="toolbar-actions">
        <v-chip color="accent" variant="tonal"
          >{{ members.length }} loaded</v-chip
        >
        <v-btn color="primary">
          <Icon name="lucide:plus" size="18" class="mr-2" />
          Add member
        </v-btn>
      </div>
    </div>

    <div class="metric-grid">
      <v-row>
        <v-col cols="12" md="4">
          <v-card class="content-panel">
            <v-card-text>
              <div class="panel-label">Active</div>
              <div class="stat-card__value">{{ activeCount }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card class="content-panel">
            <v-card-text>
              <div class="panel-label">Blocked</div>
              <div class="stat-card__value">{{ blockedCount }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card class="content-panel">
            <v-card-text>
              <div class="panel-label">With email</div>
              <div class="stat-card__value">{{ contactableCount }}</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <v-card class="table-panel">
      <v-card-text>
        <div class="table-toolbar mb-4">
          <div>
            <div class="panel-label">Roster table</div>
            <div class="text-h6 mt-2">Current member list</div>
          </div>
          <v-btn color="accent" variant="outlined">Filters next</v-btn>
        </div>

        <div class="table-scroll">
          <v-table>
            <thead>
              <tr>
                <th>Member code</th>
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
                <td>
                  <span :class="statusClass(member.status)">
                    {{ member.status }}
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
const { data } = await useMembers();

const members = computed(() => data.value?.data ?? []);

const activeCount = computed(
  () => members.value.filter((member) => member.status === "active").length,
);

const blockedCount = computed(
  () => members.value.filter((member) => member.status === "blocked").length,
);

const contactableCount = computed(
  () => members.value.filter((member) => Boolean(member.email)).length,
);

const statusClass = (status: string) => `status-chip status-chip--${status}`;
</script>
