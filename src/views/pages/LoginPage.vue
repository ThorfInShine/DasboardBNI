<template>
  <section 
    class="h-screen flex items-center justify-center"
    :style="`background-image: url(${bgImage}); background-size: cover; background-position: center; background-repeat: no-repeat;`"
  >
    <div class="container h-full flex items-center justify-center">
      <!-- FRAME PUTIH DI ATAS BACKGROUND -->
      <div class="flex max-w-xl w-full gap-6 items-center bg-white rounded-xl shadow-xl p-8">
        <img :src="Logo" alt="Logo" class="object-contain w-1/3" />
        <LoginForm />
      </div>
    </div>
  </section>
</template>

<script setup>
import LoginForm from "@/components/LoginPage/LoginForm.vue";
import Logo from "@/assets/pict/logo.png";
import bgImage from "@/assets/pict/bg.jpg"; // ✅ BACKGROUND CUSTOM!
import { onMounted } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();

onMounted(() => {
  const token = localStorage.getItem("user_token");
  if (token) router.push("/");
});
</script>