<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import BestSellingWidget from '@/components/dashboard/BestSellingWidget.vue';
import NotificationsWidget from '@/components/dashboard/NotificationsWidget.vue';
import RecentSalesWidget from '@/components/dashboard/RecentSalesWidget.vue';
import RevenueStreamWidget from '@/components/dashboard/RevenueStreamWidget.vue';
import StatsWidget from '@/components/dashboard/StatsWidget.vue';
import TrendWidget from '@/components/dashboard/TrendWidget.vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';

const { token } = useAuth();
const toast = useToast();

const showSettingsDialog = ref(false);
const loadingPreferences = ref(false);
const savingPreferences = ref(false);

const widgetVisibility = reactive({
  stats: true,
  trend: true,
  recentSales: true,
  revenueStream: true,
  bestSelling: true,
  notifications: true
});

const widgets = [
  { key: 'stats', label: 'Statistics Overview' },
  { key: 'trend', label: 'Trend Chart' },
  { key: 'recentSales', label: 'Recent Sales' },
  { key: 'revenueStream', label: 'Revenue Stream' },
  { key: 'bestSelling', label: 'Best Selling Products' },
  { key: 'notifications', label: 'Notifications' }
];

const loadPreferences = async () => {
  loadingPreferences.value = true;
  try {
    const response = await axios.get('/api/dashboard/preferences', {
      headers: { Authorization: `Bearer ${token.value}` }
    });

    if (response.data.widget_visibility) {
      Object.assign(widgetVisibility, response.data.widget_visibility);
    }
  } catch (error) {
    // Ignore error if preferences don't exist yet (first time user)
    console.log('No preferences found, using defaults');
  } finally {
    loadingPreferences.value = false;
  }
};

const savePreferences = async () => {
  savingPreferences.value = true;
  try {
    await axios.put('/api/dashboard/preferences', {
      widget_visibility: widgetVisibility
    }, {
      headers: { Authorization: `Bearer ${token.value}` }
    });

    toast.add({
      severity: 'success',
      summary: 'Berhasil',
      detail: 'Preferensi dashboard berhasil disimpan',
      life: 3000
    });
    showSettingsDialog.value = false;
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Gagal menyimpan preferensi',
      life: 3000
    });
  } finally {
    savingPreferences.value = false;
  }
};

const resetToDefaults = () => {
  Object.keys(widgetVisibility).forEach(key => {
    widgetVisibility[key] = true;
  });
};

onMounted(() => {
  loadPreferences();
});
</script>

<template>
    <div class="relative">
        <!-- Settings Button -->
        <div class="absolute top-0 right-0 z-10">
            <Button
                icon="pi pi-cog"
                rounded
                text
                @click="showSettingsDialog = true"
                v-tooltip.left="'Customize Dashboard'"
            />
        </div>

        <div class="grid grid-cols-12 gap-8">
            <StatsWidget v-if="widgetVisibility.stats" />

            <div class="col-span-12 xl:col-span-6" v-if="widgetVisibility.trend || widgetVisibility.recentSales">
                <TrendWidget v-if="widgetVisibility.trend" />
                <RecentSalesWidget v-if="widgetVisibility.recentSales" />
            </div>
            <div class="col-span-12 xl:col-span-6" v-if="widgetVisibility.revenueStream || widgetVisibility.bestSelling">
                <RevenueStreamWidget v-if="widgetVisibility.revenueStream" />
                <BestSellingWidget v-if="widgetVisibility.bestSelling" />
            </div>
            <div class="col-span-12" v-if="widgetVisibility.notifications">
                <NotificationsWidget />
            </div>
        </div>

        <!-- Settings Dialog -->
        <Dialog
            v-model:visible="showSettingsDialog"
            :style="{ width: '500px' }"
            header="Dashboard Settings"
            :modal="true"
            :dismissableMask="true"
        >
            <div class="flex flex-col gap-4">
                <p class="text-sm text-surface-600 mb-2">
                    Choose which widgets to display on your dashboard:
                </p>

                <div class="flex flex-col gap-3">
                    <div v-for="widget in widgets" :key="widget.key" class="flex items-center">
                        <Checkbox
                            :inputId="'widget_' + widget.key"
                            v-model="widgetVisibility[widget.key]"
                            :binary="true"
                        />
                        <label :for="'widget_' + widget.key" class="ml-2">{{ widget.label }}</label>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button
                    label="Reset to Defaults"
                    icon="pi pi-refresh"
                    text
                    @click="resetToDefaults"
                />
                <Button
                    label="Cancel"
                    icon="pi pi-times"
                    text
                    @click="showSettingsDialog = false"
                />
                <Button
                    label="Save"
                    icon="pi pi-check"
                    @click="savePreferences"
                    :loading="savingPreferences"
                />
            </template>
        </Dialog>
    </div>
</template>
