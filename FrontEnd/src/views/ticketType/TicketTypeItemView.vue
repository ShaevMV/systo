<template>
  <TicketTypeItem/>
</template>

<script>
import TicketTypeItem from "@/components/TicketType/TicketTypeItem.vue";

export default {
  name: "TicketTypeItemView",
  components: {TicketTypeItem},
  beforeRouteEnter: async (to, from, next) => {
    await window.store.dispatch('appFestivalTickets/getListFestival');
    await window.store.dispatch('appTicketType/loadTemplate');

    if (to.params.id) {
      await window.store.dispatch('appTicketType/loadItem', {
        id: to.params.id,
      });
    }

    next();
  },
}
</script>

<style scoped>

</style>