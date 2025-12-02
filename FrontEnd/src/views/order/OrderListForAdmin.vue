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
  created() {
    document.title = "Все заказы"
  },

}
</script>

<style scoped>

</style>
