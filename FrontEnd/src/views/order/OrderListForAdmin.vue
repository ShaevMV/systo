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
  beforeRouteEnter: (to, from, next) => {
    window.store.dispatch('appOrder/getOrderListForAdmin');
    next();
  },
}
</script>

<style scoped>

</style>
