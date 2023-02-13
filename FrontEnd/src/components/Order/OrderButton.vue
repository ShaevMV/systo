<template>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Скачать pdf</h5>
      <span> {{ getText }} </span>
      <div v-if="status === 'paid'">
        <button type="button"
                v-for="(item,index) in listTickets"
                v-bind:key="index"
                @click="downloadTicket(item.id)"
                class="btn btn-primary">Скачать билет для {{ item.name }}
        </button>
      </div>
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
  computed: {
    getText() {
      if (this.status === 'new') {
        return 'Твои билеты будут доступны для скачивания после проверки оплаты заказа. Ты также получишь их на свой e-mail.'
      }
      if (this.status === 'paid') {
        return 'Твои билеты доступны для скачивания по ссылке ниже. Они также отправлены на на твой e-mail';
      }
      if (this.status === 'difficulties_arose') {
        return 'С твоим заказом возникли трудности. Пожалуйста свяжись с организаторами.';
      }

      return '';
    },
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
