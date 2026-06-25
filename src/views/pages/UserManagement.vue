<template>
  <div class="w-full">
    <div class="card">
        <div class="flex justify-between items-center mb-4">
          <h5>User Management</h5>
          <Button label="Tambah User" icon="pi pi-plus" @click="openNew" />
        </div>

        <DataTable :value="users" :loading="loading" dataKey="id">
          <Column field="name" header="Nama" sortable></Column>
          <Column field="email" header="Email" sortable></Column>
          <Column field="npp" header="NPP" sortable></Column>
          <Column field="role" header="Role" sortable>
            <template #body="{ data }">
              <Tag
                :value="data.role === 'admin' ? 'Admin' : 'User'"
                :severity="data.role === 'admin' ? 'success' : 'info'"
              />
            </template>
          </Column>
          <Column header="Aksi" :exportable="false" style="min-width: 12rem">
            <template #body="slotProps">
              <Button
                icon="pi pi-key"
                outlined
                rounded
                class="mr-2"
                severity="warning"
                @click="openResetPassword(slotProps.data)"
                v-tooltip.top="'Reset Password'"
              />
              <Button
                icon="pi pi-trash"
                outlined
                rounded
                severity="danger"
                @click="confirmDelete(slotProps.data)"
                v-tooltip.top="'Hapus User'"
              />
            </template>
          </Column>
        </DataTable>

        <Dialog v-model:visible="userDialog" :style="{ width: '450px' }" header="Tambah User Baru" :modal="true" :dismissableMask="true">
          <div class="flex flex-col gap-4">
            <div>
              <label for="npp" class="font-bold block mb-2">NPP</label>
              <InputText id="npp" v-model="userForm.npp" required autofocus class="w-full" />
            </div>
            <div>
              <label for="name" class="font-bold block mb-2">Nama</label>
              <InputText id="name" v-model="userForm.name" required class="w-full" />
            </div>
            <div>
              <label for="password" class="font-bold block mb-2">Password</label>
              <Password
                id="password"
                v-model="userForm.password"
                required
                class="w-full"
                inputClass="w-full"
                toggleMask
                :feedback="false"
              />
              <div v-if="userForm.password" class="mt-2">
                <div class="flex items-center gap-2">
                  <div class="flex gap-1 flex-1">
                    <div class="h-1 flex-1 rounded" :style="{ backgroundColor: strengthSegment(1) }"></div>
                    <div class="h-1 flex-1 rounded" :style="{ backgroundColor: strengthSegment(2) }"></div>
                    <div class="h-1 flex-1 rounded" :style="{ backgroundColor: strengthSegment(3) }"></div>
                    <div class="h-1 flex-1 rounded" :style="{ backgroundColor: strengthSegment(4) }"></div>
                  </div>
                  <span class="text-sm font-medium" :style="{ color: strengthColor }">{{ strengthLabel }}</span>
                </div>
              </div>
            </div>
          </div>

          <template #footer>
            <Button label="Batal" icon="pi pi-times" text @click="hideDialog" />
            <Button label="Simpan" icon="pi pi-check" :loading="saving" @click="saveUser" />
          </template>
        </Dialog>

        <Dialog
          v-model:visible="resetPasswordDialog"
          :style="{ width: '450px' }"
          header="Reset Password"
          :modal="true"
          :dismissableMask="true"
        >
          <div class="flex flex-col gap-4">
            <div>
              <label class="font-bold block mb-2">User</label>
              <p class="text-lg">{{ selectedUser?.name }} ({{ selectedUser?.email }})</p>
            </div>
            <div>
              <label for="newPassword" class="font-bold block mb-2">Password Baru</label>
              <Password
                id="newPassword"
                v-model="newPassword"
                required
                class="w-full"
                inputClass="w-full"
                toggleMask
                :feedback="false"
              />
              <div v-if="newPassword" class="mt-2">
                <div class="flex items-center gap-2">
                  <div class="flex gap-1 flex-1">
                    <div class="h-1 flex-1 rounded" :style="{ backgroundColor: resetStrengthSegment(1) }"></div>
                    <div class="h-1 flex-1 rounded" :style="{ backgroundColor: resetStrengthSegment(2) }"></div>
                    <div class="h-1 flex-1 rounded" :style="{ backgroundColor: resetStrengthSegment(3) }"></div>
                    <div class="h-1 flex-1 rounded" :style="{ backgroundColor: resetStrengthSegment(4) }"></div>
                  </div>
                  <span class="text-sm font-medium" :style="{ color: resetStrengthColor }">{{ resetStrengthLabel }}</span>
                </div>
              </div>
            </div>
            <div>
              <label for="confirmNewPassword" class="font-bold block mb-2">Konfirmasi Password Baru</label>
              <Password
                id="confirmNewPassword"
                v-model="confirmNewPassword"
                required
                class="w-full"
                inputClass="w-full"
                :feedback="false"
                toggleMask
              />
            </div>
          </div>

          <template #footer>
            <Button label="Batal" icon="pi pi-times" text @click="hideResetPasswordDialog" />
            <Button label="Reset Password" icon="pi pi-check" :loading="resetting" @click="resetPassword" />
          </template>
        </Dialog>

        <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Konfirmasi" :modal="true" :dismissableMask="true">
          <div class="confirmation-content flex items-center">
            <i class="pi pi-exclamation-triangle mr-3" style="font-size: 2rem" />
            <span v-if="selectedUser"
              >Apakah Anda yakin ingin menghapus user <b>{{ selectedUser.name }}</b>?</span
            >
          </div>
          <template #footer>
            <Button label="Tidak" icon="pi pi-times" text @click="deleteDialog = false" />
            <Button label="Ya" icon="pi pi-check" severity="danger" :loading="deleting" @click="deleteUser" />
          </template>
        </Dialog>
      </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import { UserService } from '@/service/UserService';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Dropdown from 'primevue/dropdown';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';

const { token } = useAuth();
const toast = useToast();

const users = ref([]);
const loading = ref(false);
const userDialog = ref(false);
const resetPasswordDialog = ref(false);
const deleteDialog = ref(false);
const selectedUser = ref(null);
const saving = ref(false);
const resetting = ref(false);
const deleting = ref(false);
const newPassword = ref('');
const confirmNewPassword = ref('');

const userForm = reactive({
  name: '',
  email: '',
  npp: '',
  password: '',
  role: 'user'
});

const roles = ref([
  { label: 'Admin', value: 'admin' },
  { label: 'User', value: 'user' }
]);

const strengthScore = computed(() => {
  const pw = userForm.password;
  if (!pw) return 0;
  let score = 0;
  if (pw.length >= 6) score++;
  if (pw.length >= 8 && /[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw) && /[a-z]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw) && pw.length >= 10) score++;
  return score;
});

const strengthLabel = computed(() => {
  return ['Sangat Lemah', 'Lemah', 'Cukup', 'Baik', 'Kuat'][strengthScore.value];
});

const strengthColor = computed(() => {
  return ['#94a3b8', '#ef4444', '#f59e0b', '#84cc16', '#22c55e'][strengthScore.value];
});

const strengthSegment = (segment) => {
  const inactive = '#e2e8f0';
  return segment <= strengthScore.value ? strengthColor.value : inactive;
};

const resetStrengthScore = computed(() => {
  const pw = newPassword.value;
  if (!pw) return 0;
  let score = 0;
  if (pw.length >= 6) score++;
  if (pw.length >= 8 && /[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw) && /[a-z]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw) && pw.length >= 10) score++;
  return score;
});

const resetStrengthLabel = computed(() => {
  return ['Sangat Lemah', 'Lemah', 'Cukup', 'Baik', 'Kuat'][resetStrengthScore.value];
});

const resetStrengthColor = computed(() => {
  return ['#94a3b8', '#ef4444', '#f59e0b', '#84cc16', '#22c55e'][resetStrengthScore.value];
});

const resetStrengthSegment = (segment) => {
  const inactive = '#e2e8f0';
  return segment <= resetStrengthScore.value ? resetStrengthColor.value : inactive;
};

const loadUsers = async () => {
  loading.value = true;
  try {
    const response = await UserService.getAllUsers(token.value);
    users.value = response.data || [];
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data user', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const openNew = () => {
  resetForm();
  userDialog.value = true;
};

const hideDialog = () => {
  userDialog.value = false;
  resetForm();
};

const resetForm = () => {
  userForm.name = '';
  userForm.email = '';
  userForm.npp = '';
  userForm.password = '';
  userForm.role = 'user';
};

const saveUser = async () => {
  if (!userForm.npp || !userForm.name || !userForm.password) {
    toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Semua field harus diisi', life: 3000 });
    return;
  }

  if (userForm.password.length < 6) {
    toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Password minimal 6 karakter', life: 3000 });
    return;
  }

  try {
    saving.value = true;
    const payload = {
      name: userForm.name,
      npp: userForm.npp,
      password: userForm.password,
      password_confirmation: userForm.password,
      role: 'user'
    };
    await UserService.createUser(token.value, payload);
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'User berhasil ditambahkan', life: 3000 });
    hideDialog();
    loadUsers();
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Gagal menambahkan user',
      life: 3000
    });
  } finally {
    saving.value = false;
  }
};

const openResetPassword = (user) => {
  selectedUser.value = user;
  newPassword.value = '';
  confirmNewPassword.value = '';
  resetPasswordDialog.value = true;
};

const hideResetPasswordDialog = () => {
  resetPasswordDialog.value = false;
  selectedUser.value = null;
  newPassword.value = '';
  confirmNewPassword.value = '';
};

const resetPassword = async () => {
  if (!newPassword.value || !confirmNewPassword.value) {
    toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Semua field harus diisi', life: 3000 });
    return;
  }

  if (newPassword.value !== confirmNewPassword.value) {
    toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Password tidak cocok', life: 3000 });
    return;
  }

  if (newPassword.value.length < 6) {
    toast.add({ severity: 'warn', summary: 'Peringatan', detail: 'Password minimal 6 karakter', life: 3000 });
    return;
  }

  try {
    resetting.value = true;
    await UserService.resetPassword(token.value, selectedUser.value.id, newPassword.value);
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Password berhasil direset', life: 3000 });
    hideResetPasswordDialog();
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Gagal reset password',
      life: 3000
    });
  } finally {
    resetting.value = false;
  }
};

const confirmDelete = (user) => {
  selectedUser.value = user;
  deleteDialog.value = true;
};

const deleteUser = async () => {
  try {
    deleting.value = true;
    await UserService.deleteUser(token.value, selectedUser.value.id);
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'User berhasil dihapus', life: 3000 });
    deleteDialog.value = false;
    selectedUser.value = null;
    loadUsers();
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Gagal menghapus user',
      life: 3000
    });
  } finally {
    deleting.value = false;
  }
};

onMounted(() => {
  loadUsers();
});
</script>
