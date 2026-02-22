<template>
  <TypesOfPaymentFilter/>
  <TypesOfPaymentList/>
</template>

<script>
import TypesOfPaymentFilter from "@/components/TypesOfPayment/TypesOfPaymentFilter.vue";
import TypesOfPaymentList from "@/components/TypesOfPayment/TypesOfPaymentList.vue";

export default {
  name: "TypesOfPaymentListView",
  components: {TypesOfPaymentList, TypesOfPaymentFilter},
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