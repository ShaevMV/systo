import {createRouter, createWebHistory} from 'vue-router'
import HomeView from '../views/HomeView.vue'
import LoginView from "../views/auth/LoginView";
import OrderView from "../views/order/OrderView";
import AdminDashboard from "../views/admin/AdminDashboard";
import OrderItemView from "@/views/order/OrderItemView";
import OrderListForAdmin from "@/views/order/OrderListForAdmin.vue";

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
        path: '/myOrders',
        name: 'Orders',
        component: OrderView,
        meta: {
            'requiresAuth': true,
        }
    },
    {
        path: '/orders/:filter?',
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
            'role': ['all'],
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
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

/**
 * Проверяем наличие маршрута и права доступа
 */
router.beforeEach((to, from, next) => {
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
                    }).then(function (r){
                        console.log(r)
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
