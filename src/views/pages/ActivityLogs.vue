<template>
  <div class="activity-logs-page">
    <div class="card">
      <div class="flex justify-between items-center mb-4">
        <h5>Activity Logs - Riwayat Aktivitas User</h5>
      </div>

      <!-- Filters -->
      <Panel header="Filter" :toggleable="true" :collapsed="true" class="mb-4">
        <div class="grid">
          <div class="col-12 md:col-3">
            <label class="font-bold block mb-2">Tanggal Mulai</label>
            <Calendar
              v-model="filters.startDate"
              dateFormat="yy-mm-dd"
              showIcon
              placeholder="Pilih tanggal"
              class="w-full"
            />
          </div>
          <div class="col-12 md:col-3">
            <label class="font-bold block mb-2">Tanggal Akhir</label>
            <Calendar
              v-model="filters.endDate"
              dateFormat="yy-mm-dd"
              showIcon
              placeholder="Pilih tanggal"
              class="w-full"
            />
          </div>
          <div class="col-12 md:col-3">
            <label class="font-bold block mb-2">Jenis Aksi</label>
            <Dropdown
              v-model="filters.action"
              :options="actionTypes"
              optionLabel="label"
              optionValue="value"
              placeholder="Semua"
              showClear
              class="w-full"
            />
          </div>
          <div class="col-12 md:col-3">
            <label class="font-bold block mb-2">IP Address</label>
            <InputText
              v-model="filters.ipAddress"
              placeholder="Cari IP..."
              class="w-full"
            />
          </div>
        </div>
        <div class="flex gap-2 mt-3">
          <Button label="Terapkan Filter" icon="pi pi-check" @click="applyFilters" size="small" />
          <Button label="Reset" icon="pi pi-times" severity="secondary" outlined @click="resetFilters" size="small" />
        </div>
      </Panel>

      <DataTable
        :value="activityLogs"
        :loading="loading"
        :paginator="true"
        :rows="rows"
        :rowsPerPageOptions="[15, 25, 50]"
        :totalRecords="totalRecords"
        :lazy="true"
        @page="onPage"
        dataKey="id"
        class="p-datatable-sm"
        scrollable
        scrollHeight="flex"
      >
        <Column field="created_at" header="Tanggal" style="min-width: 180px">
          <template #body="{ data }">
            {{ formatDate(data.created_at) }}
          </template>
        </Column>
        <Column field="action" header="Aksi" style="min-width: 120px">
          <template #body="{ data }">
            <Tag
              :value="getActionLabel(data.action)"
              :severity="getActionSeverity(data.action)"
            />
          </template>
        </Column>
        <Column field="user" header="User" style="min-width: 180px">
          <template #body="{ data }">
            <div v-if="data.user">
              <div class="font-semibold">{{ data.user.name }}</div>
              <div class="text-xs text-surface-500">NPP: {{ data.user.npp }}</div>
            </div>
            <span v-else class="text-surface-400">Unknown User</span>
          </template>
        </Column>
        <Column field="ip_address" header="IP Address" style="min-width: 150px">
          <template #body="{ data }">
            <code class="text-xs">{{ data.ip_address || '-' }}</code>
          </template>
        </Column>
        <Column field="user_agent" header="User Agent" style="min-width: 250px">
          <template #body="{ data }">
            <div class="text-xs" style="word-break: break-word;">
              {{ truncateUserAgent(data.user_agent) }}
            </div>
          </template>
        </Column>
        <Column header="Aksi" style="min-width: 100px">
          <template #body="{ data }">
            <Button
              icon="pi pi-eye"
              outlined
              rounded
              size="small"
              @click="viewDetail(data)"
              v-tooltip.top="'Lihat Detail'"
            />
          </template>
        </Column>
      </DataTable>

      <!-- Detail Dialog -->
      <Dialog
        v-model:visible="detailDialog"
        :style="{ width: '600px' }"
        header="Detail Activity Log"
        :modal="true"
        :dismissableMask="true"
      >
        <div v-if="selectedLog" class="flex flex-col gap-4">
          <div class="grid">
            <div class="col-6">
              <label class="font-bold text-sm text-surface-500">Tanggal</label>
              <div class="mt-1">{{ formatDate(selectedLog.created_at) }}</div>
            </div>
            <div class="col-6">
              <label class="font-bold text-sm text-surface-500">Jenis Aksi</label>
              <div class="mt-1">
                <Tag
                  :value="getActionLabel(selectedLog.action)"
                  :severity="getActionSeverity(selectedLog.action)"
                />
              </div>
            </div>
            <div class="col-12">
              <label class="font-bold text-sm text-surface-500">User</label>
              <div class="mt-1" v-if="selectedLog.user">
                {{ selectedLog.user.name }} (NPP: {{ selectedLog.user.npp }})
              </div>
              <div class="mt-1" v-else>Unknown User</div>
            </div>
            <div class="col-6">
              <label class="font-bold text-sm text-surface-500">IP Address</label>
              <div class="mt-1">
                <code class="text-sm">{{ selectedLog.ip_address || '-' }}</code>
              </div>
            </div>
            <div class="col-12">
              <label class="font-bold text-sm text-surface-500">User Agent</label>
              <div class="mt-1 text-sm bg-surface-50 dark:bg-surface-800 p-2 rounded" style="word-break: break-word;">
                {{ selectedLog.user_agent || '-' }}
              </div>
            </div>
            <div class="col-12" v-if="selectedLog.metadata">
              <label class="font-bold text-sm text-surface-500">Metadata</label>
              <pre class="mt-1 text-xs bg-surface-50 dark:bg-surface-800 p-2 rounded overflow-auto">{{ JSON.stringify(selectedLog.metadata, null, 2) }}</pre>
            </div>
          </div>
        </div>
      </Dialog>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import Panel from 'primevue/panel';

const { token } = useAuth();
const toast = useToast();

const activityLogs = ref([]);
const loading = ref(false);
const detailDialog = ref(false);
const selectedLog = ref(null);
const rows = ref(15);
const totalRecords = ref(0);

const lazyParams = ref({
  page: 1,
  rows: 15
});

const filters = reactive({
  startDate: null,
  endDate: null,
  action: null,
  ipAddress: ''
});

const actionTypes = ref([
  { label: 'Login', value: 'login' },
  { label: 'Logout', value: 'logout' },
  { label: 'Failed Login', value: 'failed_login' }
]);

const loadActivityLogs = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams({
      page: lazyParams.value.page,
      per_page: lazyParams.value.rows
    });

    if (filters.startDate) {
      params.append('start_date', filters.startDate.toISOString().split('T')[0]);
    }
    if (filters.endDate) {
      params.append('end_date', filters.endDate.toISOString().split('T')[0]);
    }
    if (filters.action) {
      params.append('action', filters.action);
    }
    if (filters.ipAddress) {
      params.append('ip_address', filters.ipAddress);
    }

    const response = await axios.get(`/api/activity-logs?${params}`, {
      headers: { Authorization: `Bearer ${token.value}` }
    });

    activityLogs.value = response.data.data || [];
    totalRecords.value = response.data.total || 0;
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Gagal memuat activity logs',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

const onPage = (event) => {
  lazyParams.value.page = event.page + 1;
  lazyParams.value.rows = event.rows;
  loadActivityLogs();
};

const applyFilters = () => {
  lazyParams.value.page = 1;
  loadActivityLogs();
};

const resetFilters = () => {
  filters.startDate = null;
  filters.endDate = null;
  filters.action = null;
  filters.ipAddress = '';
  lazyParams.value.page = 1;
  loadActivityLogs();
};

const viewDetail = (log) => {
  selectedLog.value = log;
  detailDialog.value = true;
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  try {
    return new Date(dateStr).toLocaleString('id-ID', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch {
    return dateStr;
  }
};

const getActionLabel = (action) => {
  const labels = {
    login: 'Login',
    logout: 'Logout',
    failed_login: 'Failed Login'
  };
  return labels[action] || action;
};

const getActionSeverity = (action) => {
  const severities = {
    login: 'success',
    logout: 'info',
    failed_login: 'danger'
  };
  return severities[action] || 'secondary';
};

const truncateUserAgent = (ua) => {
  if (!ua) return '-';
  if (ua.length <= 60) return ua;
  return ua.substring(0, 57) + '...';
};

onMounted(() => {
  loadActivityLogs();
});
</script>

<style scoped>
.activity-logs-page {
  display: flex;
  flex-direction: column;
}
</style>
