<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const totalDevices = ref(0);
const totalEdrInstalled = ref(0);
const totalInactive = ref(0);
const recentDevices = ref(0);
const loading = ref(true);

const apiBaseUrl = '/api';

onMounted(async () => {
    try {
        const response = await axios.get(`${apiBaseUrl}/dashboard/stats`, {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('user_token')}` }
        });
        const data = response.data;
        totalDevices.value = data.total_devices || 0;
        totalEdrInstalled.value = data.total_edr_installed || 0;
        totalInactive.value = data.total_inactive || 0;
        recentDevices.value = data.recent_devices || 0;
    } catch (error) {
        console.error('Error loading stats:', error);
    } finally {
        loading.value = false;
    }
});

const formatNumber = (num) => {
    return num.toLocaleString('id-ID');
};
</script>

<template>
    <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Total Perangkat</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">{{ formatNumber(totalDevices) }}</div>
                </div>
                <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-desktop text-blue-500 !text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">EDR Terinstall</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">{{ formatNumber(totalEdrInstalled) }}</div>
                </div>
                <div class="flex items-center justify-center bg-green-100 dark:bg-green-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-shield text-green-500 !text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Tidak Aktif</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">{{ formatNumber(totalInactive) }}</div>
                </div>
                <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-exclamation-triangle text-orange-500 !text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">Baru (7 Hari)</span>
                    <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">{{ formatNumber(recentDevices) }}</div>
                </div>
                <div class="flex items-center justify-center bg-cyan-100 dark:bg-cyan-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                    <i class="pi pi-plus-circle text-cyan-500 !text-xl"></i>
                </div>
            </div>
        </div>
    </div>
</template>