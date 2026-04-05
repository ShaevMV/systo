<template>
  <BuyTicketFriendly
    :user-id="userId"/>
</template>
<script>
import axios from 'axios';
import BuyTicketFriendly from "@/components/BuyTicket/BuyTicketFriendly.vue";

export default {
  name: 'FriendlyView',
  props: {
    'userId': String
  },
  components: {
    BuyTicketFriendly
  },
  created() {
    document.title = "Система регистрации оргвзносов на систо"
  },
  beforeRouteEnter: (to, from, next) => {
    if(to.params.userId !== undefined) {
      console.log(to.params.userId);
      let promise = axios.get('/api/v1/invite/isCorrectInviteLink/'+to.params.userId);
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
