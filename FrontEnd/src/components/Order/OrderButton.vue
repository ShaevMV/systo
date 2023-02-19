<template>
  <div style="display: block;">
      <h4 class="download-title">Скачать электронные билеты с qr-кодом:</h4>
      <div class="qr-text"> {{ getText }} </div>
      <div v-if="status === 'paid'" class="mb-3 mt-3">
        <button type="button"
                v-for="(item,index) in listTickets"
                v-bind:key="index"
                @click="downloadTicket(item.id)"
                class="downloader"
                style="display: block;">Скачать билет для {{ item.name }}
        </button>
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
    async downloadTicket(id) {
      const win = window.open('about:blank', '_target=blank');
      var ticket = await this.getUrlForPdf(id);
      win.location = ticket;
    }
  }
}
</script>

<style scoped>

</style>
