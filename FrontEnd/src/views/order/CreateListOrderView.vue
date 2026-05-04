<template>
  <BuyTicketLists />
</template>

<script>
import BuyTicketLists from '@/components/BuyTicket/BuyTicketLists.vue';

export default {
  name: 'CreateListOrderView',
  components: { BuyTicketLists },
  created() {
    document.title = 'Создать список';
  },
};
</script>

<style scoped>
</style>
