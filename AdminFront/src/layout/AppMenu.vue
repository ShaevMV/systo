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
                label: 'QR-заказы',
                icon: 'pi pi-fw pi-qrcode',
                to: '/admin/qr-orders'
            },
            {
                label: 'Анкеты',
                icon: 'pi pi-fw pi-id-card',
                to: '/admin/questionnaires'
            }
        ]
    },
    {
        label: 'Справочники',
        items: [
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
