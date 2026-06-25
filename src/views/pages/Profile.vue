<template>
  <div class="grid">
    <div class="col-12">
      <div class="card">
        <h5>Profil Saya</h5>
        <p class="text-muted-color mb-4">Kelola informasi profil dan password Anda</p>

        <div class="grid">
          <!-- Profile Information Card -->
          <div class="col-12 lg:col-6">
            <div class="card bg-surface-50 dark:bg-surface-800">
              <h6 class="mb-3">Informasi Profil</h6>

              <div class="mb-3">
                <label for="name" class="block mb-2 font-semibold">Nama</label>
                <InputText
                  id="name"
                  v-model="profileForm.name"
                  class="w-full"
                  :disabled="!isEditingProfile"
                />
              </div>

              <div class="mb-3">
                <label for="email" class="block mb-2 font-semibold">Email</label>
                <InputText
                  id="email"
                  v-model="profileForm.email"
                  class="w-full"
                  disabled
                />
              </div>

              <div class="mb-3">
                <label for="npp" class="block mb-2 font-semibold">NPP</label>
                <InputText
                  id="npp"
                  v-model="profileForm.npp"
                  class="w-full"
                  disabled
                />
              </div>

              <div class="mb-4">
                <label for="role" class="block mb-2 font-semibold">Role</label>
                <Tag
                  :value="profileForm.role === 'admin' ? 'Admin' : 'User'"
                  :severity="profileForm.role === 'admin' ? 'success' : 'info'"
                />
              </div>

              <div class="flex gap-2">
                <Button
                  v-if="!isEditingProfile"
                  label="Edit Profil"
                  icon="pi pi-pencil"
                  @click="startEditProfile"
                />
                <template v-else>
                  <Button
                    label="Simpan"
                    icon="pi pi-check"
                    :loading="savingProfile"
                    @click="saveProfile"
                  />
                  <Button
                    label="Batal"
                    icon="pi pi-times"
                    severity="secondary"
                    @click="cancelEditProfile"
                  />
                </template>
              </div>
            </div>
          </div>

          <!-- Change Password Card -->
          <div class="col-12 lg:col-6">
            <div class="card bg-surface-50 dark:bg-surface-800">
              <h6 class="mb-3">Ubah Password</h6>

              <div class="mb-3">
                <label for="currentPassword" class="block mb-2 font-semibold">Password Saat Ini</label>
                <Password
                  id="currentPassword"
                  v-model="passwordForm.currentPassword"
                  class="w-full"
                  inputClass="w-full"
                  :feedback="false"
                  toggleMask
                />
              </div>

              <div class="mb-3">
                <label for="newPassword" class="block mb-2 font-semibold">Password Baru</label>
                <Password
                  id="newPassword"
                  v-model="passwordForm.newPassword"
                  class="w-full"
                  inputClass="w-full"
                  toggleMask
                />
              </div>

              <div class="mb-4">
                <label for="confirmPassword" class="block mb-2 font-semibold">Konfirmasi Password Baru</label>
                <Password
                  id="confirmPassword"
                  v-model="passwordForm.confirmPassword"
                  class="w-full"
                  inputClass="w-full"
                  :feedback="false"
                  toggleMask
                />
              </div>

              <Button
                label="Ubah Password"
                icon="pi pi-key"
                :loading="changingPassword"
                @click="changePassword"
                class="w-full"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import { AuthService } from '@/service/AuthService';
import { useToast } from 'primevue/usetoast';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const { user, token, updateUser } = useAuth();
const toast = useToast();

const isEditingProfile = ref(false);
const savingProfile = ref(false);
const changingPassword = ref(false);

const profileForm = reactive({
  name: '',
  email: '',
  npp: '',
  role: ''
});

const originalProfile = reactive({
  name: '',
  email: '',
  npp: '',
  role: ''
});

const passwordForm = reactive({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
});

const loadProfile = () => {
  if (user.value) {
    profileForm.name = user.value.name || '';
    profileForm.email = user.value.email || '';
    profileForm.npp = user.value.npp || '';
    profileForm.role = user.value.role || '';

    originalProfile.name = profileForm.name;
    originalProfile.email = profileForm.email;
    originalProfile.npp = profileForm.npp;
    originalProfile.role = profileForm.role;
  }
};

const startEditProfile = () => {
  isEditingProfile.value = true;
};

const cancelEditProfile = () => {
  profileForm.name = originalProfile.name;
  profileForm.email = originalProfile.email;
  profileForm.npp = originalProfile.npp;
  profileForm.role = originalProfile.role;
  isEditingProfile.value = false;
};

const saveProfile = async () => {
  try {
    savingProfile.value = true;

    const response = await AuthService.updateProfile(token.value, {
      name: profileForm.name
    });

    updateUser({ name: profileForm.name });
    originalProfile.name = profileForm.name;

    toast.add({
      severity: 'success',
      summary: 'Berhasil',
      detail: 'Profil berhasil diperbarui',
      life: 3000
    });

    isEditingProfile.value = false;
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Gagal',
      detail: error.response?.data?.message || 'Gagal memperbarui profil',
      life: 3000
    });
  } finally {
    savingProfile.value = false;
  }
};

const changePassword = async () => {
  if (!passwordForm.currentPassword || !passwordForm.newPassword || !passwordForm.confirmPassword) {
    toast.add({
      severity: 'warn',
      summary: 'Peringatan',
      detail: 'Semua field password harus diisi',
      life: 3000
    });
    return;
  }

  if (passwordForm.newPassword !== passwordForm.confirmPassword) {
    toast.add({
      severity: 'warn',
      summary: 'Peringatan',
      detail: 'Password baru dan konfirmasi password tidak cocok',
      life: 3000
    });
    return;
  }

  if (passwordForm.newPassword.length < 6) {
    toast.add({
      severity: 'warn',
      summary: 'Peringatan',
      detail: 'Password baru minimal 6 karakter',
      life: 3000
    });
    return;
  }

  try {
    changingPassword.value = true;

    await AuthService.changePassword(
      token.value,
      passwordForm.currentPassword,
      passwordForm.newPassword,
      passwordForm.confirmPassword
    );

    toast.add({
      severity: 'success',
      summary: 'Berhasil',
      detail: 'Password berhasil diubah',
      life: 3000
    });

    passwordForm.currentPassword = '';
    passwordForm.newPassword = '';
    passwordForm.confirmPassword = '';
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Gagal',
      detail: error.response?.data?.message || 'Gagal mengubah password',
      life: 3000
    });
  } finally {
    changingPassword.value = false;
  }
};

onMounted(() => {
  loadProfile();
});
</script>
