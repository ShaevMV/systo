<template>
  <TicketTypeItem/>
</template>

<script>
import TicketTypeItem from "@/components/TicketType/TicketTypeItem.vue";

export default {
  name: "TicketTypeItemView",
  components: {TicketTypeItem},
  beforeRouteEnter: (to, from, next) => {
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