<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const devices = ref([]);
const loading = ref(true);

const apiBaseUrl = '/api';

onMounted(async () => {
    try {
        const response = await axios.get(`${apiBaseUrl}/data`, {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('user_token')}` },
            params: { per_page: 5, sort_by: 'created_at', sort_order: 'desc' }
        });
        devices.value = response.data.data || [];
    } catch (error) {
        console.error('Error loading devices:', error);
        devices.value = [];
    } finally {
        loading.value = false;
    }
});

const formatRAM = (value) => {
    const num = parseFloat(value);
    if (isNaN(num)) return '-';
    return num.toLocaleString('id-ID') + ' MB';
};
</script>

<template>
    <div class="card">
        <div class="font-semibold text-xl mb-4">Perangkat Terbaru</div>
        <DataTable v-if="!loading" :value="devices" :rows="5" responsiveLayout="scroll" class="p-datatable-sm">
            <Column header="Hostname" style="width: 25%">
                <template #body="{ data }">
                    <span class="font-semibold">{{ data.title || '-' }}</span>
                </template>
            </Column>
            <Column header="Perangkat" style="width: 30%">
                <template #body="{ data }">
                    <div>
                        <div class="text-sm">{{ data.metadata?.manufacturer || '-' }}</div>
                        <div class="text-xs text-muted-color">{{ data.metadata?.model || '-' }}</div>
                    </div>
                </template>
            </Column>
            <Column header="RAM" style="width: 20%">
                <template #body="{ data }">
                    {{ formatRAM(data.value) }}
                </template>
            </Column>
            <Column header="Status" style="width: 25%">
                <template #body="{ data }">
                    <Tag
                        :value="data.status === 'active' ? 'Terinstall' : 'Tidak Aktif'"
                        :severity="data.status === 'active' ? 'success' : 'danger'"
                    />
                </template>
            </Column>
        </DataTable>
        <div v-else class="text-center p-4">Loading...</div>
    </div>
</template>