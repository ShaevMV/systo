<template>
  <filter-order-curator/>
  <total-number/>
  <order-list-curator/>
</template>

<script>
import FilterOrderCurator from "@/components/OrderCurator/FilterOrderCurator.vue";
import TotalNumber from "@/components/Order/TotalNumber.vue";
import OrderListCurator from "@/components/OrderCurator/OrderListCurator.vue";

export default {
  name: "OrderListForCurator",
  components: {OrderListCurator, TotalNumber, FilterOrderCurator},
  created() {
    document.title = "Заказы кураторов";
  },
  beforeRouteEnter: (to, from, next) => {
    const festivalId = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';
    window.store.dispatch('appFestivalTickets/getListFestival');
    window.store.dispatch('appOrder/getOrderListForCurator', {
      'festivalId': festivalId,
    });
    window.store.dispatch('appAccount/loadList', {
      filter: { role: 'curator' },
      orderBy: {},
    });
    next();
  },
}
</script>
