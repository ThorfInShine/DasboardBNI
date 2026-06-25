<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const imports = ref([]);
const loading = ref(true);

const apiBaseUrl = '/api';

onMounted(async () => {
    try {
        const response = await axios.get(`${apiBaseUrl}/dashboard/stats`, {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('user_token')}` }
        });
        imports.value = response.data.recent_imports || [];
    } catch (error) {
        console.error('Error fetching recent imports:', error);
    } finally {
        loading.value = false;
    }
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor(diff / (1000 * 60));

    if (minutes < 60) return `${minutes} menit lalu`;
    if (hours < 24) return `${hours} jam lalu`;
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const getStatusColor = (status) => {
    switch (status) {
        case 'success': return 'bg-green-100 dark:bg-green-400/10';
        case 'partial': return 'bg-yellow-100 dark:bg-yellow-400/10';
        case 'failed': return 'bg-red-100 dark:bg-red-400/10';
        default: return 'bg-blue-100 dark:bg-blue-400/10';
    }
};

const getStatusIcon = (status) => {
    switch (status) {
        case 'success': return 'pi pi-check-circle text-green-500';
        case 'partial': return 'pi pi-exclamation-circle text-yellow-500';
        case 'failed': return 'pi pi-times-circle text-red-500';
        default: return 'pi pi-info-circle text-blue-500';
    }
};
</script>

<template>
    <div class="card">
        <div class="font-semibold text-xl mb-4">Riwayat Import Terbaru</div>

        <div v-if="loading" class="text-center p-4">Loading...</div>

        <ul v-else-if="imports.length > 0" class="p-0 mx-0 mt-0 mb-0 list-none">
            <li v-for="item in imports" :key="item.id" class="flex items-center py-3 border-b border-surface last:border-b-0">
                <div class="w-10 h-10 flex items-center justify-center rounded-full mr-3 shrink-0" :class="getStatusColor(item.status)">
                    <i :class="getStatusIcon(item.status)" class="!text-lg"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-surface-900 dark:text-surface-0 text-sm font-medium truncate">
                        {{ item.filename }}
                    </div>
                    <div class="text-muted-color text-xs mt-1">
                        {{ item.success_count?.toLocaleString('id-ID') }} berhasil
                        <span v-if="item.error_count > 0" class="text-red-500"> · {{ item.error_count }} gagal</span>
                        <span v-if="item.warning_count > 0" class="text-yellow-500"> · {{ item.warning_count }} peringatan</span>
                    </div>
                </div>
                <div class="text-muted-color text-xs shrink-0 ml-2">
                    {{ formatDate(item.created_at) }}
                </div>
            </li>
        </ul>

        <div v-else class="text-center p-4 text-muted-color">
            <i class="pi pi-inbox text-4xl mb-2"></i>
            <p>Belum ada riwayat import</p>
        </div>
    </div>
</template>