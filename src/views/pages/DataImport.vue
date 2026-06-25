<template>
  <div class="grid">
    <div class="col-12">
      <div class="card">
        <h5>Data Import</h5>
        <p class="text-muted-color mb-4">Import data dari file CSV atau XLSX</p>

        <div class="grid">
          <div class="col-12 lg:col-6">
            <div class="card bg-surface-50 dark:bg-surface-800">
              <h6 class="mb-3">Upload File</h6>

              <div class="mb-4">
                <FileUpload
                  ref="fileUploadRef"
                  mode="basic"
                  accept=".csv,.xlsx"
                  :maxFileSize="10000000"
                  :auto="false"
                  chooseLabel="Pilih File"
                  @select="onFileSelect"
                  :disabled="uploading"
                />
                <small class="text-muted-color block mt-2">Format: CSV atau XLSX (Maks 10MB)</small>
              </div>

              <div v-if="selectedFile" class="mb-4 p-3 bg-surface-0 dark:bg-surface-900 rounded">
                <div class="flex justify-between items-center">
                  <div>
                    <i class="pi pi-file mr-2"></i>
                    <span class="font-semibold">{{ selectedFile.name }}</span>
                    <span class="text-muted-color ml-2">({{ formatFileSize(selectedFile.size) }})</span>
                  </div>
                  <Button
                    icon="pi pi-times"
                    rounded
                    text
                    severity="danger"
                    @click="clearFile"
                    :disabled="uploading"
                  />
                </div>
              </div>

              <div class="flex gap-2 mb-4">
                <Button
                  label="Import Data"
                  icon="pi pi-upload"
                  :loading="uploading"
                  :disabled="!selectedFile || uploading"
                  @click="importFile"
                  class="flex-1"
                />
                <Button
                  label="Download Template"
                  icon="pi pi-download"
                  outlined
                  @click="toggleTemplateMenu"
                />
                <Menu ref="templateMenu" :model="templateItems" :popup="true" />
              </div>

              <ProgressBar v-if="uploading" mode="indeterminate" class="mb-4" />

              <div v-if="importResult" class="mt-4">
                <Message
                  :severity="importResult.status === 'success' ? 'success' : importResult.status === 'partial' ? 'warn' : 'error'"
                  :closable="false"
                >
                  <div class="flex flex-col gap-2">
                    <div class="font-bold">{{ importResult.message }}</div>
                    <div v-if="importResult.data">
                      <div>✓ Berhasil: {{ importResult.data.success_count }} baris</div>
                      <div v-if="importResult.data.error_count > 0">
                        ✗ Gagal: {{ importResult.data.error_count }} baris
                      </div>
                      <div v-if="importResult.data.warning_count > 0" class="text-yellow-600">
                        ⚠ Peringatan: {{ importResult.data.warning_count }} baris (data tidak lengkap)
                      </div>
                    </div>
                  </div>
                </Message>

                <div v-if="importResult.data?.errors?.length > 0" class="mt-3">
                  <Accordion>
                    <AccordionTab header="Detail Error">
                      <div class="max-h-60 overflow-auto">
                        <div
                          v-for="(error, index) in importResult.data.errors"
                          :key="index"
                          class="mb-2 p-2 bg-red-50 dark:bg-red-900 rounded text-sm"
                        >
                          <strong>Baris {{ error.row }}:</strong> {{ formatError(error) }}
                        </div>
                      </div>
                    </AccordionTab>
                  </Accordion>
                </div>

                <div v-if="importResult.data?.warnings?.length > 0" class="mt-3">
                  <Accordion>
                    <AccordionTab header="Detail Peringatan">
                      <div class="max-h-60 overflow-auto">
                        <div
                          v-for="(warning, index) in importResult.data.warnings"
                          :key="index"
                          class="mb-2 p-2 bg-yellow-50 dark:bg-yellow-900 rounded text-sm"
                        >
                          <strong>Baris {{ warning.row }}:</strong> {{ warning.message }}
                        </div>
                      </div>
                    </AccordionTab>
                  </Accordion>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 lg:col-6">
            <div class="card bg-surface-50 dark:bg-surface-800">
              <h6 class="mb-3">Riwayat Import</h6>

              <DataTable :value="importHistory" :loading="loadingHistory" scrollable scrollHeight="400px">
                <Column field="filename" header="File" style="min-width: 150px"></Column>
                <Column field="status" header="Status" style="min-width: 100px">
                  <template #body="{ data }">
                    <Tag
                      :value="data.status === 'success' ? 'Berhasil' : data.status === 'partial' ? 'Sebagian' : 'Gagal'"
                      :severity="data.status === 'success' ? 'success' : data.status === 'partial' ? 'warn' : 'danger'"
                    />
                  </template>
                </Column>
                <Column field="success_count" header="Berhasil" style="min-width: 80px"></Column>
                <Column field="error_count" header="Gagal" style="min-width: 80px"></Column>
                <Column field="warning_count" header="Peringatan" style="min-width: 100px">
                  <template #body="{ data }">
                    <a
                      v-if="data.warning_count > 0"
                      href="#"
                      class="text-yellow-600 font-semibold cursor-pointer underline hover:text-yellow-800"
                      @click.prevent="showWarnings(data)"
                    >
                      {{ data.warning_count }}
                    </a>
                    <span v-else>0</span>
                  </template>
                </Column>
                <Column field="created_at" header="Tanggal" style="min-width: 150px">
                  <template #body="{ data }">
                    {{ formatDate(data.created_at) }}
                  </template>
                </Column>
              </DataTable>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Warning Details Dialog -->
    <Dialog
      v-model:visible="warningDialog.visible"
      :header="'Peringatan - ' + warningDialog.filename"
      :style="{ width: '600px' }"
      modal
    >
      <div v-if="warningDialog.warnings?.length > 0" class="flex flex-col gap-2">
        <div class="text-sm text-surface-600 dark:text-surface-300 mb-2">
          {{ warningDialog.warnings.length }} baris diimport dengan data tidak lengkap
        </div>
        <div class="max-h-96 overflow-auto">
          <div
            v-for="(warning, index) in warningDialog.warnings"
            :key="index"
            class="mb-2 p-3 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded text-sm"
          >
            <div class="font-semibold text-yellow-700 dark:text-yellow-400">
              Baris {{ warning.row }}
            </div>
            <div class="mt-1">{{ warning.message }}</div>
          </div>
        </div>
      </div>
      <div v-else class="text-surface-500">
        Tidak ada peringatan
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import { ImportService } from '@/service/ImportService';
import { useToast } from 'primevue/usetoast';
import FileUpload from 'primevue/fileupload';
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';
import Message from 'primevue/message';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import Menu from 'primevue/menu';
import Dialog from 'primevue/dialog';

const { token } = useAuth();
const toast = useToast();

const fileUploadRef = ref(null);
const templateMenu = ref(null);
const selectedFile = ref(null);
const uploading = ref(false);
const importResult = ref(null);
const importHistory = ref([]);
const loadingHistory = ref(false);
const warningDialog = ref({
  visible: false,
  filename: '',
  warnings: []
});

const templateItems = ref([
  {
    label: 'Template CSV',
    icon: 'pi pi-file',
    command: () => downloadTemplate('csv')
  },
  {
    label: 'Template XLSX',
    icon: 'pi pi-file-excel',
    command: () => downloadTemplate('xlsx')
  }
]);

const onFileSelect = (event) => {
  selectedFile.value = event.files[0];
  importResult.value = null;
};

const clearFile = () => {
  selectedFile.value = null;
  importResult.value = null;
  if (fileUploadRef.value) {
    fileUploadRef.value.clear();
  }
};

const importFile = async () => {
  if (!selectedFile.value) return;

  uploading.value = true;
  importResult.value = null;

  try {
    const fileExtension = selectedFile.value.name.split('.').pop().toLowerCase();
    const fileType = fileExtension === 'xlsx' ? 'xlsx' : 'csv';

    const response = await ImportService.importFile(token.value, selectedFile.value, fileType);

    const isPartial = response.error_count > 0;
    const hasWarnings = response.warning_count > 0;
    let status = 'success';
    let message = 'Import berhasil!';

    if (isPartial) {
      status = 'partial';
      message = `Import selesai dengan ${response.error_count} baris gagal`;
    } else if (hasWarnings) {
      status = 'success';
      message = `Import berhasil! (${response.warning_count} baris dengan data tidak lengkap)`;
    }

    importResult.value = {
      status,
      message,
      data: response
    };

    toast.add({
      severity: isPartial ? 'warn' : 'success',
      summary: isPartial ? 'Sebagian Berhasil' : 'Berhasil',
      detail: `${response.success_count} baris berhasil` + (response.error_count > 0 ? `, ${response.error_count} gagal` : '') + (response.warning_count > 0 ? `, ${response.warning_count} peringatan` : ''),
      life: 5000
    });

    clearFile();
    loadImportHistory();
  } catch (error) {
    importResult.value = {
      status: 'error',
      message: error.response?.data?.message || 'Import gagal',
      data: error.response?.data
    };

    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Gagal mengimport file',
      life: 3000
    });
  } finally {
    uploading.value = false;
  }
};

const toggleTemplateMenu = (event) => {
  templateMenu.value.toggle(event);
};

const downloadTemplate = async (type) => {
  try {
    const blob = await ImportService.downloadTemplate(token.value, type);
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `template_import.${type}`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);

    toast.add({
      severity: 'success',
      summary: 'Berhasil',
      detail: 'Template berhasil didownload',
      life: 3000
    });
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Gagal mendownload template',
      life: 3000
    });
  }
};

const loadImportHistory = async () => {
  loadingHistory.value = true;
  try {
    const response = await ImportService.getImportHistory(token.value);
    importHistory.value = response.data || [];
  } catch (error) {
    console.error('Error loading import history:', error);
  } finally {
    loadingHistory.value = false;
  }
};

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const showWarnings = (historyData) => {
  warningDialog.value = {
    visible: true,
    filename: historyData.filename,
    warnings: historyData.warnings || []
  };
};

const formatError = (error) => {
  // BNI format uses error.message (string)
  if (error.message) return error.message;
  // Standard format uses error.errors (object with field keys)
  if (error.errors) {
    return Object.values(error.errors).flat().join(', ');
  }
  return 'Unknown error';
};

onMounted(() => {
  loadImportHistory();
});
</script>
