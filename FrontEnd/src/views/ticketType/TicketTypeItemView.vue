<template>
  <TicketTypeItem/>
</template>

<script>
import TicketTypeItem from "@/components/TicketType/TicketTypeItem.vue";

export default {
  name: "TicketTypeItemView",
  components: {TicketTypeItem},
  beforeRouteEnter: (to, from, next) => {
    window.store.dispatch('appFestivalTickets/getListFestival');
    window.store.dispatch('appTicketType/loadTemplate');
    window.store.dispatch('appQuestionnaireType/loadList', {filter: {active: 1}});
    if (to.params.id) {
      window.store.dispatch('appTicketType/loadItem', {
        id: to.params.id,
      });
    }

    next();
  },
  created() {
    document.title = "Создать тип билета"
  }
}
</script>

<style scoped>

</style>