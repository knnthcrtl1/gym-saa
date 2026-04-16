<template>
  <div class="app-page">
    <PageHeader
      eyebrow="Administration"
      title="Audit Logs"
      description="Review system activity across payments, subscriptions, check-ins, and other operations."
    >
      <template #actions>
        <span class="surface-pill">
          <Icon name="lucide:scroll-text" size="16" />
          {{ pagination.total }} entries
        </span>
      </template>
    </PageHeader>

    <TableShell
      eyebrow="Activity trail"
      title="Recorded events"
      description="Chronological history of actions performed by staff and system processes."
    >
      <template #notice>
        <v-alert v-if="errorMessage" type="error" variant="tonal">
          {{ errorMessage }}
        </v-alert>
      </template>

      <template #actions>
        <div class="toolbar-cluster toolbar-cluster--end audit-filters">
          <v-text-field
            v-model="filters.action"
            density="compact"
            variant="outlined"
            hide-details
            placeholder="Action (e.g. payment.created)"
            class="audit-filter-field"
            @keyup.enter="applyFilters"
          />
          <v-select
            v-model="filters.auditable_type"
            density="compact"
            variant="outlined"
            hide-details
            placeholder="Entity type"
            :items="entityTypeOptions"
            clearable
            class="audit-filter-field"
            @update:model-value="applyFilters"
          />
          <v-text-field
            v-model="filters.date_from"
            density="compact"
            variant="outlined"
            hide-details
            type="date"
            label="From"
            class="audit-filter-field"
            @change="applyFilters"
          />
          <v-text-field
            v-model="filters.date_to"
            density="compact"
            variant="outlined"
            hide-details
            type="date"
            label="To"
            class="audit-filter-field"
            @change="applyFilters"
          />
          <AppButton
            tone="neutral"
            appearance="outline"
            :loading="loading"
            @click="applyFilters"
          >
            Apply
          </AppButton>
          <AppButton
            tone="neutral"
            appearance="outline"
            :loading="loading"
            @click="resetFilters"
          >
            Reset
          </AppButton>
        </div>
      </template>

      <div class="table-scroll">
        <v-table>
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Actor</th>
              <th>Action</th>
              <th>Entity</th>
              <th>Summary</th>
              <th class="text-right">Details</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="6" class="text-center py-6">
                Loading audit logs...
              </td>
            </tr>

            <tr v-else-if="logs.length === 0">
              <td colspan="6" class="text-center py-10">
                <div class="empty-state">
                  <div class="panel-label mb-2">No entries</div>
                  Audit events will appear here as actions occur across the
                  system.
                </div>
              </td>
            </tr>

            <tr v-for="log in logs" :key="log.id">
              <td class="table-cell-muted">
                {{ formatTimestamp(log.created_at) }}
              </td>
              <td>
                <span v-if="log.actor" class="table-primary-cell__title">
                  {{ log.actor.name }}
                </span>
                <span v-else class="table-cell-muted">System</span>
              </td>
              <td>
                <code class="audit-action-badge">{{ log.action }}</code>
              </td>
              <td class="table-cell-muted">
                {{ formatEntityType(log.auditable_type) }}
                #{{ log.auditable_id }}
              </td>
              <td class="table-cell-muted">
                {{ log.summary || "-" }}
              </td>
              <td class="text-right">
                <AppButton
                  v-if="log.before_state || log.after_state"
                  tone="neutral"
                  appearance="outline"
                  @click="toggleDetail(log.id)"
                >
                  {{ expandedId === log.id ? "Hide" : "View" }}
                </AppButton>
                <span v-else class="table-cell-muted">-</span>
              </td>
            </tr>
          </tbody>
        </v-table>
      </div>

      <!-- Expanded detail panel -->
      <div v-if="expandedLog" class="audit-detail-panel">
        <div class="audit-detail-panel__header">
          <strong>{{ expandedLog.action }}</strong>
          <span class="table-cell-muted">
            — {{ formatEntityType(expandedLog.auditable_type) }} #{{
              expandedLog.auditable_id
            }}
          </span>
        </div>

        <div
          v-if="expandedLog.changed_fields?.length"
          class="audit-detail-section"
        >
          <div class="panel-label">Changed fields</div>
          <div class="audit-changed-fields">
            <code
              v-for="field in expandedLog.changed_fields"
              :key="field"
              class="audit-field-chip"
            >
              {{ field }}
            </code>
          </div>
        </div>

        <v-row>
          <v-col v-if="expandedLog.before_state" cols="12" md="6">
            <div class="panel-label">Before</div>
            <pre class="audit-json-block">{{
              formatJson(expandedLog.before_state)
            }}</pre>
          </v-col>
          <v-col v-if="expandedLog.after_state" cols="12" md="6">
            <div class="panel-label">After</div>
            <pre class="audit-json-block">{{
              formatJson(expandedLog.after_state)
            }}</pre>
          </v-col>
        </v-row>
      </div>

      <template #footer>
        <div class="payments-pagination">
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
  </div>
</template>

<script setup lang="ts">
import type { AuditLog } from "../../../types/api";
import { useAuditLogs } from "../../../composables/useAuditLogs";
import PageHeader from "../../components/admin/PageHeader.vue";
import TableShell from "../../components/admin/TableShell.vue";
import AppButton from "../../components/ui/AppButton.vue";

type ApiPageError = {
  data?: {
    message?: string;
  };
};

definePageMeta({
  middleware: ["auth", "can"],
  permission: "audit_logs.view",
});

const { list } = useAuditLogs();

const loading = ref(false);
const errorMessage = ref("");
const logs = ref<AuditLog[]>([]);
const expandedId = ref<number | null>(null);

const filters = reactive({
  action: "",
  auditable_type: "" as string | null,
  date_from: "",
  date_to: "",
});

const entityTypeOptions = [
  { title: "Payment", value: "App\\Models\\Payment" },
  { title: "Subscription", value: "App\\Models\\Subscription" },
  { title: "Check-in", value: "App\\Models\\Checkin" },
  { title: "Member", value: "App\\Models\\Member" },
];

const pagination = reactive({
  total: 0,
  current_page: 1,
  last_page: 1,
  from: 0 as number | null,
  to: 0 as number | null,
});

const expandedLog = computed(() =>
  expandedId.value ? logs.value.find((l) => l.id === expandedId.value) : null,
);

const loadLogs = async (page = pagination.current_page) => {
  loading.value = true;
  errorMessage.value = "";

  try {
    const params: Record<string, unknown> = { page, per_page: 15 };

    if (filters.action) params.action = filters.action;
    if (filters.auditable_type) params.auditable_type = filters.auditable_type;
    if (filters.date_from) params.date_from = filters.date_from;
    if (filters.date_to) params.date_to = filters.date_to;

    const response = await list(params);

    logs.value = response.data;
    pagination.total = response.total;
    pagination.current_page = response.current_page;
    pagination.last_page = response.last_page;
    pagination.from = response.from;
    pagination.to = response.to;
  } catch (error) {
    const typedError = error as ApiPageError;
    errorMessage.value =
      typedError.data?.message ?? "Unable to load audit logs.";
  } finally {
    loading.value = false;
  }
};

const applyFilters = () => {
  expandedId.value = null;
  loadLogs(1);
};

const resetFilters = () => {
  filters.action = "";
  filters.auditable_type = null;
  filters.date_from = "";
  filters.date_to = "";
  expandedId.value = null;
  loadLogs(1);
};

const changePage = (page: number) => {
  if (page < 1 || page > pagination.last_page || loading.value) return;
  expandedId.value = null;
  loadLogs(page);
};

const toggleDetail = (id: number) => {
  expandedId.value = expandedId.value === id ? null : id;
};

const formatTimestamp = (value?: string | null) => {
  if (!value) return "-";
  return new Intl.DateTimeFormat("en", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(new Date(value));
};

const formatEntityType = (type: string) => {
  const parts = type.split("\\");
  return parts[parts.length - 1] || type;
};

const formatJson = (obj: Record<string, unknown>) => {
  try {
    return JSON.stringify(obj, null, 2);
  } catch {
    return String(obj);
  }
};

onMounted(() => loadLogs(1));
</script>

<style lang="scss" scoped>
.audit-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
}

.audit-filter-field {
  min-width: 140px;
  max-width: 200px;
}

.audit-action-badge {
  padding: 0.15em 0.4em;
  font-size: 0.82rem;
  border-radius: 4px;
  background: rgba(var(--v-theme-primary), 0.08);
}

.audit-detail-panel {
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  padding: 1rem 1.25rem;
}

.audit-detail-panel__header {
  margin-bottom: 0.75rem;
}

.audit-detail-section {
  margin-bottom: 0.75rem;
}

.audit-changed-fields {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-top: 0.25rem;
}

.audit-field-chip {
  padding: 0.1em 0.5em;
  font-size: 0.8rem;
  border-radius: 4px;
  background: rgba(var(--v-theme-warning), 0.12);
}

.audit-json-block {
  overflow-x: auto;
  padding: 0.75rem;
  border-radius: 6px;
  font-size: 0.78rem;
  line-height: 1.4;
  background: rgba(var(--v-theme-surface-variant), 0.4);
  white-space: pre-wrap;
  word-break: break-word;
}
</style>
