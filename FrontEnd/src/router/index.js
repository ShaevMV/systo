import {createRouter, createWebHistory} from 'vue-router'
import HomeView from '../views/HomeView.vue'
import StubView from '../views/StubView.vue'
import LoginView from "../views/auth/LoginView";
import OrderView from "../views/order/OrderView";
import AdminDashboard from "../views/admin/AdminDashboard";
import OrderItemView from "@/views/order/OrderItemView";
import OrderListForAdmin from "@/views/order/OrderListForAdmin.vue";
import Error404 from "@/views/error/Error404.vue";
import ForgotPasswordView from "@/views/auth/ForgotPasswordView.vue";
import ResetPassword from "@/components/Auth/ResetPassword.vue";
import ProfileView from "@/views/user/ProfileView.vue";
import AboutView from "@/views/AboutView.vue";
import OrgsView from "@/views/OrgsView.vue";
import PrivateView from "@/views/PrivateView.vue";
import FaqView from "@/views/FaqView.vue";
import QuestionnaireView from "../views/questionnaire/QuestionnaireView.vue";
import store from '../store';
import PromoCodeView from "@/views/promoCode/PromoCodeView.vue";
import PromoCodeItemView from "@/views/promoCode/PromoCodeItemView.vue";
import InviteLinkView from "@/views/auth/InviteLinkView.vue";
import QuestionnaireListView from "@/views/questionnaire/QuestionnaireListView.vue";
import QuestionnaireRegView from "@/views/questionnaire/QuestionnaireRegView.vue";

const routes = [
    {
        path: '/',
        name: 'stub',
        component: StubView
    },

    // инвайт при старте покупка билета
    {
        path: '/hfjlsd65t4732',
        name: 'home',
        component: HomeView
    },
    // инвайт от пользователя
    {
        path: '/invite/:userId',
        name: 'homeInvite',
        component: HomeView,
    },
    // инвайт на первый раз
    {
        path: '/invite/newUser/:userId',
        name: 'homeInviteNewUser',
        component: HomeView,
    },
    // авторизация
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: {
            'guest': true
        }
    },
    // сброс пароля
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

    // профиль
    {
        path: '/profile',
        name: 'Profile',
        component: ProfileView,
        meta: {
            'requiresAuth': true,
        }
    },


    // создать инвайт ссылку
    {
        path: '/invite',
        name: 'InviteLink',
        component: InviteLinkView,
        meta: {
            'requiresAuth': true,
        }
    },


    // мои закзы - заказы авторизованого пользователя
    {
        path: '/myOrders',
        name: 'Orders',
        component: OrderView,
        meta: {
            'requiresAuth': true,
        }
    },
    // просмотр определённого заказа
    {
        path: '/order/:id',
        name: 'orderItems',
        component: OrderItemView,
        props: true,
        meta: {
            'requiresAuth': true,
        }
    },
    // список всех закаов (АДМИН ПАНЕЛЬ)
    {
        path: '/orders',
        name: 'AllOrders',
        component: OrderListForAdmin,
        meta: {
            'requiresAuth': true,
            'role': ['admin'],
        }
    },


    // анктеты
    // Анкета нового пользователя
    {
        path: '/questionnaire/newUser',
        name: 'QuestionnaireNewUser',
        props: true,
        component: QuestionnaireRegView,
        meta: {
            'requiresAuth': false
        }
    },
    // Анкета от заказа
    {
        path: '/questionnaire/quest/:order_id/:ticket_id',
        name: 'Questionnaire',
        props: true,
        component: QuestionnaireView,
    },
    // Анкета от заказа
    {
        path: '/questionnaire/:order_id/:ticket_id',
        name: 'QuestionnaireOld',
        props: true,
        component: QuestionnaireView,
    },
    // Редактирование анкеты
    {
        path: '/questionnaire/edit/:id',
        name: 'QuestionnaireEdit',
        component: QuestionnaireView,
        props: true,
        meta: {
            'requiresAuth': true,
        }
    },

    // список всех анкет (АДМИН ПАНЕЛЬ)
    {
        path: '/questionnaires/',
        name: 'QuestionnaireList',
        component: QuestionnaireListView,
        meta: {
            'requiresAuth': true,
            'role': ['admin']
        }
    },
    // статические ссылки
    {
        path: '/conditions',
        name: 'Conditions',
        component: AboutView,
    },
    {
        path: '/orgvznos',
        name: 'Orgvznos',
        component: OrgsView,
    },
    {
        path: '/faq',
        name: 'Faq',
        component: FaqView,
    },
    {
        path: '/private',
        name: 'Private',
        component: PrivateView,
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
        path: '/promo-codes',
        name: 'PromoCodes',
        component: PromoCodeView,
        meta: {
            'requiresAuth': true,
            'role': ['admin']
        }
    },

    {
        path: '/promoCode/:id?',
        name: 'promoCodeItem',
        component: PromoCodeItemView,
        props: true,
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
