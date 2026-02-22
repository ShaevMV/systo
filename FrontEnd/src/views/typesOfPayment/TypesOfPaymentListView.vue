<template>
  <TypesOfPaymentFilter/>
</template>

<script>
import TypesOfPaymentFilter from "@/components/TypesOfPayment/TypesOfPaymentFilter.vue";

export default {
  name: "TypesOfPaymentListView",
  components: {TypesOfPaymentFilter},
  beforeRouteEnter: (to, from, next) => {
    window.store.dispatch('appAccount/loadList', {
      filter: {
        'role': 'seller'
      },
      orderBy: {},
    });

    next();
  }
}
</script>

<style scoped>

</style>