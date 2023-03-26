import {createRouter, createWebHistory} from 'vue-router'
import HomeView from '../views/HomeView.vue'
import LoginView from "../views/auth/LoginView";
import OrderView from "../views/order/OrderView";
import AdminDashboard from "../views/admin/AdminDashboard";
import OrderItemView from "@/views/order/OrderItemView";
import OrderListForAdmin from "@/views/order/OrderListForAdmin.vue";
import RegView from "@/views/auth/RegView.vue";
import Error404 from "@/views/error/Error404.vue";
import ForgotPasswordView from "@/views/auth/ForgotPasswordView.vue";
import ResetPassword from "@/components/Auth/ResetPassword.vue";
import ProfileView from "@/views/user/ProfileView.vue";
import AboutView from "@/views/AboutView.vue";
import store from '../store'
import PromoCodeView from "@/views/promoCode/PromoCodeView.vue";

const routes = [
    {
        path: '/',
        name: 'home',
        component: HomeView
    },
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: {
            'guest': true
        }
    },
    {
        path: '/registration',
        name: 'registration',
        component: RegView,
        meta: {
            'guest': true
        }
    },
    {
        path: '/forgotPassword',
        name: 'forgotPassword',
        component: ForgotPasswordView,
        meta: {
            'guest': true
        }
    },
    {
        path: '/resetPassword/:token',
        name: 'resetPassword',
        component: ResetPassword,
        meta: {
            'guest': true
        }
    },
    {
        path: '/myOrders',
        name: 'Orders',
        component: OrderView,
        meta: {
            'requiresAuth': true,
        }
    },
    {
        path: '/profile',
        name: 'Profile',
        component: ProfileView,
        meta: {
            'requiresAuth': true,
        }
    },
    {
        path: '/orders',
        name: 'AllOrders',
        component: OrderListForAdmin,
        meta: {
            'requiresAuth': true,
            'role': ['admin'],
        }
    },
    {
        path: '/order/:id',
        name: 'orderItems',
        component: OrderItemView,
        meta: {
            'requiresAuth': true,
        }
    },
    {
        path: '/admin',
        name: 'adminDashboard',
        component: AdminDashboard,
        meta: {
            'requiresAuth': true,
            'role': ['admin']
        }
    },
    {
        path: '/conditions',
        name: 'Conditions',
        component: AboutView,
    },
    {
        path: '/promo-codes',
        name: 'PromoCodes',
        component: PromoCodeView,
        meta: {
            'requiresAuth': true,
            'role': ['admin']
        }
    },
    {
        path: '/promoCode/:id',
        name: 'promoCodeItem',
        component: OrderItemView,
        meta: {
            'requiresAuth': true,
            'role': ['admin']
        }
    },
    {
        path: '/:pathMatch(.*)*',
        component: Error404,
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

/**
 * Проверяем наличие маршрута и права доступа
 */
router.beforeEach((to, from, next) => {
        store.commit('HIDE_MENU');
        let token = (localStorage['user.token'] !== undefined && localStorage['user.token'] !== '' && localStorage['user.token'] !== null);
        if (to.matched.some(record => record.meta.requiresAuth)) {
            if (!token) {
                next({
                    path: '/login',
                    query: {
                        nextUrl: to.fullPath,
                    }
                });
            } else {
                if(to.matched.some(record => record.meta.role)) {
                    window.store.dispatch('appUser/isCorrectRole',{
                        'role':to.meta.role
                    }).then(function (){
                        next();
                    })
                } else {
                    next();
                }
            }
        } else if (to.matched.some(record => record.meta.guest)) {
            if (!token) {
                next();
            } else {
                next({path: '/'});
            }
        } else {
            next();
        }
    }
);
export default router;
