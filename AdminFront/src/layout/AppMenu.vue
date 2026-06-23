<script setup>
import { ref } from 'vue';
import AppMenuItem from './AppMenuItem.vue';

const model = ref([
    {
        label: 'Управление',
        items: [
            {
                label: 'Дашборд',
                icon: 'pi pi-fw pi-chart-bar',
                to: '/admin/dashboard'
            },
            {
                label: 'Анкеты',
                icon: 'pi pi-fw pi-id-card',
                to: '/admin/questionnaires'
            }
        ]
    },
    {
        label: 'Заказы',
        items: [
            {
                label: 'QR-заказы',
                icon: 'pi pi-fw pi-qrcode',
                to: '/admin/qr-orders'
            },
            {
                label: 'Оргвзносы',
                icon: 'pi pi-fw pi-shopping-cart',
                to: '/admin/orders'
            },
            {
                label: 'Дружеские',
                icon: 'pi pi-fw pi-users',
                to: '/admin/orders-friendly'
            },
            {
                label: 'Заказы-списки',
                icon: 'pi pi-fw pi-list',
                to: '/admin/orders-lists'
            }
        ]
    },
    {
        label: 'Справочники',
        items: [
            {
                label: 'Фестивали',
                icon: 'pi pi-fw pi-calendar',
                to: '/admin/festivals'
            },
            {
                label: 'Типы билетов',
                icon: 'pi pi-fw pi-ticket',
                to: '/admin/ticket-types'
            },
            {
                label: 'Опции',
                icon: 'pi pi-fw pi-tags',
                to: '/admin/options'
            },
            {
                label: 'Типы анкет',
                icon: 'pi pi-fw pi-list-check',
                to: '/admin/questionnaire-types'
            },
            {
                label: 'Типы оплат',
                icon: 'pi pi-fw pi-credit-card',
                to: '/admin/types-of-payment'
            },
            {
                label: 'Локации',
                icon: 'pi pi-fw pi-map-marker',
                to: '/admin/locations'
            },
            {
                label: 'Промокоды',
                icon: 'pi pi-fw pi-percentage',
                to: '/admin/promo-codes'
            }
        ]
    },
    {
        label: 'Билеты',
        items: [
            {
                label: 'Доставка в baza',
                icon: 'pi pi-fw pi-server',
                to: '/admin/baza-delivery'
            }
        ]
    },
    {
        label: 'Письма',
        items: [
            {
                label: 'Шаблоны',
                icon: 'pi pi-fw pi-file-edit',
                to: '/admin/templates'
            },
            {
                label: 'Привязки шаблонов',
                icon: 'pi pi-fw pi-link',
                to: '/admin/template-bindings'
            },
            {
                label: 'Доставка писем',
                icon: 'pi pi-fw pi-send',
                to: '/admin/email-delivery'
            }
        ]
    }
]);
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
