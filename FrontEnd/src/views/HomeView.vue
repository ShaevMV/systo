<template>
    <BuyTicket/>
</template>
<script>
import BuyTicket from "@/components/BuyTicket/BuyTicket";

export default {
  name: 'HomeView',
  components: {
    BuyTicket,
  },
  created() {
    document.title = "Система регистрации оргвзносов на систо"
  },
}
</script>
