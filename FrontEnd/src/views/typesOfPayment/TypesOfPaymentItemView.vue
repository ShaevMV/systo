<template>
  <TypesOfPaymentItem/>
</template>

<script>
import TypesOfPaymentItem from "@/components/TypesOfPayment/TypesOfPaymentItem.vue";

export default {
  name: "TypesOfPaymentItemView",
  components: {TypesOfPaymentItem},
  beforeRouteEnter: (to, from, next) => {
    window.store.dispatch('appTicketType/loadTemplate');
    window.store.dispatch('appAccount/loadList', {
      filter: {
        'role': 'seller'
      },
      orderBy: {},
    });
    window.store.dispatch('appTicketType/loadList', {
      filter: {
        'is_live_ticket': 'true'
      },
      orderBy: {},
    });
    if (to.params.id) {
      console.log(to.params.id)
      window.store.dispatch('appTypesOfPayment/loadItem', {
        id: to.params.id,
      });
    }

    next();
  },
}
</script>

<style scoped>

</style>