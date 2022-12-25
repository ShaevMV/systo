<template>
  <filter-order/>
  <order-list :is-admin="true"/>
</template>

<script>
import OrderList from "@/components/Order/OrderList.vue";
import FilterOrder from "@/components/Order/FilterOrder.vue";

export default {
  name: "OrderListForAdmin",
  components: {FilterOrder, OrderList},
  beforeRouteEnter: (to, from, next) => {
    window.store.dispatch('appOrder/getOrderListForUser');
    next();
  },
}
</script>

<style scoped>

</style>
