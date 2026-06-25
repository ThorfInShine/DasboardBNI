import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { useAuth } from '@/composables/useAuth';

import Aura from '@primeuix/themes/aura';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';

import '@/assets/styles.scss';

const app = createApp(App);

app.use(router);
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: '.app-dark'
        }
    }
});
app.use(ToastService);
app.component('Toast', Toast);
app.use(ConfirmationService);

const { initAuth } = useAuth();
initAuth();

app.mount('#app');