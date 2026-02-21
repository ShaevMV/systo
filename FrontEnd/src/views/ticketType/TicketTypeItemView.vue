<template>
  <TicketTypeFilter/>
  <ticket-type-list/>
</template>

<script>
import TicketTypeFilter from "@/components/TicketType/TicketTypeFilter.vue";
import TicketTypeList from "@/components/TicketType/TicketTypeList.vue";

export default {
  name: "TicketTypeItemView",
  components: {TicketTypeList, TicketTypeFilter}
}
</script>

<style scoped>

</style>