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
        <v-chip color="primary" variant="tonal">
          {{ pagination.total }} loaded
        </v-chip>
        <v-text-field
          v-model="search"
          density="compact"
          variant="outlined"
          hide-details
          placeholder="Search members"
          class="members-search"
          @keyup.enter="loadMembers"
        />
        <v-btn color="primary" :loading="loading" @click="openCreate">
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
        <v-alert v-if="errorMessage" type="error" variant="tonal" class="mb-4">
          {{ errorMessage }}
        </v-alert>

        <div class="table-toolbar mb-4">
          <div>
            <div class="panel-label">Roster table</div>
            <div class="text-h6 mt-2">Current member list</div>
          </div>
          <v-btn variant="outlined" :loading="loading" @click="loadMembers">
            Refresh
          </v-btn>
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
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="6" class="text-center py-6">Loading...</td>
              </tr>

              <tr v-else-if="members.length === 0">
                <td colspan="6" class="text-center py-6">No members found.</td>
              </tr>

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
                <td class="text-right">
                  <v-btn
                    size="small"
                    variant="text"
                    color="primary"
                    @click="openEdit(member)"
                  >
                    Edit
                  </v-btn>
                  <v-btn
                    size="small"
                    variant="text"
                    color="error"
                    :loading="deletingId === member.id"
                    @click="deleteMember(member.id)"
                  >
                    Delete
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </v-table>
        </div>
      </v-card-text>
    </v-card>

    <MemberFormDialog
      v-model="dialogOpen"
      :member="selectedMember"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup lang="ts">
import type { Member } from "../../../types/api";

type ApiPageError = {
  data?: {
    message?: string;
  };
};

definePageMeta({
  middleware: ["auth"],
});

const { list, remove } = useMembers();

const loading = ref(false);
const members = ref<Member[]>([]);
const search = ref("");
const dialogOpen = ref(false);
const selectedMember = ref<Member | null>(null);
const deletingId = ref<number | null>(null);
const errorMessage = ref("");
const pagination = reactive({
  total: 0,
  current_page: 1,
  last_page: 1,
});

const loadMembers = async () => {
  loading.value = true;
  errorMessage.value = "";

  try {
    const response = await list({
      search: search.value || undefined,
    });

    members.value = response.data;
    pagination.total = response.total;
    pagination.current_page = response.current_page;
    pagination.last_page = response.last_page;
  } catch (error) {
    const typedError = error as ApiPageError;

    errorMessage.value = typedError.data?.message ?? "Unable to load members.";
  } finally {
    loading.value = false;
  }
};

const openCreate = () => {
  selectedMember.value = null;
  dialogOpen.value = true;
};

const openEdit = (member: Member) => {
  selectedMember.value = member;
  dialogOpen.value = true;
};

const handleSaved = async () => {
  await loadMembers();
};

const deleteMember = async (id: number) => {
  if (!window.confirm("Delete this member?")) {
    return;
  }

  deletingId.value = id;
  errorMessage.value = "";

  try {
    await remove(id);
    await loadMembers();
  } catch (error) {
    const typedError = error as ApiPageError;

    errorMessage.value = typedError.data?.message ?? "Unable to delete member.";
  } finally {
    deletingId.value = null;
  }
};

await loadMembers();

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

<style scoped>
.members-search {
  min-width: 220px;
}
</style>
