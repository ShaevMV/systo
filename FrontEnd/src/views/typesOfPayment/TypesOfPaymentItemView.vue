<template>
  <TypesOfPaymentItem/>
</template>

<script>
import TypesOfPaymentItem from "@/components/TypesOfPayment/TypesOfPaymentItem.vue";

export default {
  name: "TypesOfPaymentItemView",
  components: {TypesOfPaymentItem},
  beforeRouteEnter: (to, from, next) => {
    if (to.params.id) {
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