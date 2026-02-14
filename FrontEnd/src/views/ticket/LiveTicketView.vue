<template>
  <h1> номер вашего билета {{getLiveTicketNumber}} </h1>
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