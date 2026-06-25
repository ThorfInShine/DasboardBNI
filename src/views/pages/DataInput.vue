<template>
  <div class="card">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-5 pb-4" style="border-bottom: 1px solid var(--surface-border)">
      <div class="flex items-center justify-center rounded-full" style="width: 2.5rem; height: 2.5rem; background: linear-gradient(135deg, var(--p-primary-100), var(--p-primary-200))">
        <i class="pi pi-desktop" style="color: var(--p-primary-500); font-size: 1.1rem"></i>
      </div>
      <div>
        <div class="text-xl font-bold">Input Data Perangkat</div>
        <div class="text-sm text-muted-color">Isi form berikut untuk menambahkan data perangkat baru</div>
      </div>
    </div>

    <!-- Two Column Layout -->
    <div class="data-input-columns">
      <!-- ========== KOLOM KIRI ========== -->
      <div class="data-input-left">
        <!-- Identitas -->
        <div class="section-badge blue">
          <i class="pi pi-id-card"></i>
          <span>IDENTITAS</span>
        </div>

        <div class="field-row">
          <div class="field-full">
            <label>Computer Name <span class="text-red-500">*</span></label>
            <InputText v-model="form.computer_name" class="w-full" placeholder="T01218804" />
          </div>
        </div>
        <div class="field-row">
          <div class="field-half">
            <label>Device ID</label>
            <InputText v-model="form.device_id" class="w-full" placeholder="ID Perangkat" />
          </div>
          <div class="field-half">
            <label>Agent GUID</label>
            <InputText v-model="form.agentguid" class="w-full" placeholder="GUID" />
          </div>
        </div>
        <div class="field-row">
          <div class="field-half">
            <label>Lokasi <span class="text-red-500">*</span></label>
            <InputText v-model="form.group_id" class="w-full" placeholder="w18.wma.skm_malang" />
          </div>
          <div class="field-half">
            <label>Domain / Workgroup</label>
            <InputText v-model="form.domain_workgroup" class="w-full" placeholder="bni.co.id" />
          </div>
        </div>

        <div class="section-divider"></div>

        <!-- Sistem Operasi -->
        <div class="section-badge cyan">
          <i class="pi pi-code"></i>
          <span>SISTEM OPERASI</span>
        </div>

        <div class="field-row">
          <div style="flex: 2">
            <label>Operating System</label>
            <InputText v-model="form.operating_system" class="w-full" placeholder="Windows 10 Enterprise" />
          </div>
          <div style="flex: 1">
            <label>Versi</label>
            <InputText v-model="form.os_version" class="w-full" placeholder="10.0.19045" />
          </div>
        </div>
      </div>

      <!-- Vertical Divider -->
      <div class="data-input-divider"></div>

      <!-- ========== KOLOM KANAN ========== -->
      <div class="data-input-right">
        <!-- Hardware -->
        <div class="section-badge orange">
          <i class="pi pi-microchip"></i>
          <span>HARDWARE</span>
        </div>

        <div class="field-row">
          <div class="field-half">
            <label>Manufacturer</label>
            <InputText v-model="form.manufacturer" class="w-full" placeholder="HP" />
          </div>
          <div class="field-half">
            <label>Model</label>
            <InputText v-model="form.product_name" class="w-full" placeholder="HP 260 G3 DM" />
          </div>
        </div>
        <div class="field-row">
          <div class="field-third">
            <label>RAM (MB)</label>
            <InputNumber v-model="form.ram_size" class="w-full" placeholder="8192" :min="0" />
          </div>
          <div class="field-third">
            <label>Chasis Type</label>
            <InputText v-model="form.chasis_type" class="w-full" placeholder="Desktop" />
          </div>
          <div class="field-third">
            <label>Serial Number</label>
            <InputText v-model="form.serial_number" class="w-full" placeholder="CZC1234567" />
          </div>
        </div>

        <div class="section-divider"></div>

        <!-- Status & Monitoring -->
        <div class="section-badge green">
          <i class="pi pi-shield"></i>
          <span>STATUS & MONITORING</span>
        </div>

        <div class="field-row">
          <div class="field-half">
            <label>Status EDR <span class="text-red-500">*</span></label>
            <Dropdown
              v-model="form.status_edr"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              class="w-full"
            />
          </div>
          <div class="field-half">
            <label>Online Status</label>
            <Dropdown
              v-model="form.online_status"
              :options="onlineStatusOptions"
              optionLabel="label"
              optionValue="value"
              class="w-full"
            />
          </div>
        </div>
        <div class="field-row">
          <div class="field-full">
            <label>Last Check-in</label>
            <DatePicker v-model="form.last_checkin" class="w-full" showTime hourFormat="24" placeholder="Pilih tanggal & waktu" showIcon />
          </div>
        </div>
      </div>
    </div>

    <!-- Footer Actions -->
    <div class="flex items-center justify-between mt-5 pt-4" style="border-top: 1px solid var(--surface-border)">
      <div class="text-sm text-muted-color">
        <i class="pi pi-info-circle mr-1"></i>
        Field bertanda <span class="text-red-500 font-bold">*</span> wajib diisi
      </div>
      <div class="flex gap-2">
        <Button label="Reset" icon="pi pi-refresh" severity="secondary" outlined @click="resetForm" />
        <Button label="Simpan Data" icon="pi pi-check" @click="submitForm" :loading="submitting" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useAuth } from '@/composables/useAuth';
import { DataService } from '@/service/DataService';
import { useToast } from 'primevue/usetoast';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';

const { token } = useAuth();
const toast = useToast();
const submitting = ref(false);

const statusOptions = [
  { label: 'Terinstall', value: 'active' },
  { label: 'Tidak Aktif', value: 'inactive' }
];

const onlineStatusOptions = [
  { label: 'Offline - Offline (0)', value: '0' },
  { label: 'Online - Not logged in (1)', value: '1' },
  { label: 'Online - Logged in & active (11)', value: '11' },
  { label: 'Online - Logged in but inactive (12)', value: '12' },
  { label: 'Offline - Never check in (199)', value: '199' }
];

const form = reactive({
  computer_name: '',
  device_id: '',
  group_id: '',
  domain_workgroup: '',
  operating_system: '',
  os_version: '',
  manufacturer: '',
  product_name: '',
  ram_size: null,
  serial_number: '',
  chasis_type: '',
  status_edr: 'active',
  online_status: '0',
  agentguid: '',
  last_checkin: null
});

const resetForm = () => {
  form.computer_name = '';
  form.device_id = '';
  form.group_id = '';
  form.domain_workgroup = '';
  form.operating_system = '';
  form.os_version = '';
  form.manufacturer = '';
  form.product_name = '';
  form.ram_size = null;
  form.serial_number = '';
  form.chasis_type = '';
  form.status_edr = 'active';
  form.online_status = '0';
  form.agentguid = '';
  form.last_checkin = null;
};

const submitForm = async () => {
  if (!form.computer_name.trim()) {
    toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Computer Name wajib diisi', life: 3000 });
    return;
  }
  if (!form.group_id.trim()) {
    toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Lokasi (Group ID) wajib diisi', life: 3000 });
    return;
  }

  submitting.value = true;
  try {
    const payload = {
      title: form.computer_name,
      category: form.group_id,
      value: form.ram_size || 0,
      date: form.last_checkin ? new Date(form.last_checkin).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
      description: `${form.operating_system} ${form.os_version} - ${form.manufacturer} ${form.product_name}`.trim(),
      status: form.status_edr,
      metadata: {
        device_id: form.device_id,
        manufacturer: form.manufacturer,
        model: form.product_name,
        os: form.operating_system,
        os_version: form.os_version,
        serial_number: form.serial_number,
        last_checkin: form.last_checkin ? form.last_checkin.toISOString() : '',
        online_status: form.online_status,
        domain_workgroup: form.domain_workgroup,
        chasis_type: form.chasis_type,
        agentguid: form.agentguid,
        import_type: 'manual',
        has_warnings: false
      }
    };

    await DataService.createData(token.value, payload);
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Data perangkat berhasil ditambahkan', life: 3000 });
    resetForm();
  } catch (error) {
    console.error('Error saving data:', error);
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal menyimpan data', life: 3000 });
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped>
.data-input-columns {
  display: flex;
  gap: 0;
}

.data-input-left,
.data-input-right {
  flex: 1;
  min-width: 0;
}

.data-input-left {
  padding-right: 1.5rem;
}

.data-input-right {
  padding-left: 1.5rem;
}

.data-input-divider {
  width: 1px;
  background: var(--surface-border);
  flex-shrink: 0;
}

.section-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  margin-bottom: 1.25rem;
  padding: 0.35rem 0.75rem;
  border-radius: 8px;
}
.section-badge.blue {
  background: rgba(59, 130, 246, 0.08);
  color: #3b82f6;
}
.section-badge.orange {
  background: rgba(249, 115, 22, 0.08);
  color: #f97316;
}
.section-badge.cyan {
  background: rgba(6, 182, 212, 0.08);
  color: #06b6d4;
}
.section-badge.green {
  background: rgba(34, 197, 94, 0.08);
  color: #22c55e;
}

.section-divider {
  border-top: 1px dashed var(--surface-border);
  margin: 1.5rem 0;
}

.field-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.field-full {
  flex: 1;
}

.field-half {
  flex: 1;
}

.field-third {
  flex: 1;
}

.field-row label {
  display: block;
  font-weight: 500;
  font-size: 0.875rem;
  margin-bottom: 0.4rem;
}

@media (max-width: 960px) {
  .data-input-columns {
    flex-direction: column;
  }
  .data-input-left {
    padding-right: 0;
    padding-bottom: 1.5rem;
  }
  .data-input-right {
    padding-left: 0;
    padding-top: 1.5rem;
  }
  .data-input-divider {
    width: 100%;
    height: 1px;
  }
}
</style>
