<template>
  <div class="ticket">
    <h1> Номер вашего оргвзноса {{ getLiveTicketNumber }} </h1>
    <img src="/assets/img/medved.png" alt="Номер Вашего Оргвзноса">
  </div>
</template>

<script>
import {mapGetters} from "vuex";

export default {
  name: "LiveTicketView",
  props: {
    'id': String
  },
  data() {
    return {
      number: null
    }
  },
  computed: {
    ...mapGetters('appTicket', [
      'getLiveTicketNumber',
    ]),
  },
  beforeRouteEnter: (to, from, next) => {
    if (to.params.id) {
      window.store.dispatch('appTicket/toPushNumber', {
        number: to.params.id,
      });
    }

    next();
  },
}
</script>

<style scoped>

</style>