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
                label: 'Шаблоны',
                icon: 'pi pi-fw pi-file-edit',
                to: '/admin/templates'
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
