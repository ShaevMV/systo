import { createRouter, createWebHistory } from 'vue-router';

// BASE_URL — из Vite --base (локально '/', на staging '/baza/').
// Главный экран = сразу сканер (решение владельца, дизайн-гейт Ф5).
export const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        { path: '/', redirect: '/scan' },
        { path: '/scan', name: 'scan', component: () => import('@/views/ScanView.vue') },
        { path: '/search', name: 'search', component: () => import('@/views/SearchView.vue') },
        { path: '/shift', name: 'shift', component: () => import('@/views/ShiftView.vue') },
        { path: '/more', name: 'more', component: () => import('@/views/MoreView.vue') },
        // Управление (гейтится по правам через whoami): права доступа (Шаг 4) и далее.
        { path: '/permissions', name: 'permissions', component: () => import('@/views/PermissionMatrixView.vue') }
    ]
});
