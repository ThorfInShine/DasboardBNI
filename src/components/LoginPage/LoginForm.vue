<template>
  <form class="w-full" @submit.prevent="handleLogin">
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
      <label for="password" class="block mb-2 text-sm font-medium text-primary">Password</label>
      <input
        v-model="formData.password"
        type="password"
        id="password"
        class="w-full p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        placeholder="Masukkan password"
        required
      />
    </div>
    
    <!-- ✅ PRIMEVUE BUTTON OFFICIAL -->
    <Button
      type="submit"
      label="Login"
      class="w-full mb-4"
      :loading="loading"
      :disabled="loading"
    />
    
    <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-md text-red-600 text-sm mb-4">
      {{ error }}
    </div>

    <div class="flex w-full justify-center">
      <p class="text-gray-600 text-sm">
        Belum punya akun?
        <router-link to="/auth/regist" class="text-blue-600 font-medium hover:underline ml-1">Daftar</router-link>
      </p>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive } from "vue";
import { useRouter } from "vue-router";
import { useAuth } from "@/composables/useAuth";
import { AuthService } from "@/service/AuthService";
import Button from 'primevue/button';

const router = useRouter();
const { login } = useAuth();

const formData = reactive({
  npp: "",
  password: "",
});

const loading = ref(false);
const error = ref("");

const handleLogin = async () => {
  try {
    loading.value = true;
    error.value = "";

    const response = await AuthService.login(formData.npp, formData.password);

    const userData = {
      name: response.user?.name || response.name,
      email: response.user?.email || response.email,
      npp: response.user?.npp || response.npp || formData.npp,
      role: response.user?.role || response.role
    };

    const userToken = response.token || response.user_token;

    login(userData, userToken);

    router.push("/");
  } catch (err) {
    error.value = err.response?.data?.message || "NPP atau password salah!";
  } finally {
    loading.value = false;
  }
};
</script>