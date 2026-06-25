<script setup>
import { useLayout } from '@/layout/composables/layout';
import { useAuth } from '@/composables/useAuth';
import AppConfigurator from './AppConfigurator.vue';
import bnilogo from '@/assets/pict/Bank_Negara_Indonesia_logo.svg';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import Badge from 'primevue/badge';
import Avatar from 'primevue/avatar';

const { toggleMenu, toggleDarkMode, isDarkTheme } = useLayout();
const { logout: authLogout, user, isAdmin } = useAuth();
const router = useRouter();
const menu = ref();
const showConfigurator = ref(false);

const logout = () => {
  authLogout();
};

const goToProfile = () => {
  router.push('/profile');
};

const goToUserManagement = () => {
  router.push('/user-management');
};

const toggleConfigurator = () => {
  showConfigurator.value = !showConfigurator.value;
};

const items = computed(() => {
  const menuItems = [
    { label: 'Profile', icon: 'pi pi-user', command: goToProfile },
    { label: 'Logout', icon: 'pi pi-sign-out', command: logout }
  ];

  if (isAdmin.value) {
    menuItems.splice(1, 0, { label: 'User Management', icon: 'pi pi-users', command: goToUserManagement });
  }

  return menuItems;
});

const toggle = (event) => {
  menu.value.toggle(event);
};

const configuratorRef = ref(null);

const handleClickOutside = () => {
  if (showConfigurator.value) {
    showConfigurator.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <div class="layout-topbar">
    <div class="layout-topbar-logo-container">
      <button class="layout-menu-button layout-topbar-action" @click="toggleMenu">
        <i class="pi pi-bars"></i>
      </button>
      <router-link to="/" class="layout-topbar-logo flex items-center ml-4">
        <img
          src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/05/Danantara_Indonesia.svg/330px-Danantara_Indonesia.svg.png"
          alt="Danantara Logo"
          style="height: 30px; width: auto; margin-left: 20px; max-width: 100%;"/>
        <img :src="bnilogo" alt="BNI Logo" style="height: 30px; margin-left: 20px; width: auto; max-width: 100%;" />
        
      </router-link>
    </div>

    <div class="layout-topbar-actions">

      <button
        class="layout-topbar-menu-button layout-topbar-action"
        v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }"
      >
        <i class="pi pi-ellipsis-v"></i>
      </button>

      <div class="layout-topbar-menu hidden lg:block">
        <div class="layout-topbar-menu-content w-fit">
          <div class="flex flex-col gap-2">
            <Button
              icon="pi pi-palette"
              @click.stop="toggleConfigurator"
              type="button"
              rounded
            />
            <Button
              type="button"
              icon="pi pi-user"
              rounded
              @click="toggle"
              aria-haspopup="true"
              aria-controls="overlay_menu"
            />
          </div>
          <div v-if="showConfigurator" ref="configuratorRef" style="position: relative;" @click.stop>
            <AppConfigurator />
          </div>
          <Menu
            ref="menu"
            id="overlay_menu"
            :model="items"
            :popup="true"
            class="layout-topbar-menu"
          >
            <template #submenulabel="{ item }">
              <span class="text-primary font-bold">{{ item.label }}</span>
            </template>
            <template #item="{ item, props }">
              <a v-ripple class="flex items-center" v-bind="props.action">
                <span :class="item.icon" />
                <span>{{ item.label }}</span>
                <Badge v-if="item.badge" class="ml-auto" :value="item.badge" />
                <span v-if="item.shortcut" class="ml-auto border border-surface rounded bg-emphasis text-muted-color text-xs p-1">{{ item.shortcut }}</span>
              </a>
            </template>
            <template #end>
              <button
                v-ripple
                class="relative overflow-hidden w-full border-0 bg-transparent flex items-start p-2 pl-4 hover:bg-surface-100 dark:hover:bg-surface-800 rounded-none cursor-pointer transition-colors duration-200">

              </button>
            </template>
          </Menu>
        </div>
      </div>
    </div>
  </div>
</template>