<template>
  <div class="row">
    <UserOrderList/>
  </div>
</template>

<script>
import UserOrderList from "@/components/Order/UserOrderList";
export default {
  name: "OrderView",
  components: {UserOrderList},
  beforeRouteEnter: (to, from, next) => {
    window.store.dispatch('appOrder/getOrderListForUser');
    next();
  },
}
</script>

<style scoped>

</style>
