<template>
  <form class="w-full" @submit.prevent="handleRegister">
    <div class="mb-5">
      <label for="npp" class="block mb-2 text-sm font-medium text-primary">NPP</label>
      <input
        v-model="formData.npp"
        type="text"
        id="npp"
        class="w-full p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        placeholder="Masukkan npp"
        required
      />
    </div>
    
    <div class="mb-5">
      <label for="name" class="block mb-2 text-sm font-medium text-primary">Nama Lengkap</label>
      <input
        v-model="formData.name"
        type="text"
        id="name"
        class="w-full p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        placeholder="Masukkan nama lengkap"
        required
      />
    </div>
    
    <div class="mb-5">
      <label for="password" class="block mb-2 text-sm font-medium text-primary">Password</label>
      <input
        v-model="formData.password"
        type="password"
        id="password"
        class="w-full p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        placeholder="Minimal 8 karakter"
        required
      />
    </div>
    
    <div class="mb-5">
      <label for="passwordConfirm" class="block mb-2 text-sm font-medium text-primary">Konfirmasi Password</label>
      <input
        v-model="formData.password_confirmation"
        type="password"
        id="passwordConfirm"
        class="w-full p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        placeholder="Ulangi password"
        required
      />
    </div>
    
    <!-- ✅ PRIMEVUE BUTTON OFFICIAL -->
    <Button
      type="submit"
      label="Daftar"
      class="w-full mb-4"
      :loading="loading"
      :disabled="loading"
    />
    
    <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-md text-red-600 text-sm mb-4">
      {{ error }}
    </div>

    <div class="flex w-full justify-center">
      <p class="text-gray-600 text-sm">
        Sudah punya akun?
        <router-link to="/auth/login" class="text-blue-600 font-medium hover:underline ml-1">Login</router-link>
      </p>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import Button from 'primevue/button';

const router = useRouter();

const formData = reactive({
  npp: "",
  name: "",
  password: "",
  password_confirmation: "",
});

const loading = ref(false);
const error = ref("");

const handleRegister = async () => {
  try {
    loading.value = true;
    error.value = "";

    const response = await axios.post("/api/register", formData);

    localStorage.setItem("token", response.data.token);
    localStorage.setItem("user", JSON.stringify(response.data.user));

    router.push("/");
  } catch (err) {
    error.value = err.response?.data?.message || "Gagal mendaftar!";
  } finally {
    loading.value = false;
  }
};
</script>