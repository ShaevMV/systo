<template>
    <BuyTicket/>
</template>
<script>
import BuyTicket from "@/components/BuyTicket/BuyTicket";
import axios from 'axios';

export default {
  name: 'HomeView',
  props: {
    'userId': String
  },
  components: {
    BuyTicket,
  },
  created() {
    document.title = "Система регистрации оргвзносов на систо"
  },
  beforeRouteEnter: (to, from, next) => {
    if(to.params.userId !== undefined) {
      console.log(to.params.userId);
      let promise = axios.get('/api/v1/festival/isCorrectInviteLink/'+to.params.userId);
      promise.then(function (response) {
        if(!response.data.success) {
          window.location.href = '/';
        }
      });
    }
    next();
  },
}
</script>
