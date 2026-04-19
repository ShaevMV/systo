<template>
  <filter-order/>
  <total-number/>
  <order-list :is-admin="true"/>
</template>

<script>
import OrderList from "@/components/Order/OrderList.vue";
import FilterOrder from "@/components/Order/FilterOrder.vue";
import TotalNumber from "@/components/Order/TotalNumber.vue";

export default {
  name: "OrderListForAdmin",
  components: {TotalNumber, FilterOrder, OrderList},
  created() {
    document.title = "Все заказы"
  },
  beforeRouteEnter: (to, from, next) => {
    let festivalId = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8'
    window.store.dispatch('appFestivalTickets/getListFestival');
    window.store.dispatch('appFestivalTickets/getListPriceFor', {festival_id: festivalId});
    window.store.dispatch('appFestivalTickets/getListTypesOfPayment', {
      festival_id: festivalId,
      is_admin: true
    });
    let filterData = {
      'festivalId': festivalId,
    };
    window.store.dispatch('appOrder/getOrderListForAdmin', filterData);
    next();
  },
}
</script>

<style scoped>

</style>
