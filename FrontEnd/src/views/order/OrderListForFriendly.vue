<template>
      <filter-order/>
      <total-number/>
      <order-list :is-admin="true"/>
</template>

<script>
import OrderList from "@/components/OrderFriendly/OrderList.vue";
import FilterOrder from "@/components/OrderFriendly/FilterOrder.vue";
import TotalNumber from "@/components/OrderFriendly/TotalNumber.vue";

export default {
  name: "OrderListForFriendly",
  components: {TotalNumber, FilterOrder, OrderList},
  created() {
    document.title = "Все заказы"
  },
}
</script>

<style scoped>

</style>
