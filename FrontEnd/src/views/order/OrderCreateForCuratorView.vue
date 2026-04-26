<template>
  <CreateOrderCurator/>
</template>

<script>
import CreateOrderCurator from "@/components/OrderCurator/CreateOrderCurator.vue";

export default {
  name: "OrderCreateForCuratorView",
  components: {CreateOrderCurator},
  created() {
    document.title = "Создать заказ куратора";
  },
}
</script>
