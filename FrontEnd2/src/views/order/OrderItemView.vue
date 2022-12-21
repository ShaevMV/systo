<template>
  <div class="py-4 container-fluid">
    <div class="row">
      <div class="col-12">
        <order-item/>
      </div>
    </div>
  </div>

</template>

<script>
import OrderItem from "@/components/Order/OrderItem";

export default {
  name: "OrderItemView",
  components: {OrderItem},

  props: {
    'id': String
  },
  beforeRouteEnter: (to, from, next) => {
    window.store.dispatch('appOrder/loadOrderItem', to.params.id);
    next();
  },
}
</script>

<style scoped>

</style>
