import {createRouter, createWebHistory} from "vue-router";
import Dashboard from "@/views/Dashboard.vue";
import Tables from "@/views/Tables.vue";
import Billing from "@/views/Billing.vue";
import VirtualReality from "@/views/VirtualReality.vue";
import Profile from "@/views/Profile.vue";
import Rtl from "@/views/Rtl.vue";
import SignIn from "@/views/SignIn.vue";
import SignUp from "@/views/SignUp.vue";
import OrderItemView from "@/views/order/OrderItemView.vue";

const routes = [
    {
        path: "/",
        name: "/",
        redirect: "/profile",
    },
    {
        path: "/dashboard",
        name: "Dashboard",
        component: Dashboard,
        meta: {
            'requiresAuth': true,
            'role': ['admin'],
        }
    },
    {
        path: "/order",
        name: "Orders",
        component: Tables,
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
        path: "/billing",
        name: "Billing",
        component: Billing,
    },
    {
        path: "/virtual-reality",
        name: "Virtual Reality",
        component: VirtualReality,
    },
    {
        path: "/profile",
        name: "Profile",
        component: Profile,
    },
    {
        path: "/rtl-page",
        name: "Rtl",
        component: Rtl,
    },
    {
        path: "/sign-in",
        name: "Sign In",
        component: SignIn,
    },
    {
        path: "/sign-up",
        name: "Sign Up",
        component: SignUp,
    },
];

const router = createRouter({
    history: createWebHistory(process.env.BASE_URL),
    routes,
    linkActiveClass: "active",
});

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
