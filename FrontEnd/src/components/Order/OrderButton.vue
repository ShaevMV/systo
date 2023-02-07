<template>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Скачать pdf</h5>
      <div v-if="status === 'paid'">
        <button type="button"
                v-for="(item,index) in listTickets"
                v-bind:key="index"
                @click="downloadTicket(item.id)"
                class="btn btn-primary">Скачать билет для {{ item.name }}
        </button>
      </div>
      <span v-else> Билеты будут доступны для скачивания после проверки оплаты заказа </span>
    </div>
  </div>
</template>

<script>
import {mapActions} from "vuex";

export default {
  name: "OrderButton",
  props: {
    status: {
      type: String,
      default: 'new',
    },
    listTickets: {
      type: Array
    },
    id: {
      type: String
    }
  },
  methods: {
    ...mapActions('appOrder', [
      'getUrlForPdf'
    ]),
    /**
     * Скачать билеты
     * @param id
     */
    downloadTicket(id) {
      this.getUrlForPdf(id);
    }
  }
}
</script>

<style scoped>

</style>
