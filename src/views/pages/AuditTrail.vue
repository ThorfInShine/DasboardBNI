<template>
  <div class="audit-trail-page">
    <div class="card">
      <div class="flex justify-between items-center mb-4">
        <h5>Audit Trail - Riwayat Perubahan Data</h5>
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
            <label class="font-bold block mb-2">Cari</label>
            <InputText
              v-model="filters.search"
              placeholder="Cari judul atau kategori..."
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
        :value="auditTrail"
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
        <Column header="Data" style="min-width: 200px">
          <template #body="{ data }">
            <div v-if="data.data">
              <div class="font-semibold">{{ data.data.title }}</div>
              <div class="text-xs text-surface-500">{{ data.data.category }}</div>
            </div>
            <span v-else class="text-surface-400">-</span>
          </template>
        </Column>
        <Column field="changed_by" header="Diubah Oleh" style="min-width: 150px">
          <template #body="{ data }">
            <div v-if="data.changed_by">
              <div>{{ data.changed_by.name }}</div>
              <div class="text-xs text-surface-500">NPP: {{ data.changed_by.npp }}</div>
            </div>
            <span v-else class="text-surface-400">System</span>
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
        :style="{ width: '700px' }"
        header="Detail Perubahan"
        :modal="true"
        :dismissableMask="true"
      >
        <div v-if="selectedHistory" class="flex flex-col gap-4">
          <div class="grid">
            <div class="col-6">
              <label class="font-bold text-sm text-surface-500">Tanggal</label>
              <div class="mt-1">{{ formatDate(selectedHistory.created_at) }}</div>
            </div>
            <div class="col-6">
              <label class="font-bold text-sm text-surface-500">Jenis Aksi</label>
              <div class="mt-1">
                <Tag
                  :value="getActionLabel(selectedHistory.action)"
                  :severity="getActionSeverity(selectedHistory.action)"
                />
              </div>
            </div>
            <div class="col-12">
              <label class="font-bold text-sm text-surface-500">Diubah Oleh</label>
              <div class="mt-1" v-if="selectedHistory.changed_by">
                {{ selectedHistory.changed_by.name }} (NPP: {{ selectedHistory.changed_by.npp }})
              </div>
              <div class="mt-1" v-else>System</div>
            </div>
          </div>

          <div v-if="selectedHistory.action === 'created'" class="border border-green-200 bg-green-50 dark:bg-green-900/20 rounded p-3">
            <h6 class="mb-2 text-green-700 dark:text-green-400">Data Baru Dibuat</h6>
            <div class="grid gap-2">
              <div v-for="(value, key) in selectedHistory.new_values" :key="key" class="col-12">
                <span class="font-semibold">{{ formatFieldName(key) }}:</span>
                <span class="ml-2">{{ formatFieldValue(key, value) }}</span>
              </div>
            </div>
          </div>

          <div v-else-if="selectedHistory.action === 'deleted'" class="border border-red-200 bg-red-50 dark:bg-red-900/20 rounded p-3">
            <h6 class="mb-2 text-red-700 dark:text-red-400">Data Dihapus</h6>
            <div class="grid gap-2">
              <div v-for="(value, key) in selectedHistory.old_values" :key="key" class="col-12">
                <span class="font-semibold">{{ formatFieldName(key) }}:</span>
                <span class="ml-2">{{ formatFieldValue(key, value) }}</span>
              </div>
            </div>
          </div>

          <div v-else-if="selectedHistory.action === 'updated'">
            <h6 class="mb-3">Perubahan Field</h6>
            <div v-for="(newValue, key) in selectedHistory.new_values" :key="key" class="mb-3">
              <div v-if="hasChanged(key)" class="border border-surface-200 rounded p-3">
                <div class="font-semibold mb-2">{{ formatFieldName(key) }}</div>
                <div class="grid">
                  <div class="col-6">
                    <div class="text-xs text-surface-500 mb-1">Sebelum:</div>
                    <div class="bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 p-2 rounded">
                      {{ formatFieldValue(key, selectedHistory.old_values[key]) }}
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="text-xs text-surface-500 mb-1">Sesudah:</div>
                    <div class="bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 p-2 rounded">
                      {{ formatFieldValue(key, newValue) }}
                    </div>
                  </div>
                </div>
              </div>
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

const auditTrail = ref([]);
const loading = ref(false);
const detailDialog = ref(false);
const selectedHistory = ref(null);
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
  search: ''
});

const actionTypes = ref([
  { label: 'Dibuat', value: 'created' },
  { label: 'Diperbarui', value: 'updated' },
  { label: 'Dihapus', value: 'deleted' }
]);

const loadAuditTrail = async () => {
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
    if (filters.search) {
      params.append('search', filters.search);
    }

    const response = await axios.get(`/api/audit-trail?${params}`, {
      headers: { Authorization: `Bearer ${token.value}` }
    });

    auditTrail.value = response.data.data || [];
    totalRecords.value = response.data.total || 0;
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Gagal memuat audit trail',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

const onPage = (event) => {
  lazyParams.value.page = event.page + 1;
  lazyParams.value.rows = event.rows;
  loadAuditTrail();
};

const applyFilters = () => {
  lazyParams.value.page = 1;
  loadAuditTrail();
};

const resetFilters = () => {
  filters.startDate = null;
  filters.endDate = null;
  filters.action = null;
  filters.search = '';
  lazyParams.value.page = 1;
  loadAuditTrail();
};

const viewDetail = (history) => {
  selectedHistory.value = history;
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
    created: 'Dibuat',
    updated: 'Diperbarui',
    deleted: 'Dihapus'
  };
  return labels[action] || action;
};

const getActionSeverity = (action) => {
  const severities = {
    created: 'success',
    updated: 'info',
    deleted: 'danger'
  };
  return severities[action] || 'secondary';
};

const formatFieldName = (key) => {
  const names = {
    title: 'Hostname',
    category: 'Lokasi',
    value: 'RAM (MB)',
    status: 'Status EDR',
    description: 'Deskripsi',
    date: 'Tanggal',
    metadata: 'Metadata'
  };
  return names[key] || key;
};

const formatFieldValue = (key, value) => {
  if (value === null || value === undefined) return '-';
  if (key === 'status') {
    return value === 'active' ? 'Terinstall' : 'Tidak Aktif';
  }
  if (key === 'metadata' && typeof value === 'object') {
    return JSON.stringify(value, null, 2);
  }
  return String(value);
};

const hasChanged = (key) => {
  if (!selectedHistory.value) return false;
  const oldVal = selectedHistory.value.old_values?.[key];
  const newVal = selectedHistory.value.new_values?.[key];
  return JSON.stringify(oldVal) !== JSON.stringify(newVal);
};

onMounted(() => {
  loadAuditTrail();
});
</script>

<style scoped>
.audit-trail-page {
  display: flex;
  flex-direction: column;
}
</style>
