import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import store from './store';
import axios from 'axios';

import Aura from '@primeuix/themes/aura';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';

import '@/assets/tailwind.css';
import '@/assets/styles.scss';

// API URL из .env (VITE_API_URL).
// Dev:  http://api.tickets.loc/
// Prod: https://api.spaceofjoy.ru/ (или staging)
axios.defaults.baseURL = import.meta.env.VITE_API_URL || 'http://api.tickets.loc/';
axios.defaults.withCredentials = true;

// Флаг и очередь для предотвращения race condition при обновлении токена.
let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
    failedQueue.forEach((prom) => (error ? prom.reject(error) : prom.resolve(token)));
    failedQueue = [];
};

// Подставляет актуальный токен в каждый исходящий запрос.
axios.interceptors.request.use(
    function (config) {
        const token = localStorage['user.token'];
        if (token) {
            config.headers.Authorization = token;
        }
        return config;
    },
    function (error) {
        return Promise.reject(error);
    }
);

// Перехватывает 401 — обновляет токен ровно один раз, повторяет все накопившиеся запросы.
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const originalRequest = error.config;
        const isAuthEndpoint = ['/api/refresh', '/api/login', '/api/logout'].some((url) => originalRequest.url === url);

        if (error.response?.status === 401 && !originalRequest._retry && !isAuthEndpoint) {
            if (isRefreshing) {
                // Ставим запрос в очередь — он выполнится после получения нового токена.
                return new Promise((resolve, reject) => {
                    failedQueue.push({ resolve, reject });
                })
                    .then((token) => {
                        originalRequest.headers.Authorization = token;
                        return axios(originalRequest);
                    })
                    .catch((err) => Promise.reject(err));
            }

            originalRequest._retry = true;
            isRefreshing = true;

            return store
                .dispatch('appUser/tokenRefresh')
                .then((token) => {
                    processQueue(null, token);
                    originalRequest.headers.Authorization = token;
                    return axios(originalRequest);
                })
                .catch((err) => {
                    processQueue(err, null);
                    store.dispatch('appUser/logOut');
                    return Promise.reject(err);
                })
                .finally(() => {
                    isRefreshing = false;
                });
        }

        return Promise.reject(error);
    }
);

const app = createApp(App);

app.use(store);
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
app.use(ConfirmationService);

app.mount('#app');
