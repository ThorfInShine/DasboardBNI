<template>
    <div class="surface-ground flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
        <div class="flex flex-col items-center justify-center">
            <div style="border-radius: 56px; padding: 0.3rem; background: linear-gradient(180deg, rgba(247, 149, 51, 0.4) 10%, rgba(243, 112, 85, 0))">
                <div class="w-full bg-surface-0 dark:bg-surface-900 py-20 px-8 sm:px-20 flex flex-col items-center" style="border-radius: 53px">
                    <span class="text-primary font-bold text-6xl">403</span>
                    <h1 class="text-surface-900 dark:text-surface-0 font-bold text-3xl mb-2">Akses Ditolak</h1>
                    <p class="text-muted-color mb-8">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
                    <router-link to="/" class="no-underline">
                        <Button label="Kembali ke Dashboard" icon="pi pi-home" class="p-button-text" />
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import Button from 'primevue/button';
</script>
