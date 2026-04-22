<template>
  <filter-order/>
  <total-number/>
  <order-list :is-admin="true"/>
</template>

<script>
import OrderList from "@/components/OrderFriendly/OrderList.vue";
import FilterOrder from "@/components/OrderFriendly/FilterOrder.vue";
import TotalNumber from "@/components/OrderFriendly/TotalNumber.vue";

export default {
  name: "OrderListForFriendly",
  components: {TotalNumber, FilterOrder, OrderList},
  created() {
    document.title = "Все заказы 'пушеров'"
  },
  beforeRouteEnter: (to, from, next) => {
    let festivalId = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8'
    window.store.dispatch('appFestivalTickets/getListFestival');
    window.store.dispatch('appOrder/getOrderListForFrendly', {
      'festivalId': festivalId,
    });
    window.store.dispatch('appAccount/loadList', {
      filter: {
        'role': 'pusher'
      },
      orderBy: {},
    });
    next();
  },
}
</script>

<style scoped>

</style>
