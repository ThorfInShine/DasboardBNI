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

async function fetchManufacturerData() {
    try {
        const response = await axios.get(`${apiBaseUrl}/dashboard/pie-chart`, {
            headers: { 'Authorization': `Bearer ${localStorage.getItem('user_token')}` }
        });
        const data = response.data;
        const documentStyle = getComputedStyle(document.documentElement);

        const colors = [
            documentStyle.getPropertyValue('--p-cyan-500'),
            documentStyle.getPropertyValue('--p-orange-500'),
            documentStyle.getPropertyValue('--p-green-500'),
            documentStyle.getPropertyValue('--p-pink-500'),
            documentStyle.getPropertyValue('--p-purple-500'),
            documentStyle.getPropertyValue('--p-teal-500'),
            documentStyle.getPropertyValue('--p-blue-500'),
            documentStyle.getPropertyValue('--p-yellow-500')
        ];

        chartData.value = {
            labels: data.labels || [],
            datasets: [
                {
                    data: data.counts || [],
                    backgroundColor: colors.slice(0, (data.labels || []).length),
                    hoverBackgroundColor: colors.slice(0, (data.labels || []).length).map(c => c)
                }
            ]
        };
    } catch (error) {
        console.error('Error loading manufacturers:', error);
        chartData.value = { labels: [], datasets: [] };
    } finally {
        loading.value = false;
    }
}

function setChartOptions() {
    const documentStyle = getComputedStyle(document.documentElement);
    const textColor = documentStyle.getPropertyValue('--text-color');

    chartOptions.value = {
        plugins: {
            legend: {
                labels: {
                    usePointStyle: true,
                    color: textColor,
                    font: { size: 11 }
                },
                position: 'bottom'
            }
        }
    };
}

onMounted(() => {
    fetchManufacturerData();
    setChartOptions();
});

watch(isDarkTheme, () => {
    setChartOptions();
});
</script>

<template>
    <div class="card">
        <div class="font-semibold text-xl mb-4">Distribusi Manufacturer</div>
        <div v-if="loading" class="text-center p-4">Loading chart...</div>
        <div v-else-if="chartData && chartData.labels.length > 0" class="flex justify-center">
            <Chart type="doughnut" :data="chartData" :options="chartOptions" class="w-full md:w-80" />
        </div>
        <div v-else class="text-center p-4 text-muted-color">
            <i class="pi pi-chart-pie text-4xl mb-2"></i>
            <p>Belum ada data</p>
        </div>
    </div>
</template>