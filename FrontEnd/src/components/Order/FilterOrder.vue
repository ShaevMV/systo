<template>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Фильтр</h5>

      <div class="row g-3">
        <!-- тип оргвзноса -->
        <div class="col-md-3">
          <label for="validationDefault01" class="form-label">Тип оргвзноса</label>
          <select class="form-select"
                  v-model="typeOrder"
                  id="validationDefault01">
            <option value=null>Выберите тип оргвзноса</option>
            <option v-for="(typeTickets) in getTicketType"
                    v-bind:key="typeTickets.id"
                    v-bind:value="typeTickets.id">{{ typeTickets.name }} /
              {{ typeTickets.price }} руб.
            </option>
          </select>
        </div>
        <!-- Способ покупки билета -->
        <div class="col-md-3">
          <label for="validationDefault01" class="form-label">Способ покупки билета</label>
          <select class="form-select"
                  v-model="typesOfPayment"
                  id="validationDefault01">
            <option value=null>Выберите способ покупки билета</option>
            <option v-for="(typesOfPayment) in getTypesOfPayment"
                    v-bind:key="typesOfPayment.id"
                    v-bind:value="typesOfPayment.id">{{ typesOfPayment.name }}
            </option>
          </select>
        </div>
        <!-- email -->
        <div class="col-md-4">
          <label for="validationDefaultUsername" class="form-label">Email</label>
          <div class="input-group">
            <span class="input-group-text" id="inputGroupPrepend2">@</span>
            <input type="text"
                   v-model="email"
                   class="form-control"
                   id="validationDefaultUsername"
                   aria-describedby="inputGroupPrepend2">
          </div>
        </div>

        <!-- статус заказа -->
        <div class="col-md-4">
          <label for="validationDefault01" class="form-label">Статус заказа</label>
          <select class="form-select"
                  v-model="status"
                  id="validationDefault01">
            <option value=null>Выберите статус заказа</option>
            <option value="new">Новый</option>
            <option value="paid">Оплаченный</option>
            <option value="cancel">Отменёный</option>
            <option value="difficulties_arose">Возникли трудности</option>
          </select>
        </div>

        <!-- промо код -->
        <div class="col-md-4">
          <label for="validationDefault03" class="form-label">Промо код</label>
          <input type="text"
                 v-model="promoCode"
                 class="form-control"
                 id="validationDefault03">
        </div>
        <div class="col-6">
          <button class="btn btn-primary"
                  @click="sendFilter"
                  type="submit">Применить фильтр
          </button>
        </div>
        <div class="col-6">
          <button class="btn btn-primary"
                  @click="clearFilter"
                  type="submit">Сбросить фильтр
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
import {mapActions, mapGetters} from 'vuex';

export default {
  name: "FilterOrder",
  data() {
    return {
      typeOrder: null,
      email: null,
      status: null,
      promoCode: null,
      typesOfPayment: null,
    }
  },
  computed: {
    ...mapGetters('appFestivalTickets', [
      'getTypesOfPayment',
      'getTicketType',
    ]),
  },
  methods: {
    ...mapActions('appFestivalTickets', [
      'loadDataForOrderingTickets',
    ]),
    ...mapActions('appOrder', [
      'getOrderListForAdmin',
    ]),
    /**
     * Отправить данные для фильтра
     */
    sendFilter: function () {
      this.getOrderListForAdmin({
        'typeOrder': this.typeOrder,
        'email': this.email,
        'status': this.status,
        'promoCode': this.promoCode,
        'typesOfPayment': this.typesOfPayment,
      });
    },
    clearFilter: function () {
      this.typeOrder = null;
      this.email = null;
      this.status = null;
      this.promoCode = null;
      this.typesOfPayment = null;
      this.getOrderListForAdmin();
    }
  },
  async created() {
    await this.loadDataForOrderingTickets();
  },
}
</script>

<style scoped>

</style>
