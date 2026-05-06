<template>
  <FilterOrder action-name="getOrderListsList" />
  <OrderList title="Заказы-списки (admin/manager)" :can-change-status="true" />
</template>

<script>
import OrderList from '@/components/OrderLists/OrderList.vue';
import FilterOrder from '@/components/OrderLists/FilterOrder.vue';

export default {
  name: 'OrderListsListView',
  components: { OrderList, FilterOrder },
  created() {
    document.title = 'Заказы-списки';
  },
  beforeRouteEnter: (to, from, next) => {
    const festivalId = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';
    window.store.dispatch('appFestivalTickets/getListFestival');
    window.store.dispatch('appOrder/getOrderListsList', { festivalId });
    next();
  },
};
</script>

<style scoped>
</style>
