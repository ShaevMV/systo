<template>
  <filter-order/>
  <order-list is-admin="true"/>
</template>

<script>
import OrderList from "@/components/Order/OrderList.vue";
import FilterOrder from "@/components/Order/FilterOrder.vue";

export default {
  name: "OrderListForAdmin",
  components: {FilterOrder, OrderList}
}
</script>

<style scoped>

</style>
