<template>
  <section class="section">
    <div class="row">
    <div class="col-lg-12">
      <filter-order/>
      <order-list :is-admin="true"/>
    </div>
    </div>
  </section>

</template>

<script>
import OrderList from "@/components/Order/OrderList.vue";
import FilterOrder from "@/components/Order/FilterOrder.vue";

export default {
  name: "OrderListForAdmin",
  components: {FilterOrder, OrderList},
  beforeRouteEnter: (to, from, next) => {
    window.store.dispatch('appOrder/getOrderListForAdmin');
    next();
  },
}
</script>

<style scoped>

</style>
