<template>
  <div class="dm-page">
    <div class="dm-card card">
        <div class="flex justify-between items-center mb-4">
          <h5>Data Management</h5>
          <div class="flex gap-2">
            <Button
              v-if="selectedRows.length > 0"
              label="Edit Massal"
              icon="pi pi-pencil"
              severity="secondary"
              @click="showBulkEditDialog"
            />
            <Button
              v-if="selectedRows.length > 0"
              :label="`Hapus (${selectedRows.length})`"
              icon="pi pi-trash"
              severity="danger"
              @click="confirmBatchDelete"
            />
            <Button
              label="Export"
              icon="pi pi-download"
              severity="secondary"
              outlined
              @click="showExportDialog"
            />
            <Button label="Tambah Data" icon="pi pi-plus" @click="router.push('/data-input')" />
          </div>
        </div>

        <!-- Advanced Filters Panel -->
        <div class="mb-4">
          <Button
            :label="showAdvancedFilters ? 'Sembunyikan Filter' : 'Filter Lanjutan'"
            icon="pi pi-filter"
            severity="secondary"
            outlined
            size="small"
            @click="showAdvancedFilters = !showAdvancedFilters"
          />
        </div>

        <Panel v-if="showAdvancedFilters" header="Filter Lanjutan" :toggleable="false" class="mb-4">
          <div class="grid">
            <div class="col-12 md:col-3">
              <label class="font-bold block mb-2">Kategori</label>
              <Dropdown
                v-model="advancedFilters.category"
                :options="filterCategories"
                placeholder="Semua Kategori"
                showClear
                class="w-full"
              />
            </div>
            <div class="col-12 md:col-3">
              <label class="font-bold block mb-2">Status EDR</label>
              <Dropdown
                v-model="advancedFilters.status"
                :options="filterStatuses"
                optionLabel="label"
                optionValue="value"
                placeholder="Semua Status"
                showClear
                class="w-full"
              />
            </div>
            <div class="col-12 md:col-3">
              <label class="font-bold block mb-2">Tanggal Mulai</label>
              <Calendar
                v-model="advancedFilters.startDate"
                dateFormat="yy-mm-dd"
                showIcon
                placeholder="Pilih tanggal"
                class="w-full"
              />
            </div>
            <div class="col-12 md:col-3">
              <label class="font-bold block mb-2">Tanggal Akhir</label>
              <Calendar
                v-model="advancedFilters.endDate"
                dateFormat="yy-mm-dd"
                showIcon
                placeholder="Pilih tanggal"
                class="w-full"
              />
            </div>
          </div>

          <div class="flex gap-2 mt-3">
            <Button label="Terapkan Filter" icon="pi pi-check" @click="applyAdvancedFilters" size="small" />
            <Button label="Reset Filter" icon="pi pi-times" severity="secondary" outlined @click="clearAdvancedFilters" size="small" />
          </div>

          <div class="mt-4 pt-4 border-t border-surface-border">
            <label class="font-bold block mb-2">Filter Tersimpan</label>
            <div class="flex gap-2">
              <Dropdown
                v-model="selectedSavedFilter"
                :options="savedFilters"
                optionLabel="name"
                placeholder="Pilih filter tersimpan"
                showClear
                @change="loadSavedFilter"
                class="flex-1"
                :loading="loadingSavedFilters"
              />
              <Button icon="pi pi-save" v-tooltip.top="'Simpan Filter'" @click="showSaveFilterDialog" size="small" />
              <Button icon="pi pi-trash" v-tooltip.top="'Hapus Filter'" severity="danger" outlined @click="deleteSavedFilter" :disabled="!selectedSavedFilter" size="small" />
            </div>
          </div>
        </Panel>

        <DataTable
          v-model:filters="filters"
          :value="dataList"
          :loading="loading"
          :paginator="true"
          :rows="rows"
          :rowsPerPageOptions="[10, 25, 50]"
          :totalRecords="totalRecords"
          :lazy="true"
          @page="onPage"
          @sort="onSort"
          dataKey="id"
          filterDisplay="row"
          :globalFilterFields="['title', 'category', 'status']"
          class="p-datatable-sm"
          scrollable
          scrollHeight="flex"
          tableStyle="table-layout: auto; width: 100%"
        >
          <template #header>
            <div class="flex justify-between items-center">
              <IconField iconPosition="left">
                <InputIcon>
                  <i class="pi pi-search" />
                </InputIcon>
                <InputText v-model="filters['global'].value" placeholder="Cari data..." @input="onSearch" />
              </IconField>
            </div>
          </template>

          <Column headerStyle="width: 3rem">
            <template #header>
              <Checkbox :modelValue="isAllSelected" @update:modelValue="toggleSelectAll" binary />
            </template>
            <template #body="{ data }">
              <Checkbox :modelValue="isSelected(data)" @update:modelValue="toggleRowSelection(data)" binary />
            </template>
          </Column>
          <Column field="title" header="Hostname" sortable>
            <template #body="{ data }">
              <span class="font-semibold">{{ data.title }}</span>
            </template>
          </Column>
          <Column field="category" header="Lokasi" sortable>
            <template #body="{ data }">
              <Tag :value="data.category" severity="info" />
            </template>
          </Column>
          <Column header="Perangkat">
            <template #body="{ data }">
              <div>
                <div class="font-semibold text-sm">{{ data.metadata?.manufacturer || '-' }}</div>
                <div class="text-xs text-surface-500">{{ data.metadata?.model || '-' }}</div>
              </div>
            </template>
          </Column>
          <Column header="OS">
            <template #body="{ data }">
              {{ data.metadata?.os ? ('Win ' + data.metadata.os) : '-' }}
            </template>
          </Column>
          <Column field="value" header="RAM" sortable>
            <template #body="{ data }">
              {{ formatRAM(data.value) }} MB
            </template>
          </Column>
          <Column field="status" header="Status EDR" sortable>
            <template #body="{ data }">
              <Tag
                :value="data.status === 'active' ? 'Terinstall' : 'Tidak Aktif'"
                :severity="data.status === 'active' ? 'success' : 'danger'"
              />
            </template>
          </Column>
          <Column header="Aksi" :exportable="false">
            <template #body="slotProps">
              <Button icon="pi pi-eye" outlined rounded size="small" class="mr-1" @click="viewDetail(slotProps.data)" v-tooltip.top="'Detail'" />
              <Button icon="pi pi-pencil" outlined rounded size="small" class="mr-1" @click="editData(slotProps.data)" v-tooltip.top="'Edit'" />
              <Button icon="pi pi-trash" outlined rounded severity="danger" size="small" @click="confirmDelete(slotProps.data)" v-tooltip.top="'Hapus'" />
            </template>
          </Column>
        </DataTable>

        <!-- Detail Dialog -->
        <Dialog v-model:visible="detailDialog" :style="{ width: '600px' }" header="Detail Perangkat" :modal="true" :dismissableMask="true">
          <div v-if="selectedData" class="flex flex-col gap-3">
            <div class="grid">
              <div class="col-6">
                <label class="font-bold text-sm text-surface-500">Hostname</label>
                <div class="mt-1">{{ selectedData.title }}</div>
              </div>
              <div class="col-6">
                <label class="font-bold text-sm text-surface-500">Device ID</label>
                <div class="mt-1 text-sm">{{ selectedData.metadata?.device_id || '-' }}</div>
              </div>
              <div class="col-6">
                <label class="font-bold text-sm text-surface-500">Lokasi</label>
                <div class="mt-1"><Tag :value="selectedData.category" severity="info" /></div>
              </div>
              <div class="col-6">
                <label class="font-bold text-sm text-surface-500">Status EDR</label>
                <div class="mt-1">
                  <Tag
                    :value="selectedData.status === 'active' ? 'Terinstall' : 'Tidak Aktif'"
                    :severity="selectedData.status === 'active' ? 'success' : 'danger'"
                  />
                </div>
              </div>
              <div class="col-6">
                <label class="font-bold text-sm text-surface-500">Operating System</label>
                <div class="mt-1">{{ selectedData.metadata?.os || '-' }}</div>
              </div>
              <div class="col-6">
                <label class="font-bold text-sm text-surface-500">OS Version</label>
                <div class="mt-1 text-sm">{{ selectedData.metadata?.os_version || '-' }}</div>
              </div>
              <div class="col-6">
                <label class="font-bold text-sm text-surface-500">Manufacturer</label>
                <div class="mt-1">{{ selectedData.metadata?.manufacturer || '-' }}</div>
              </div>
              <div class="col-6">
                <label class="font-bold text-sm text-surface-500">Model</label>
                <div class="mt-1">{{ selectedData.metadata?.model || '-' }}</div>
              </div>
              <div class="col-6">
                <label class="font-bold text-sm text-surface-500">RAM</label>
                <div class="mt-1">{{ formatRAM(selectedData.value) }} MB</div>
              </div>
              <div class="col-6">
                <label class="font-bold text-sm text-surface-500">Serial Number</label>
                <div class="mt-1 text-sm">{{ selectedData.metadata?.serial_number || '-' }}</div>
              </div>
              <div class="col-6">
                <label class="font-bold text-sm text-surface-500">Last Check-in</label>
                <div class="mt-1">{{ formatCheckin(selectedData.metadata?.last_checkin) }}</div>
              </div>
              <div class="col-6">
                <label class="font-bold text-sm text-surface-500">Online Status</label>
                <div class="mt-1">
                  <Tag
                    :value="getOnlineStatusLabel(selectedData.metadata?.online_status)"
                    :severity="getOnlineStatusSeverity(selectedData.metadata?.online_status)"
                  />
                </div>
              </div>
              <div class="col-6">
                <label class="font-bold text-sm text-surface-500">Domain / Workgroup</label>
                <div class="mt-1 text-sm">{{ selectedData.metadata?.domain_workgroup || '-' }}</div>
              </div>
              <div class="col-6">
                <label class="font-bold text-sm text-surface-500">Chasis Type</label>
                <div class="mt-1 text-sm">{{ selectedData.metadata?.chasis_type || '-' }}</div>
              </div>
              <div class="col-6">
                <label class="font-bold text-sm text-surface-500">Agent GUID</label>
                <div class="mt-1 text-xs" style="word-break: break-all;">{{ selectedData.metadata?.agentguid || '-' }}</div>
              </div>
            </div>
            <div v-if="selectedData.metadata?.has_warnings" class="p-3 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded">
              <div class="flex items-center gap-2 text-yellow-700 dark:text-yellow-400">
                <i class="pi pi-exclamation-triangle"></i>
                <span class="font-semibold">Data ini diimport dengan data tidak lengkap</span>
              </div>
            </div>
          </div>
        </Dialog>

        <!-- Edit Dialog -->
        <Dialog v-model:visible="dataDialog" :style="{ width: '750px' }" header="Edit Perangkat" :modal="true" :dismissableMask="true">
          <div class="flex flex-col gap-4">
            <div class="grid">
              <!-- Identitas -->
              <div class="col-6">
                <label for="edit_hostname" class="font-bold block mb-2">Computer Name (Hostname)</label>
                <InputText id="edit_hostname" v-model="formData.title" class="w-full" />
              </div>
              <div class="col-6">
                <label for="edit_device_id" class="font-bold block mb-2">Device ID</label>
                <InputText id="edit_device_id" v-model="formData.metadata.device_id" class="w-full" />
              </div>
              <div class="col-6">
                <label for="edit_location" class="font-bold block mb-2">Lokasi (Group ID)</label>
                <InputText id="edit_location" v-model="formData.category" class="w-full" />
              </div>
              <div class="col-6">
                <label for="edit_domain" class="font-bold block mb-2">Domain / Workgroup</label>
                <InputText id="edit_domain" v-model="formData.metadata.domain_workgroup" class="w-full" />
              </div>
              <div class="col-6">
                <label for="edit_agentguid" class="font-bold block mb-2">Agent GUID</label>
                <InputText id="edit_agentguid" v-model="formData.metadata.agentguid" class="w-full" />
              </div>

              <!-- Sistem Operasi -->
              <div class="col-6">
                <label for="edit_os" class="font-bold block mb-2">Operating System</label>
                <InputText id="edit_os" v-model="formData.metadata.os" class="w-full" />
              </div>
              <div class="col-6">
                <label for="edit_os_version" class="font-bold block mb-2">OS Version</label>
                <InputText id="edit_os_version" v-model="formData.metadata.os_version" class="w-full" />
              </div>

              <!-- Hardware -->
              <div class="col-6">
                <label for="edit_manufacturer" class="font-bold block mb-2">Manufacturer</label>
                <InputText id="edit_manufacturer" v-model="formData.metadata.manufacturer" class="w-full" />
              </div>
              <div class="col-6">
                <label for="edit_model" class="font-bold block mb-2">Product Name (Model)</label>
                <InputText id="edit_model" v-model="formData.metadata.model" class="w-full" />
              </div>
              <div class="col-4">
                <label for="edit_ram" class="font-bold block mb-2">RAM (MB)</label>
                <InputNumber id="edit_ram" v-model="formData.value" class="w-full" />
              </div>
              <div class="col-4">
                <label for="edit_chasis" class="font-bold block mb-2">Chasis Type</label>
                <InputText id="edit_chasis" v-model="formData.metadata.chasis_type" class="w-full" />
              </div>
              <div class="col-4">
                <label for="edit_serial" class="font-bold block mb-2">Serial Number</label>
                <InputText id="edit_serial" v-model="formData.metadata.serial_number" class="w-full" />
              </div>

              <!-- Status & Monitoring -->
              <div class="col-6">
                <label for="edit_status" class="font-bold block mb-2">Status EDR</label>
                <Dropdown
                  id="edit_status"
                  v-model="formData.status"
                  :options="statuses"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Pilih Status"
                  class="w-full"
                />
              </div>
              <div class="col-6">
                <label for="edit_online" class="font-bold block mb-2">Online Status</label>
                <Dropdown
                  id="edit_online"
                  v-model="formData.metadata.online_status"
                  :options="onlineStatuses"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Pilih Online Status"
                  class="w-full"
                />
              </div>
              <div class="col-12">
                <label for="edit_checkin" class="font-bold block mb-2">Last Check-in</label>
                <InputText id="edit_checkin" v-model="formData.metadata.last_checkin" class="w-full" placeholder="2025-06-24T12:00:00" />
              </div>
            </div>
          </div>

          <template #footer>
            <Button label="Batal" icon="pi pi-times" text @click="hideDialog" />
            <Button label="Simpan" icon="pi pi-check" @click="saveData" />
          </template>
        </Dialog>

        <!-- Delete Dialog -->
        <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Konfirmasi" :modal="true" :dismissableMask="true">
          <div class="confirmation-content flex items-center">
            <i class="pi pi-exclamation-triangle mr-3" style="font-size: 2rem" />
            <span v-if="selectedData">Apakah Anda yakin ingin menghapus <b>{{ selectedData.title }}</b>?</span>
          </div>
          <template #footer>
            <Button label="Tidak" icon="pi pi-times" text @click="deleteDialog = false" />
            <Button label="Ya" icon="pi pi-check" severity="danger" @click="deleteData" />
          </template>
        </Dialog>

        <!-- Batch Delete Dialog -->
        <Dialog v-model:visible="batchDeleteDialog" :style="{ width: '450px' }" header="Konfirmasi Hapus Batch" :modal="true" :dismissableMask="true">
          <div class="confirmation-content flex items-center">
            <i class="pi pi-exclamation-triangle mr-3" style="font-size: 2rem; color: var(--red-500)" />
            <span>Apakah Anda yakin ingin menghapus <b>{{ selectedRows.length }}</b> data yang dipilih?</span>
          </div>
          <template #footer>
            <Button label="Tidak" icon="pi pi-times" text @click="batchDeleteDialog = false" />
            <Button label="Ya, Hapus Semua" icon="pi pi-trash" severity="danger" @click="batchDelete" :loading="batchDeleting" />
          </template>
        </Dialog>

        <!-- Bulk Edit Dialog -->
        <Dialog v-model:visible="bulkEditDialog" :style="{ width: '500px' }" header="Edit Massal" :modal="true" :dismissableMask="true">
          <p class="mb-4 text-sm text-surface-600">
            Mengubah <b>{{ selectedRows.length }}</b> data yang dipilih. Kosongkan field yang tidak ingin diubah.
          </p>
          <div class="flex flex-col gap-4">
            <div>
              <label for="bulk_category" class="font-bold block mb-2">Kategori (Lokasi)</label>
              <Dropdown
                id="bulk_category"
                v-model="bulkEditData.category"
                :options="filterCategories"
                placeholder="Tidak diubah"
                showClear
                class="w-full"
              />
            </div>
            <div>
              <label for="bulk_status" class="font-bold block mb-2">Status EDR</label>
              <Dropdown
                id="bulk_status"
                v-model="bulkEditData.status"
                :options="statuses"
                optionLabel="label"
                optionValue="value"
                placeholder="Tidak diubah"
                showClear
                class="w-full"
              />
            </div>
          </div>
          <template #footer>
            <Button label="Batal" icon="pi pi-times" text @click="bulkEditDialog = false" />
            <Button label="Simpan Perubahan" icon="pi pi-check" @click="bulkUpdate" :loading="bulkEditing" />
          </template>
        </Dialog>

        <!-- Export Dialog -->
        <Dialog v-model:visible="exportDialog" :style="{ width: '600px' }" header="Export Data" :modal="true" :dismissableMask="true">
          <div class="flex flex-col gap-4">
            <div>
              <label class="font-bold block mb-2">Format</label>
              <div class="flex gap-4">
                <div class="flex items-center">
                  <input type="radio" id="format_csv" value="csv" v-model="exportFormat" class="mr-2" />
                  <label for="format_csv">CSV</label>
                </div>
                <div class="flex items-center">
                  <input type="radio" id="format_xlsx" value="xlsx" v-model="exportFormat" class="mr-2" />
                  <label for="format_xlsx">XLSX (Excel)</label>
                </div>
              </div>
            </div>

            <div>
              <div class="flex justify-between items-center mb-2">
                <label class="font-bold">Pilih Kolom</label>
                <div class="flex gap-2">
                  <Button label="Pilih Semua" size="small" text @click="selectAllColumns" />
                  <Button label="Hapus Semua" size="small" text @click="deselectAllColumns" />
                </div>
              </div>
              <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto p-2 border border-surface-200 rounded">
                <div v-for="col in availableExportColumns" :key="col.key" class="flex items-center">
                  <Checkbox
                    :inputId="'col_' + col.key"
                    v-model="exportColumns"
                    :value="col.key"
                    :binary="false"
                  />
                  <label :for="'col_' + col.key" class="ml-2 text-sm">{{ col.label }}</label>
                </div>
              </div>
            </div>

            <p class="text-sm text-surface-600">
              Filter yang sedang aktif akan diterapkan pada export.
            </p>
          </div>
          <template #footer>
            <Button label="Batal" icon="pi pi-times" text @click="exportDialog = false" />
            <Button label="Export" icon="pi pi-download" @click="performExport" :loading="exporting" />
          </template>
        </Dialog>

        <!-- Online Status Explanation -->
        <div class="chasis-mapping-section card mt-4">
          <h6 class="mb-3">Online Status Explanation</h6>
          <ul class="m-0 pl-4" style="line-height: 2;">
            <li><strong>0:</strong> Offline - Offline</li>
            <li><strong>1:</strong> Online - Online but not logged in</li>
            <li><strong>11:</strong> Online - User Logged in and active</li>
            <li><strong>12:</strong> Online - User Logged in but inactive</li>
            <li><strong>199:</strong> Offline - Agent never check in</li>
          </ul>
        </div>

        <!-- Chasis Type Mapping Reference -->
        <div class="chasis-mapping-section card mt-4">
          <h6 class="mb-3">Chasis Type Mapping (Chassis Type → Parameter)</h6>
          <DataTable :value="chasisMappingData" class="p-datatable-sm" :rows="20" :paginator="false" stripedRows>
            <Column field="chassisType" header="Chassis Type" />
            <Column field="parameter" header="Parameter" />
          </DataTable>
          <div class="text-sm text-muted-color mt-3 p-2" style="background: var(--surface-50); border-radius: 6px;">
            <i class="pi pi-info-circle mr-1"></i>
            Catatan: Nilai di kolom "Chasis Type" adalah nilai asli dari database. Kolom "Chasis Type Parameter" menunjukkan nilai standar setelah mapping.
          </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { DataService } from '@/service/DataService';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Checkbox from 'primevue/checkbox';
import Calendar from 'primevue/calendar';
import Panel from 'primevue/panel';
import { SavedFilterService } from '@/service/SavedFilterService';

const { isAdmin, token } = useAuth();
const toast = useToast();
const router = useRouter();

const dataList = ref([]);
const loading = ref(false);
const dataDialog = ref(false);
const deleteDialog = ref(false);
const batchDeleteDialog = ref(false);
const bulkEditDialog = ref(false);
const batchDeleting = ref(false);
const bulkEditing = ref(false);
const detailDialog = ref(false);
const selectedData = ref(null);
const showChasisMapping = ref(false);
const exporting = ref(false);

const chasisMappingData = ref([
  { chassisType: 'All In One', parameter: 'All in one' },
  { chassisType: 'Notebook', parameter: 'Notebook' },
  { chassisType: 'Desktop', parameter: 'PC Desktop' },
  { chassisType: 'Rack Mount Chassis', parameter: 'Rackmount Server' },
  { chassisType: 'Main Server Chassis', parameter: 'Rackmount Server' },
  { chassisType: 'Type 31', parameter: 'Notebook' },
  { chassisType: 'LapTop', parameter: 'Notebook' },
  { chassisType: 'Type 35', parameter: 'Mini PC' },
  { chassisType: 'Tower', parameter: 'Tower Server' },
  { chassisType: 'Mini Tower', parameter: 'PC Desktop' },
  { chassisType: 'Type 32', parameter: 'Notebook' },
  { chassisType: 'Space Saving', parameter: 'Mini PC' },
  { chassisType: 'Low Profile Desktop', parameter: 'All in one' },
  { chassisType: 'Other', parameter: 'Virtual Machine' },
  { chassisType: 'Docking Station', parameter: 'Notebook' },
  { chassisType: 'Portable', parameter: 'Notebook' }
]);
const selectedRows = ref([]);
const rows = ref(10);
const totalRecords = ref(0);
const searchTimeout = ref(null);

// Export dialog state
const exportDialog = ref(false);
const exportFormat = ref('csv');
const exportColumns = ref([]);
const availableExportColumns = [
  { key: 'id', label: 'ID' },
  { key: 'device_id', label: 'Device ID' },
  { key: 'online_status', label: 'Online Status' },
  { key: 'category', label: 'Lokasi (Group ID)' },
  { key: 'domain_workgroup', label: 'Domain/Workgroup' },
  { key: 'title', label: 'Computer Name' },
  { key: 'os', label: 'Operating System' },
  { key: 'os_version', label: 'OS Version' },
  { key: 'manufacturer', label: 'Manufacturer' },
  { key: 'model', label: 'Product Name' },
  { key: 'value', label: 'RAM (MB)' },
  { key: 'chasis_type', label: 'Chasis Type' },
  { key: 'status', label: 'Status EDR' },
  { key: 'serial_number', label: 'Serial Number' },
  { key: 'last_checkin', label: 'Last Checkin Time' },
  { key: 'agentguid', label: 'Agent GUID' },
  { key: 'created_at', label: 'Created At' },
  { key: 'updated_at', label: 'Updated At' }
];

// Advanced filters state
const showAdvancedFilters = ref(false);
const advancedFilters = ref({
  category: null,
  status: null,
  startDate: null,
  endDate: null
});
const savedFilters = ref([]);
const selectedSavedFilter = ref(null);
const loadingSavedFilters = ref(false);
const filterCategories = ref([]);
const filterStatuses = ref([
  { label: 'Terinstall', value: 'active' },
  { label: 'Tidak Aktif', value: 'inactive' }
]);

const lazyParams = ref({
  page: 1,
  rows: 10,
  sortField: null,
  sortOrder: null,
  filters: {}
});

const filters = ref({
  global: { value: null, matchMode: 'contains' }
});

const formData = reactive({
  id: null,
  title: '',
  category: '',
  value: 0,
  status: '',
  description: '',
  metadata: {
    device_id: '',
    os: '',
    os_version: '',
    manufacturer: '',
    model: '',
    serial_number: '',
    last_checkin: '',
    online_status: '0',
    domain_workgroup: '',
    chasis_type: '',
    agentguid: '',
    import_type: 'bni_device'
  }
});

const statuses = ref([
  { label: 'Terinstall', value: 'active' },
  { label: 'Tidak Aktif', value: 'inactive' }
]);

const bulkEditData = reactive({
  category: null,
  status: null
});

const onlineStatuses = ref([
  { label: 'Offline - Offline (0)', value: '0' },
  { label: 'Online - Not logged in (1)', value: '1' },
  { label: 'Online - Logged in & active (11)', value: '11' },
  { label: 'Online - Logged in but inactive (12)', value: '12' },
  { label: 'Offline - Never check in (199)', value: '199' }
]);

const loadData = async () => {
  loading.value = true;
  try {
    // Build advanced filter params
    const filterParams = {};
    if (advancedFilters.value.category) {
      filterParams.category = advancedFilters.value.category;
    }
    if (advancedFilters.value.status) {
      filterParams.status = advancedFilters.value.status;
    }
    if (advancedFilters.value.startDate) {
      filterParams.start_date = advancedFilters.value.startDate.toISOString().split('T')[0];
    }
    if (advancedFilters.value.endDate) {
      filterParams.end_date = advancedFilters.value.endDate.toISOString().split('T')[0];
    }

    const response = await DataService.getAllData(
      token.value,
      lazyParams.value.page,
      lazyParams.value.rows,
      filters.value.global.value || '',
      filterParams
    );
    dataList.value = response.data || [];
    totalRecords.value = response.total || 0;
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const onPage = (event) => {
  lazyParams.value.page = event.page + 1;
  lazyParams.value.rows = event.rows;
  loadData();
};

const onSort = (event) => {
  lazyParams.value.sortField = event.sortField;
  lazyParams.value.sortOrder = event.sortOrder;
  loadData();
};

const onSearch = () => {
  clearTimeout(searchTimeout.value);
  searchTimeout.value = setTimeout(() => {
    lazyParams.value.page = 1;
    loadData();
  }, 500);
};

const isAllSelected = computed(() => {
  return dataList.value.length > 0 && selectedRows.value.length === dataList.value.length;
});

const isSelected = (row) => {
  return selectedRows.value.some(r => r.id === row.id);
};

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedRows.value = [];
  } else {
    selectedRows.value = [...dataList.value];
  }
};

const toggleRowSelection = (row) => {
  const index = selectedRows.value.findIndex(r => r.id === row.id);
  if (index >= 0) {
    selectedRows.value.splice(index, 1);
  } else {
    selectedRows.value.push(row);
  }
};

const openNew = () => {
  resetForm();
  dataDialog.value = true;
};

const hideDialog = () => {
  dataDialog.value = false;
  resetForm();
};

const resetForm = () => {
  formData.id = null;
  formData.title = '';
  formData.category = '';
  formData.value = 0;
  formData.status = '';
  formData.description = '';
  formData.metadata = {
    device_id: '',
    os: '',
    os_version: '',
    manufacturer: '',
    model: '',
    serial_number: '',
    last_checkin: '',
    online_status: '0',
    domain_workgroup: '',
    chasis_type: '',
    agentguid: '',
    import_type: 'bni_device'
  };
};

const viewDetail = (data) => {
  selectedData.value = data;
  detailDialog.value = true;
};

const editData = (data) => {
  formData.id = data.id;
  formData.title = data.title;
  formData.category = data.category;
  formData.value = parseFloat(data.value) || 0;
  formData.status = data.status;
  formData.description = data.description;
  formData.metadata = {
    device_id: data.metadata?.device_id || '',
    os: data.metadata?.os || '',
    os_version: data.metadata?.os_version || '',
    manufacturer: data.metadata?.manufacturer || '',
    model: data.metadata?.model || '',
    serial_number: data.metadata?.serial_number || '',
    last_checkin: data.metadata?.last_checkin || '',
    online_status: String(data.metadata?.online_status ?? '0'),
    domain_workgroup: data.metadata?.domain_workgroup || '',
    chasis_type: data.metadata?.chasis_type || '',
    agentguid: data.metadata?.agentguid || '',
    import_type: data.metadata?.import_type || 'bni_device'
  };
  dataDialog.value = true;
};

const saveData = async () => {
  try {
    const payload = {
      title: formData.title,
      category: formData.category,
      value: formData.value,
      status: formData.status,
      description: formData.description,
      date: new Date().toISOString().split('T')[0],
      metadata: formData.metadata
    };

    if (formData.id) {
      await DataService.updateData(token.value, formData.id, payload);
      toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data berhasil diperbarui', life: 3000 });
    } else {
      await DataService.createData(token.value, payload);
      toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data berhasil ditambahkan', life: 3000 });
    }
    hideDialog();
    loadData();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menyimpan data', life: 3000 });
  }
};

const confirmDelete = (data) => {
  selectedData.value = data;
  deleteDialog.value = true;
};

const deleteData = async () => {
  try {
    await DataService.deleteData(token.value, selectedData.value.id);
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data berhasil dihapus', life: 3000 });
    deleteDialog.value = false;
    selectedData.value = null;
    loadData();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menghapus data', life: 3000 });
  }
};

const confirmBatchDelete = () => {
  batchDeleteDialog.value = true;
};

const batchDelete = async () => {
  batchDeleting.value = true;
  try {
    const ids = selectedRows.value.map(row => row.id);
    const result = await DataService.batchDelete(token.value, ids);
    toast.add({
      severity: 'success',
      summary: 'Berhasil',
      detail: result.message || `${ids.length} data berhasil dihapus`,
      life: 3000
    });
    batchDeleteDialog.value = false;
    selectedRows.value = [];
    loadData();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menghapus data', life: 3000 });
  } finally {
    batchDeleting.value = false;
  }
};

const showBulkEditDialog = () => {
  bulkEditData.category = null;
  bulkEditData.status = null;
  bulkEditDialog.value = true;
};

const bulkUpdate = async () => {
  // Validate at least one field is selected
  if (!bulkEditData.category && !bulkEditData.status) {
    toast.add({
      severity: 'warn',
      summary: 'Perhatian',
      detail: 'Pilih minimal satu field untuk diubah',
      life: 3000
    });
    return;
  }

  bulkEditing.value = true;
  try {
    const ids = selectedRows.value.map(row => row.id);
    const updates = {};

    if (bulkEditData.category) updates.category = bulkEditData.category;
    if (bulkEditData.status) updates.status = bulkEditData.status;

    const result = await DataService.batchUpdate(token.value, ids, updates);
    toast.add({
      severity: 'success',
      summary: 'Berhasil',
      detail: result.message || `${ids.length} data berhasil diupdate`,
      life: 3000
    });
    bulkEditDialog.value = false;
    selectedRows.value = [];
    loadData();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal mengupdate data', life: 3000 });
  } finally {
    bulkEditing.value = false;
  }
};

const formatRAM = (value) => {
  const num = parseFloat(value);
  if (isNaN(num)) return '-';
  return num.toLocaleString('id-ID');
};

const formatCheckin = (checkin) => {
  if (!checkin) return '-';
  try {
    return new Date(checkin).toLocaleString('id-ID', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch {
    return checkin;
  }
};

const getOnlineStatusLabel = (status) => {
  const s = String(status ?? '');
  switch (s) {
    case '0': return 'Offline - Offline';
    case '1': return 'Online - Not logged in';
    case '11': return 'Online - Logged in & active';
    case '12': return 'Online - Logged in but inactive';
    case '199': return 'Offline - Never check in';
    default: return s ? `Status (${s})` : '-';
  }
};

const getOnlineStatusSeverity = (status) => {
  const s = String(status ?? '');
  switch (s) {
    case '0': return 'danger';
    case '1': return 'warn';
    case '11': return 'success';
    case '12': return 'secondary';
    case '199': return 'danger';
    default: return 'info';
  }
};

const exportCSV = async () => {
  exporting.value = true;
  try {
    const search = filters.value.global.value || '';
    await DataService.exportData(token.value, search);
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data berhasil diexport', life: 3000 });
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal mengexport data', life: 3000 });
  } finally {
    exporting.value = false;
  }
};

// Enhanced export functions
const showExportDialog = () => {
  // Set default format and select all columns by default
  exportFormat.value = 'csv';
  exportColumns.value = availableExportColumns.map(col => col.key);
  exportDialog.value = true;
};

const selectAllColumns = () => {
  exportColumns.value = availableExportColumns.map(col => col.key);
};

const deselectAllColumns = () => {
  exportColumns.value = [];
};

const performExport = async () => {
  if (exportColumns.value.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'Perhatian',
      detail: 'Pilih minimal satu kolom untuk diexport',
      life: 3000
    });
    return;
  }

  exporting.value = true;
  try {
    // Build filter params from advanced filters
    const filterParams = {};
    if (advancedFilters.value.category) {
      filterParams.category = advancedFilters.value.category;
    }
    if (advancedFilters.value.status) {
      filterParams.status = advancedFilters.value.status;
    }
    if (advancedFilters.value.startDate) {
      filterParams.start_date = advancedFilters.value.startDate.toISOString().split('T')[0];
    }
    if (advancedFilters.value.endDate) {
      filterParams.end_date = advancedFilters.value.endDate.toISOString().split('T')[0];
    }

    const search = filters.value.global.value || '';

    await DataService.exportData(
      token.value,
      search,
      exportFormat.value,
      exportColumns.value,
      filterParams
    );

    toast.add({
      severity: 'success',
      summary: 'Berhasil',
      detail: `Data berhasil diexport ke format ${exportFormat.value.toUpperCase()}`,
      life: 3000
    });
    exportDialog.value = false;
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal mengexport data', life: 3000 });
  } finally {
    exporting.value = false;
  }
};

// Advanced filter functions
const applyAdvancedFilters = () => {
  lazyParams.value.page = 1;
  loadData();
};

const clearAdvancedFilters = () => {
  advancedFilters.value = {
    category: null,
    status: null,
    startDate: null,
    endDate: null
  };
  selectedSavedFilter.value = null;
  lazyParams.value.page = 1;
  loadData();
};

const loadSavedFilters = async () => {
  loadingSavedFilters.value = true;
  try {
    const response = await SavedFilterService.getAll(token.value);
    savedFilters.value = response;
  } catch (error) {
    console.error('Failed to load saved filters:', error);
  } finally {
    loadingSavedFilters.value = false;
  }
};

const loadSavedFilter = () => {
  if (selectedSavedFilter.value) {
    const filterData = selectedSavedFilter.value.filters;
    advancedFilters.value = {
      category: filterData.category || null,
      status: filterData.status || null,
      startDate: filterData.startDate ? new Date(filterData.startDate) : null,
      endDate: filterData.endDate ? new Date(filterData.endDate) : null
    };
    applyAdvancedFilters();
  }
};

const showSaveFilterDialog = () => {
  const filterName = prompt('Masukkan nama filter:');
  if (filterName) {
    saveSavedFilter(filterName);
  }
};

const saveSavedFilter = async (name) => {
  try {
    const filterData = {
      category: advancedFilters.value.category,
      status: advancedFilters.value.status,
      startDate: advancedFilters.value.startDate ? advancedFilters.value.startDate.toISOString().split('T')[0] : null,
      endDate: advancedFilters.value.endDate ? advancedFilters.value.endDate.toISOString().split('T')[0] : null
    };
    await SavedFilterService.save(token.value, name, filterData);
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Filter berhasil disimpan', life: 3000 });
    loadSavedFilters();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menyimpan filter', life: 3000 });
  }
};

const deleteSavedFilter = async () => {
  if (!selectedSavedFilter.value) return;

  try {
    await SavedFilterService.delete(token.value, selectedSavedFilter.value.id);
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Filter berhasil dihapus', life: 3000 });
    selectedSavedFilter.value = null;
    loadSavedFilters();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menghapus filter', life: 3000 });
  }
};

const loadUniqueCategories = async () => {
  try {
    const response = await DataService.getAllData(token.value, 1, 1000, '', {});
    const categories = [...new Set(response.data.map(d => d.category))].filter(Boolean);
    filterCategories.value = categories.sort();
  } catch (error) {
    console.error('Failed to load categories:', error);
  }
};

onMounted(() => {
  loadData();
  loadSavedFilters();
  loadUniqueCategories();
});
</script>

<style scoped>
.dm-page {
  display: flex;
  flex-direction: column;
}

.dm-card {
  margin: 0 !important;
}

:deep(.p-datatable-table) {
  table-layout: auto;
  width: 100%;
}

:deep(.p-datatable td),
:deep(.p-datatable th) {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

:deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(4)) {
  white-space: normal;
  max-width: 200px;
}

:deep(.p-datatable .p-datatable-tbody > tr > td:nth-child(3)) {
  max-width: 220px;
}

:deep(.p-paginator) {
  border-top: 1px solid var(--surface-border);
  padding: 0.5rem;
}

.chasis-mapping-section {
  border: 1px solid var(--surface-border);
  border-radius: 8px;
  padding: 1.25rem;
}
</style>
