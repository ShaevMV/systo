<template>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Скачать pdf</h5>
      <button type="button"
              v-if="status === 'paid'"
              @click="downloadTicket(id)"
              class="btn btn-primary">Скачать</button>
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
    id: {
      type: String
    }
  },
  methods:{
    ...mapActions('appOrder',[
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
