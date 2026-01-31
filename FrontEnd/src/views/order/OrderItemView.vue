<template>
  <div v-if="!getError('error')">
    <order-item/>
  </div>
  <div v-else>
    {{ getError('error') }}
  </div>
</template>

<script>
import OrderItem from "@/components/Order/OrderItem";
import QuestionnaireItem from "@/components/Order/QuestionnaireItem";
import {mapGetters} from "vuex";

export default {
  name: "OrderItemView",
  components: {OrderItem, QuestionnaireItem},
  computed: {
    ...mapGetters('appOrder', [
      'getError',
    ]),
  },
  props: {
    'id': String
  },

  beforeRouteEnter: (to, from, next) => {
    window.store.dispatch('appOrder/loadOrderItem', {
      id:to.params.id
    });
    next();
  },
}
</script>

<style scoped>

</style>
