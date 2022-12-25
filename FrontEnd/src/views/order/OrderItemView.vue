<template>
  <div v-if="!getError('error')">
    <order-item/>
    <order-comment/>
  </div>
  <div v-else>
    {{ getError('error') }}
  </div>
</template>

<script>
import OrderItem from "@/components/Order/OrderItem";
import OrderComment from "@/components/Order/OrderComment.vue";
import {mapGetters} from "vuex";

export default {
  name: "OrderItemView",
  components: {OrderComment, OrderItem},
  computed: {
    ...mapGetters('appOrder', [
      'getError',
    ]),
  },
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
