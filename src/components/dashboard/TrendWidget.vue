<script setup>
import { ref, onMounted, watch } from 'vue';
import { useLayout } from '@/layout/composables/layout';
import axios from 'axios';
import Chart from 'primevue/chart';

const { isDarkTheme } = useLayout();
const chartData = ref(null);
const chartOptions = ref(null);
const loading = ref(true);

const apiBaseUrl = '/api';

async function fetchTrendData() {
    try {
        const response = await axios.get(`${apiBaseUrl}/dashboard/line-chart?days=30`, {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('user_token')}` }
        });
        const data = response.data;
        const documentStyle = getComputedStyle(document.documentElement);

        chartData.value = {
            labels: (data.labels || []).map(d => {
                const date = new Date(d);
                return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
            }),
            datasets: [
                {
                    label: 'Data Diimport',
                    data: data.total_imported || [],
                    fill: true,
                    borderColor: documentStyle.getPropertyValue('--p-cyan-500'),
                    backgroundColor: 'rgba(6, 182, 212, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Error',
                    data: data.total_errors || [],
                    fill: false,
                    borderColor: documentStyle.getPropertyValue('--p-red-500'),
                    backgroundColor: documentStyle.getPropertyValue('--p-red-500'),
                    tension: 0.4
                }
            ]
        };
    } catch (error) {
        console.error('Error fetching trend data:', error);
        chartData.value = { labels: [], datasets: [] };
    } finally {
        loading.value = false;
    }
}

function setChartOptions() {
    const documentStyle = getComputedStyle(document.documentElement);
    const borderColor = documentStyle.getPropertyValue('--surface-border');
    const textMutedColor = documentStyle.getPropertyValue('--text-color-secondary');

    chartOptions.value = {
        maintainAspectRatio: false,
        aspectRatio: 0.6,
        plugins: {
            legend: {
                labels: {
                    color: textMutedColor,
                    usePointStyle: true
                }
            }
        },
        scales: {
            x: {
                ticks: { color: textMutedColor },
                grid: { color: 'transparent' }
            },
            y: {
                ticks: { color: textMutedColor },
                grid: { color: borderColor, drawTicks: false }
            }
        }
    };
}

onMounted(() => {
    fetchTrendData();
    setChartOptions();
});

watch(isDarkTheme, () => {
    setChartOptions();
});
</script>

<template>
    <div class="card">
        <div class="font-semibold text-xl mb-4">Aktivitas Import (30 Hari)</div>
        <div v-if="loading" class="text-center p-4">Loading chart...</div>
        <div v-else-if="chartData && chartData.labels.length > 0">
            <Chart type="line" :data="chartData" :options="chartOptions" class="h-80" />
        </div>
        <div v-else class="text-center p-4 text-muted-color">
            <i class="pi pi-chart-line text-4xl mb-2"></i>
            <p>Belum ada data import</p>
        </div>
    </div>
</template>
