<template>
    <div class="container-fluid">
      <UserOrderList/>
    </div>
</template>

<script>
import UserOrderList from "@/components/Order/OrderList.vue";

export default {
  name: "OrderView",
  components: {UserOrderList},
  beforeRouteEnter: (to, from, next) => {
    window.store.dispatch('appOrder/getOrderListForUser');
    next();
  },
  created() {
    document.title = "Мои заказы"
  },

}
</script>

<style scoped>

</style>
