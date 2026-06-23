import { createApp } from 'vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import Aura from '@primeuix/themes/aura';
import 'primeicons/primeicons.css';

import App from '@/App.vue';
import { router } from '@/router';

const app = createApp(App);

app.use(router);
app.use(PrimeVue, {
    theme: { preset: Aura, options: { darkModeSelector: '.app-dark' } }
});
// Единый механизм уведомлений КПП (Фаза A) — PrimeVue Toast; обёртка в @/lib/notify.
app.use(ToastService);

app.mount('#app');
