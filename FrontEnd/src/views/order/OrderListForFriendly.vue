<template>
      <filter-order/>
      <total-number/>
      <div class="row mb-3">
        <div class="col-12">
          <export-button />
          <button class="btn btn-info ml-2" @click="showModal = true">⚙️ Настройки выгрузки</button>
        </div>
      </div>
      <order-list :is-admin="true"/>
      <export-modal v-model="showModal" />
</template>

<script>
import OrderList from "@/components/OrderFriendly/OrderList.vue";
import FilterOrder from "@/components/OrderFriendly/FilterOrder.vue";
import TotalNumber from "@/components/OrderFriendly/TotalNumber.vue";
import ExportButton from "@/components/Reports/ExportButton.vue";
import ExportModal from "@/components/Reports/ExportModal.vue";

export default {
  name: "OrderListForFriendly",
  components: {ExportModal, ExportButton, TotalNumber, FilterOrder, OrderList},
  data() {
    return {
      showModal: false,
    };
  },
  created() {
    document.title = "Все заказы"
  },
}
</script>

<style scoped>
</style>
