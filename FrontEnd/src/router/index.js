import {createRouter, createWebHistory} from 'vue-router'
import HomeView from '../views/HomeView.vue'
import FriendlyView from '../views/FriendlyView.vue'
import StubView from '../views/StubView.vue'
import LoginView from "../views/auth/LoginView";
import OrderView from "../views/order/OrderView";
import AdminDashboard from "../views/admin/AdminDashboard";
import OrderItemView from "@/views/order/OrderItemView";
import OrderListForAdmin from "@/views/order/OrderListForAdmin.vue";
import OrderListForFriendly from "@/views/order/OrderListForFriendly.vue";
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
import LiveTicketView from "@/views/ticket/LiveTicketView.vue";

import TicketTypeListView from "@/views/ticketType/TicketTypeListView.vue";
import TicketTypeItemView from "@/views/ticketType/TicketTypeItemView.vue";

import TypesOfPaymentListView from "@/views/typesOfPayment/TypesOfPaymentListView.vue";
import TypesOfPaymentItemView from "@/views/typesOfPayment/TypesOfPaymentItemView.vue";

import QuestionnaireTypeListView from "@/views/questionnaireType/QuestionnaireTypeListView.vue";
import QuestionnaireTypeItemView from "@/views/questionnaireType/QuestionnaireTypeItemView.vue";
import LocationListView from "@/views/location/LocationListView.vue";
import LocationItemView from "@/views/location/LocationItemView.vue";
import OrderListsListView from "@/views/order/OrderListsListView.vue";
import OrderListForCurator from "@/views/order/OrderListForCurator.vue";
import CreateListOrderView from "@/views/order/CreateListOrderView.vue";

import AccountListView from "@/views/account/AccountListView.vue";

import RegView from "@/views/auth/RegView.vue";
import RegCuratorView from "@/views/auth/RegCuratorView.vue";

const routes = [
    {
        path: '/',
        name: 'stub',
        component: StubView
    },
    // инвайт при старте покупка билета
    {
        path: '/frendlyOrder',
        name: 'frendlyOrder',
        component: FriendlyView,
        meta: {
            'requiresAuth': true,
            'role': ['admin', 'pusher', 'pusher_curator'],
        }
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
    {
        path: '/regGydhf',
        name: 'registration',
        component: RegView,
        meta: {
            'guest': true
        }
    },
    {
        path: '/regCuratorTgdtr64',
        name: 'registrationCurator',
        component: RegCuratorView,
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
            'role': ['admin', 'seller'],
        }
    },
    // список всех закаов (АДМИН ПАНЕЛЬ)
    {
        path: '/ordersFriendly',
        name: 'AllOrdersFriendly',
        component: OrderListForFriendly,
        meta: {
            'requiresAuth': true,
            'role': ['admin', 'pusher', 'pusher_curator'],
        }
    },

    // Заказы-списки (admin/manager)
    {
        path: '/ordersLists',
        name: 'AllOrdersLists',
        component: OrderListsListView,
        meta: {
            'requiresAuth': true,
            'role': ['admin', 'manager'],
        }
    },
    // Свои заказы-списки (куратор)
    {
        path: '/curatorOrders',
        name: 'CuratorOrders',
        component: OrderListForCurator,
        meta: {
            'requiresAuth': true,
            'role': ['admin', 'curator', 'pusher_curator'],
        }
    },
    // Форма создания заказа-списка (куратор)
    {
        path: '/curatorOrders/create',
        name: 'CreateListOrder',
        component: CreateListOrderView,
        meta: {
            'requiresAuth': true,
            'role': ['admin', 'curator', 'pusher_curator'],
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
        path: '/questionnaire/guest/:order_id/:ticket_id',
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
            'role': ['admin', 'manager']
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
        path: '/ticket/live/:id?',
        name: 'liveTicketView',
        component: LiveTicketView,
        props: true,
    },

    // Типы билтетов
    {
        path: '/ticketType/list',
        name: 'TicketTypeListView',
        component: TicketTypeListView,
        props: true,
        meta: {
            'requiresAuth': true,
            'role': ['admin']
        }
    },
    {
        path: '/ticketType/:id?',
        name: 'TicketTypeItemView',
        component: TicketTypeItemView,
        props: true,
        meta: {
            'requiresAuth': true,
            'role': ['admin']
        }
    },

    // Типы анкет
    {
        path: '/questionnaireType/list',
        name: 'QuestionnaireTypeListView',
        component: QuestionnaireTypeListView,
        props: true,
        meta: {
            'requiresAuth': true,
            'role': ['admin']
        }
    },
    {
        path: '/questionnaireType/:id?',
        name: 'QuestionnaireTypeItemView',
        component: QuestionnaireTypeItemView,
        props: true,
        meta: {
            'requiresAuth': true,
            'role': ['admin']
        }
    },

    // Локации (сцены) для заказов-списков
    {
        path: '/location/list',
        name: 'LocationListView',
        component: LocationListView,
        props: true,
        meta: {
            'requiresAuth': true,
            'role': ['admin']
        }
    },
    {
        path: '/location/:id?',
        name: 'LocationItemView',
        component: LocationItemView,
        props: true,
        meta: {
            'requiresAuth': true,
            'role': ['admin']
        }
    },

    // Типы оплат
    {
        path: '/typesOfPayment/list',
        name: 'TypesOfPaymentListView',
        component: TypesOfPaymentListView,
        props: true,
        meta: {
            'requiresAuth': true,
            'role': ['admin']
        }
    },
    {
        path: '/typesOfPayment/:id?',
        name: 'TypesOfPaymentItemView',
        component: TypesOfPaymentItemView,
        props: true,
        meta: {
            'requiresAuth': true,
            'role': ['admin']
        }
    },

    // Акаунтды пользователей
    {
        path: '/account/list',
        name: 'AccountListView',
        component: AccountListView,
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
        const rawToken = localStorage['user.token'];
        const lifetime = localStorage['user.token.lifetime'];
        const token = rawToken && rawToken !== 'null' && lifetime
            && Math.trunc(Date.now() / 1000) < +lifetime;

        // "Рабочие" роли не должны попадать на форму обычного заказа /hfjlsd65t4732 —
        // кидаем каждую на свою профильную страницу.
        const role    = localStorage['user.role'];
        const isAdmin = localStorage['user.isAdmin'] === 'true' || role === 'admin';
        if (token && !isAdmin && to.path === '/hfjlsd65t4732') {
            if (role === 'curator')        return next({ path: '/curatorOrders/create' });
            if (role === 'pusher')         return next({ path: '/frendlyOrder' });
            if (role === 'pusher_curator') return next({ path: '/frendlyOrder' });
        }

        if (to.matched.some(record => record.meta.requiresAuth)) {
            if (!token) {
                next({ path: '/login', query: { nextUrl: to.fullPath } });
            } else {
                if (to.matched.some(record => record.meta.role)) {
                    window.store.dispatch('appUser/isCorrectRole', { 'role': to.meta.role })
                        .then(() => next())
                        .catch(() => next({ path: '/login' }));
                } else {
                    next();
                }
            }
        } else if (to.matched.some(record => record.meta.guest)) {
            if (!token) {
                next();
            } else {
                next({ path: '/' });
            }
        } else {
            next();
        }
    }
);
export default router;
