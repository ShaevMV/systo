import AppLayout from '@/layout/AppLayout.vue';
import { createRouter, createWebHistory } from 'vue-router';
import store from '@/store';

const router = createRouter({
    // BASE_URL из vite --base: локально '/', на staging '/admin/'.
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/',
            redirect: '/admin/dashboard'
        },
        {
            path: '/',
            component: AppLayout,
            children: [
                {
                    path: '/admin/dashboard',
                    name: 'dashboard',
                    component: () => import('@/views/admin/DashboardView.vue'),
                    meta: {
                        requiresAuth: true,
                        role: ['admin']
                    }
                },
                {
                    path: '/admin/qr-orders',
                    name: 'qrOrders',
                    component: () => import('@/views/admin/QrOrderListView.vue'),
                    meta: {
                        requiresAuth: true,
                        role: ['admin']
                    }
                },
                {
                    path: '/admin/templates',
                    name: 'templates',
                    component: () => import('@/views/admin/TemplateListView.vue'),
                    meta: {
                        requiresAuth: true,
                        role: ['admin']
                    }
                },
                {
                    path: '/admin/templates/:id',
                    name: 'templateEditor',
                    component: () => import('@/views/admin/TemplateEditorView.vue'),
                    meta: {
                        requiresAuth: true,
                        role: ['admin']
                    }
                },
                {
                    path: '/admin/template-bindings',
                    name: 'templateBindings',
                    component: () => import('@/views/admin/TemplateBindingListView.vue'),
                    meta: {
                        requiresAuth: true,
                        role: ['admin']
                    }
                },
                {
                    path: '/admin/email-delivery',
                    name: 'emailDelivery',
                    component: () => import('@/views/admin/EmailDeliveryListView.vue'),
                    meta: {
                        requiresAuth: true,
                        role: ['admin']
                    }
                },
                {
                    path: '/admin/locations',
                    name: 'locations',
                    component: () => import('@/views/admin/LocationListView.vue'),
                    meta: {
                        requiresAuth: true,
                        role: ['admin']
                    }
                }
            ]
        },
        {
            path: '/login',
            name: 'login',
            component: () => import('@/views/auth/LoginView.vue'),
            meta: { guest: true }
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'notfound',
            component: () => import('@/views/pages/NotFound.vue')
        }
    ]
});

/**
 * Guard: проверка токена + (опционально) роли по meta.role.
 * Логика перенесена 1:1 из старого FrontEnd/src/router/index.js.
 */
router.beforeEach((to, from, next) => {
    const rawToken = localStorage['user.token'];
    const lifetime = localStorage['user.token.lifetime'];
    const token = rawToken && rawToken !== 'null' && lifetime && Math.trunc(Date.now() / 1000) < +lifetime;

    if (to.matched.some((record) => record.meta.requiresAuth)) {
        if (!token) {
            next({ path: '/login', query: { nextUrl: to.fullPath } });
        } else if (to.matched.some((record) => record.meta.role)) {
            store
                .dispatch('appUser/isCorrectRole', { role: to.meta.role })
                .then(() => next())
                .catch(() => next({ path: '/login' }));
        } else {
            next();
        }
    } else if (to.matched.some((record) => record.meta.guest)) {
        // Гостевые страницы (логин): залогиненного уводим на главную.
        if (!token) {
            next();
        } else {
            next({ path: '/' });
        }
    } else {
        next();
    }
});

export default router;
