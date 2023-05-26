<template>
      <filter-order :festival-id="id"/>
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
  props: {
    'id': String
  },
  beforeRouteEnter: (to, from, next) => {
    window.store.dispatch('appOrder/getOrderListForAdmin',{
      festivalId: to.params.id
    });
    next();
  },
  beforeRouteUpdate: (to, from, next) => {
    window.store.dispatch('appOrder/getOrderListForAdmin',{
      festivalId: to.params.id
    });
    next();
  },
  created() {
    document.title = "Все заказы"
  },

}
</script>

<style scoped>

</style>
