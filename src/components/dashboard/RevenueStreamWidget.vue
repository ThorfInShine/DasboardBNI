<script setup>
import { ref, onMounted, watch } from 'vue';
import { useLayout } from '@/layout/composables/layout';
import axios from 'axios';
import Chart from 'primevue/chart';

const { getPrimary, getSurface, isDarkTheme } = useLayout();
const chartData = ref(null);
const chartOptions = ref(null);
const loading = ref(true);

const apiBaseUrl = '/api';

async function fetchLocationData() {
    try {
        const response = await axios.get(`${apiBaseUrl}/dashboard/bar-chart`, {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('user_token')}` }
        });
        const data = response.data;
        const documentStyle = getComputedStyle(document.documentElement);

        // Shorten long labels
        const labels = (data.labels || []).map(l => {
            if (l && l.length > 20) return l.substring(0, 18) + '...';
            return l;
        });

        chartData.value = {
            labels: labels,
            datasets: [
                {
                    label: 'Jumlah Perangkat',
                    backgroundColor: documentStyle.getPropertyValue('--p-primary-400'),
                    data: data.counts || [],
                    barThickness: 28
                }
            ]
        };
    } catch (error) {
        console.error('Error fetching location data:', error);
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
        indexAxis: 'y',
        maintainAspectRatio: false,
        aspectRatio: 0.8,
        scales: {
            x: {
                ticks: { color: textMutedColor },
                grid: { color: borderColor, drawTicks: false }
            },
            y: {
                ticks: { color: textMutedColor, font: { size: 11 } },
                grid: { color: 'transparent' }
            }
        },
        plugins: {
            legend: { display: false }
        }
    };
}

onMounted(() => {
    fetchLocationData();
    setChartOptions();
});

watch([getPrimary, getSurface, isDarkTheme], () => {
    setChartOptions();
});
</script>

<template>
    <div class="card">
        <div class="font-semibold text-xl mb-4">Perangkat per Lokasi</div>
        <div v-if="loading" class="text-center p-4">Loading chart...</div>
        <div v-else-if="chartData && chartData.labels.length > 0">
            <Chart type="bar" :data="chartData" :options="chartOptions" class="h-80" />
        </div>
        <div v-else class="text-center p-4 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-2"></i>
            <p>Belum ada data</p>
        </div>
    </div>
</template>