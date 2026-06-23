<template>
  <div class="app-page">
    <PageHeader
      eyebrow="Front desk flow"
      title="Attendance"
      description="Record member check-ins, confirm active access, and review today’s attendance log."
    >
      <template #actions>
        <span class="surface-pill">
          <Icon name="lucide:scan-line" size="16" />
          {{ pagination.total }} total
        </span>
        <v-text-field
          v-model="searchInput"
          density="compact"
          variant="outlined"
          hide-details
          placeholder="Search member code or name"
          class="members-search"
          prepend-inner-icon="mdi-magnify"
        />
        <v-text-field
          v-model="dateFilter"
          density="compact"
          variant="outlined"
          hide-details
          type="date"
          class="members-filter"
        />
        <AppButton tone="primary" @click="dialogOpen = true">
          <Icon name="lucide:plus" size="18" class="mr-2" />
          Manual check-in
        </AppButton>
      </template>
    </PageHeader>

    <div class="metric-grid">
      <v-row>
        <v-col cols="12" md="4">
          <v-card class="content-panel">
            <v-card-text>
              <div class="panel-label">Today</div>
              <div class="stat-card__value">{{ todayCount }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card class="content-panel">
            <v-card-text>
              <div class="panel-label">Manual</div>
              <div class="stat-card__value">{{ manualCount }}</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card class="content-panel">
            <v-card-text>
              <div class="panel-label">Unique members</div>
              <div class="stat-card__value">{{ uniqueMembersCount }}</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <TableShell
      eyebrow="Attendance log"
      title="Recent check-ins"
      description="The backend now blocks duplicate same-day entries and members with expired or unpaid subscriptions."
    >
      <template #notice>
        <v-alert v-if="errorMessage" type="error" variant="tonal">
          {{ errorMessage }}
        </v-alert>
      </template>

      <template #actions>
        <div class="toolbar-cluster toolbar-cluster--end">
          <AppButton
            tone="neutral"
            appearance="outline"
            :loading="loading"
            @click="reloadCurrentPage"
          >
            Refresh
          </AppButton>
        </div>
      </template>

      <div class="table-scroll">
        <v-table>
          <thead>
            <tr>
              <th>Member</th>
              <th>Subscription</th>
              <th>Time</th>
              <th>Source</th>
              <th>Verified by</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="text-center py-6">Loading check-ins...</td>
            </tr>

            <tr v-else-if="checkins.length === 0">
              <td colspan="5" class="text-center py-10">
                <div class="empty-state">
                  <div class="panel-label mb-2">No check-ins found</div>
                  The list updates as soon as the front desk records entries.
                </div>
              </td>
            </tr>

            <tr v-for="checkin in checkins" :key="checkin.id">
              <td>
                <div class="table-primary-cell">
                  <div class="surface-avatar surface-avatar--sm">
                    {{ initials(checkin) }}
                  </div>
                  <div>
                    <div class="table-primary-cell__title">
                      {{ memberName(checkin) }}
                    </div>
                    <div class="table-primary-cell__subtitle">
                      {{
                        checkin.member?.member_code ||
                        `Member #${checkin.member_id}`
                      }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="table-cell-muted">
                {{
                  checkin.subscription?.membership_plan?.name ||
                  `Subscription #${checkin.subscription_id}`
                }}
              </td>
              <td class="table-cell-muted">
                {{ formatDateTime(checkin.checkin_time) }}
              </td>
              <td>
                <AppStatusTag :label="checkin.source" />
              </td>
              <td class="table-cell-muted">
                {{ checkin.verifier?.name || "System" }}
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>

      <template #footer>
        <div class="members-pagination">
          <div class="table-meta">
            <span>
              Showing
              <strong
                >{{ pagination.from || 0 }}-{{ pagination.to || 0 }}</strong
              >
              of <strong>{{ pagination.total }}</strong>
            </span>
            <span>
              Page <strong>{{ pagination.current_page }}</strong> of
              <strong>{{ pagination.last_page }}</strong>
            </span>
          </div>

          <div class="toolbar-cluster toolbar-cluster--end">
            <AppButton
              tone="neutral"
              appearance="outline"
              :disabled="pagination.current_page <= 1 || loading"
              @click="changePage(pagination.current_page - 1)"
            >
              Previous
            </AppButton>
            <AppButton
              tone="primary"
              appearance="outline"
              :disabled="
                pagination.current_page >= pagination.last_page || loading
              "
              @click="changePage(pagination.current_page + 1)"
            >
              Next
            </AppButton>
          </div>
        </div>
      </template>
    </TableShell>

    <CheckinFormDialog v-model="dialogOpen" @saved="handleSaved" />
  </div>
</template>

<script setup lang="ts">
import { useCheckins } from "../../../composables/useCheckins";
import type { Checkin } from "../../../types/api";
import PageHeader from "../../components/admin/PageHeader.vue";
import CheckinFormDialog from "../../components/attendance/CheckinFormDialog.vue";
import TableShell from "../../components/admin/TableShell.vue";
import AppButton from "../../components/ui/AppButton.vue";
import AppStatusTag from "../../components/ui/AppStatusTag.vue";

type ApiPageError = {
  data?: {
    message?: string;
  };
};

definePageMeta({
  middleware: ["auth", "can"],
  permission: "attendance.view",
});

const { list } = useCheckins();

const loading = ref(false);
const dialogOpen = ref(false);
const checkins = ref<Checkin[]>([]);
const errorMessage = ref("");
const searchInput = ref("");
const search = ref("");
const dateFilter = ref(new Date().toISOString().slice(0, 10));
const pagination = reactive({
  total: 0,
  current_page: 1,
  last_page: 1,
  from: 0 as number | null,
  to: 0 as number | null,
});

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const loadCheckins = async (page = pagination.current_page) => {
  loading.value = true;
  errorMessage.value = "";

  try {
    const response = await list({
      page,
      per_page: 10,
      search: search.value || undefined,
      date: dateFilter.value || undefined,
    });

    checkins.value = response.data;
    pagination.total = response.total;
    pagination.current_page = response.current_page;
    pagination.last_page = response.last_page;
    pagination.from = response.from;
    pagination.to = response.to;
  } catch (error) {
    const typedError = error as ApiPageError;
    errorMessage.value =
      typedError.data?.message ?? "Unable to load attendance.";
  } finally {
    loading.value = false;
  }
};

const todayCount = computed(() => checkins.value.length);
const manualCount = computed(
  () => checkins.value.filter((entry) => entry.source === "manual").length,
);
const uniqueMembersCount = computed(
  () => new Set(checkins.value.map((entry) => entry.member_id)).size,
);

const memberName = (checkin: Checkin) =>
  checkin.member
    ? `${checkin.member.first_name} ${checkin.member.last_name}`
    : `Member #${checkin.member_id}`;

const initials = (checkin: Checkin) => {
  const first = checkin.member?.first_name?.[0] ?? "M";
  const last = checkin.member?.last_name?.[0] ?? "B";
  return `${first}${last}`.toUpperCase();
};

const formatDateTime = (value: string) =>
  new Intl.DateTimeFormat("en-PH", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(value));

const handleSaved = async () => {
  await loadCheckins(1);
};

const changePage = async (page: number) => {
  if (page < 1 || page > pagination.last_page || loading.value) {
    return;
  }

  await loadCheckins(page);
};

const reloadCurrentPage = async () => {
  await loadCheckins();
};

watch(searchInput, (value) => {
  if (searchTimer) {
    clearTimeout(searchTimer);
  }

  searchTimer = setTimeout(async () => {
    search.value = value.trim();
    await loadCheckins(1);
  }, 250);
});

watch(dateFilter, async () => {
  await loadCheckins(1);
});

await loadCheckins();
</script>
