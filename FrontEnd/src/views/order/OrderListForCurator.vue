<template>
  <FilterOrder action-name="getOrderListForCurator" />
  <OrderList title="Мои заказы-списки (куратор)" :can-change-status="false" />
</template>

<script>
import OrderList from '@/components/OrderLists/OrderList.vue';
import FilterOrder from '@/components/OrderLists/FilterOrder.vue';

export default {
  name: 'OrderListForCurator',
  components: { OrderList, FilterOrder },
  created() {
    document.title = 'Мои списки';
  },
  beforeRouteEnter: (to, from, next) => {
    const festivalId = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';
    window.store.dispatch('appFestivalTickets/getListFestival');
    window.store.dispatch('appOrder/getOrderListForCurator', { festivalId });
    next();
  },
};
</script>

<style scoped>
</style>
