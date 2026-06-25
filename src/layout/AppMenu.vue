<script setup>
import { ref, computed } from 'vue';
import { useAuth } from '@/composables/useAuth';
import AppMenuItem from './AppMenuItem.vue';

const { isAdmin } = useAuth();

const model = computed(() => {
    const menuItems = [
        {
            label: 'Home',
            items: [{ label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/' }]
        },
        {
            label: 'Data',
            items: [
                { label: 'Data Management', icon: 'pi pi-fw pi-database', to: '/data-management' },
                { label: 'Data Input', icon: 'pi pi-fw pi-pencil', to: '/data-input' },
                { label: 'Data Import', icon: 'pi pi-fw pi-upload', to: '/data-import' },
            ]
        },
    ];

    if (isAdmin.value) {
        menuItems.push({
            label: 'Administration',
            items: [
                { label: 'User Management', icon: 'pi pi-fw pi-users', to: '/user-management' },
                { label: 'Audit Trail', icon: 'pi pi-fw pi-history', to: '/audit-trail' },
                { label: 'Activity Logs', icon: 'pi pi-fw pi-file-check', to: '/activity-logs' },
            ]
        });
    }

    return menuItems;
});
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item.label || i">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
