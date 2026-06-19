<script setup>
import { computed, ref } from 'vue';
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';
import { useLayout } from '@/layout/composables/layout';
import AppConfigurator from './AppConfigurator.vue';
import Menu from 'primevue/menu';

const { toggleMenu, toggleDarkMode, isDarkTheme } = useLayout();
const store = useStore();
const router = useRouter();

const email = computed(() => store.getters['appUser/getEmail']);

// 📅 Календарь → сводка/дашборд (обзор продаж и заказов «на сегодня»).
function goCalendar() {
    router.push('/admin/dashboard');
}

// 📥 Входящие → «Доставка писем» (лента всех писем со статусами доставки).
function goInbox() {
    router.push('/admin/email-delivery');
}

// 👤 Профиль → выпадающее меню: личный кабинет + выход.
const userMenu = ref(null);
const userMenuItems = computed(() => [
    {
        label: email.value || 'Аккаунт',
        items: [
            { label: 'Личный кабинет', icon: 'pi pi-id-card', command: () => router.push('/admin/profile') },
            { label: 'Выход', icon: 'pi pi-sign-out', command: () => store.dispatch('appUser/logOut') }
        ]
    }
]);

function toggleUserMenu(event) {
    userMenu.value.toggle(event);
}
</script>

<template>
    <div class="layout-topbar">
        <div class="layout-topbar-logo-container">
            <button class="layout-menu-button layout-topbar-action" @click="toggleMenu">
                <i class="pi pi-bars"></i>
            </button>
            <router-link to="/" class="layout-topbar-logo">
                <img src="/img/logo-solarsysto-2026.webp" alt="Solar Systo" class="layout-topbar-logo-img" />
                <span class="layout-topbar-logo-text font-display">SOLAR SYSTO</span>
            </router-link>
        </div>

        <div class="layout-topbar-actions">
            <div class="layout-config-menu">
                <button type="button" class="layout-topbar-action" @click="toggleDarkMode">
                    <i :class="['pi', { 'pi-moon': isDarkTheme, 'pi-sun': !isDarkTheme }]"></i>
                </button>
                <div class="relative">
                    <button
                        v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'p-anchored-overlay-enter-active', leaveToClass: 'hidden', leaveActiveClass: 'p-anchored-overlay-leave-active', hideOnOutsideClick: true }"
                        type="button"
                        class="layout-topbar-action layout-topbar-action-highlight"
                    >
                        <i class="pi pi-palette"></i>
                    </button>
                    <AppConfigurator />
                </div>
            </div>

            <button
                class="layout-topbar-menu-button layout-topbar-action"
                v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'p-anchored-overlay-enter-active', leaveToClass: 'hidden', leaveActiveClass: 'p-anchored-overlay-leave-active', hideOnOutsideClick: true }"
            >
                <i class="pi pi-ellipsis-v"></i>
            </button>

            <div class="layout-topbar-menu hidden lg:block">
                <div class="layout-topbar-menu-content">
                    <button type="button" class="layout-topbar-action" title="Сводка / дашборд" @click="goCalendar">
                        <i class="pi pi-calendar"></i>
                        <span>Сводка</span>
                    </button>
                    <button type="button" class="layout-topbar-action" title="Доставка писем" @click="goInbox">
                        <i class="pi pi-inbox"></i>
                        <span>Письма</span>
                    </button>
                    <button type="button" class="layout-topbar-action" :title="email || 'Аккаунт'" aria-haspopup="true" @click="toggleUserMenu">
                        <i class="pi pi-user"></i>
                        <span>Профиль</span>
                    </button>
                    <Menu ref="userMenu" :model="userMenuItems" :popup="true" />
                </div>
            </div>
        </div>
    </div>
</template>
