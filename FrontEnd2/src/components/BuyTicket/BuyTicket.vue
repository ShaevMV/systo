<template>
  <div class="container">
    <div class="row">
      <soft-alert
          v-if="showResult"
          :textReturn="returnText"
          :color="success ? 'success' : 'warning'"
      />
      <div class="col-lg-7 mx-auto">
        <div class="card mt-2 mx-auto p-4 bg-light">
          <div class="card-body bg-light">

            <div class="container">
              <div id="contact-form" role="form">
                <div class="controls">
                  <!--                  Добавить гостя-->
                  <div class="row">
                    <div class="col-md-12">
                      <div class="form-group">
                        <label for="newGuest">Имя и Фамилию гостя *</label>
                        <div class="input-group mb-3">
                          <input type="text"
                                 id="newGuest"
                                 class="form-control"
                                 placeholder="Введите Имя и Фамилию гостя"
                                 aria-label="Введите Имя и Фамилию гостя"
                                 v-model="newGuest"
                                 aria-describedby="basic-addon1">
                          <div class="input-group-prepend">
                            <button class="input-group-text btn"
                                    :disabled="!isAllowedNewGuest"
                                    value=""
                                    @click="addGuest()"
                                    id="basic-addon1">+
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!--                  Список гостей-->
                  <div class="row" v-show="guests.length > 0">
                    <div class="col-md-12">
                      <div class="form-group">
                        <label for="inputCount" class="col-lg-4 control-label">Данные о гостях:</label>
                        <div class="input-group mb-3"
                             v-for="(itemGuest,index) in guests" v-bind:key="index">
                          <input type="text"
                                 class="form-control"
                                 readonly
                                 v-bind:value="itemGuest.value"
                                 aria-describedby="basic-addon2">
                          <div class="input-group-prepend">
                              <span class="input-group-text btn"
                                    @click="delGuest(index)"
                                    id="basic-addon2">
                                <i class="fa fa-trash"></i>
                              </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!--                  Email и Тип оргвзноса-->
                  <div class="row">
                    <!--                  Email -->
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="form_email">Email *</label>
                        <input id="form_email"
                               type="email"
                               name="email"
                               class="form-control"
                               placeholder="Please enter your email *"
                               required="required"
                               v-model="email"
                               v-bind:readonly="isAuth"
                               data-error="Valid email is required.">

                      </div>
                    </div>
                    <!--                  Тип оргвзноса: *-->
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="form_need">Тип оргвзноса: *</label>
                        <select id="form_need"
                                name="need"
                                class="form-control"
                                required="required"
                                v-model="selectTypeTicket"
                                data-error="Please specify your need.">
                          <option disabled value="null">Выберите тип оргвзноса</option>
                          <option v-for="(typeTickets) in getTicketType"
                                  v-bind:key="typeTickets.id"
                                  v-bind:value="typeTickets.id">{{ typeTickets.name }} /
                            {{ typeTickets.price }} руб.
                          </option>
                        </select>

                      </div>
                    </div>
                  </div>
                  <!--                  Промокод-->
                  <div class="row">
                    <div class="col-mb-12">
                      <div class="form-group">
                        <label for="form_promo_cod">Промокод:</label>
                        <div class="input-group mb-3">
                          <input type="text"
                                 id="form_promo_cod"
                                 class="form-control"
                                 placeholder="Промокод"
                                 aria-label="Промокод"
                                 v-model="promoCode"
                                 v-bind:readonly="getDiscountByPromoCode > 0"
                                 aria-describedby="basic-addon1">
                        </div>
                        <small class="form-text text-muted" v-show="getDiscountByPromoCode > 0">
                          Ваш промо код принят, ваша скидка составит {{ getDiscountByPromoCode }} ₽
                        </small>
                      </div>
                    </div>
                  </div>
                  <!--                  Способ оплаты: *-->
                  <div class="row">
                    <div class="col-md-12">
                      <div class="form-group">
                        <label for="form_need">Способ оплаты: *</label>
                        <div class="payment-choice" v-for="typesOfPayment in getTypesOfPayment"
                             v-bind:key="typesOfPayment.id">
                          <div class="form-check">
                            <label class="form-check-label" v-bind:for="typesOfPayment.id">
                              <input type="radio"
                                     class="form-check-input"
                                     v-model="selectTypesOfPayment"
                                     v-bind:value="typesOfPayment.id"
                                     v-bind:id="typesOfPayment.id">
                              {{ typesOfPayment.name }}
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!--                  Дата плптежа -->
                  <div class="row">
                    <div class="col-md-12">
                      <div class="form-group">
                        <label for="form_message">Введите данные о том, когда вы внесли платеж:</label>
                        <input type="datetime-local" class="form-control-plaintext" id="selectData" v-model="date">
                      </div>

                    </div>
                    <div class="col-md-12">
                      <div class="form-group">
                        <label for="idBuy">Идентификатор платежа:</label>
                        <textarea class="form-control" v-model="idBuy" id="idBuy" rows="3"></textarea>
                        <small class="form-text text-muted">
                          При переводах на Сбербанк напишите сюда последние 4 цифры карты, с которой вы сделали перевод
                          <b>(сюда же вписываем ID или номер "живого билета" с весны для скидки)</b>
                        </small>
                      </div>

                    </div>
                    <div class="col-md-12">
                      <div class="form-check">
                        <input class="form-check-input"
                               type="checkbox"
                               value=""
                               v-model="confirm"
                               id="defaultCheck1">
                        <label class="form-check-label" for="defaultCheck1">
                          Регистрируя организационный взнос, вы соглашаетесь с &nbsp;<a
                            href="/conditions/" target="_blank">условиями фестиваля</a>
                        </label>
                      </div>

                    </div>
                  </div>
                  <!--                  Стоимость -->
                  <div class="row" v-show="totalPrice > 0">
                    <div class="col-md-6">
                      <h4 class="my-lg-2 font-weight-normal">Итог: {{ totalPrice }} <small class="text-muted">/
                        руб.</small></h4>

                    </div>
                    <div class="col-md-6">
                      <h4 class="my-lg-2 font-weight-normal">Кол-во гостей: {{ countGuests }} </h4>
                    </div>

                  </div>
                  <div class="row" v-show="totalPrice > 0 && getDiscountByPromoCode > 0">
                    <div class="col-md-6">
                      <h4 class="my-lg-2 font-weight-normal">Скидка: </h4>

                    </div>
                    <div class="col-md-6">
                      <h4 class="my-lg-2 font-weight-normal">{{ getDiscountByPromoCode }} <small class="text-muted">/
                        руб.</small></h4>
                    </div>

                  </div>
                  <!--                  Подтвердить внесение-->
                  <div class="row">
                    <div class="col-md-12">
                      <button type="button"
                              :disabled="!isNotCorrect"
                              @click="orderTicket"
                              class="btn btn-lg btn-block btn-outline-primary ">Подтвердить внесение
                        средств
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- /.8 -->

      </div>

    </div>
  </div>

</template>

<script>
import {mapGetters, mapActions} from 'vuex';
import SoftAlert from "@/components/SoftAlert.vue";

export default {
  name: "BuyTicket",
  components: {SoftAlert},
  data() {
    return {
      selectTypesOfPayment: null,
      guests: [],
      newGuest: null,
      email: null,
      date: null,
      idBuy: null,
      confirm: false,
      returnText: null,
      success: null,
      showResult: false,
    }
  },
  computed: {
    ...mapGetters('appFestivalTickets', [
      'getTypesOfPayment',
      'getTicketType',
      'isAllowedGuest',
      'getSelectTicketType',
      'getSelectTicketTypeId',
      'getSelectTicketTypeLimit',
      'getDiscountByPromoCode',
      'getPromoCodeName',
    ]),
    ...mapGetters('appUser', [
      'isAuth',
      'getEmail',
    ]),
    /**
     * Проверка на ведение всех данных
     * @returns {false|*|null}
     */
    isNotCorrect: function () {
      return this.selectTypeTicket !== null &&
          this.selectTypesOfPayment !== null &&
          this.guests.length > 0 &&
          this.date !== null &&
          this.confirm === true &&
          this.idBuy !== null &&
          (this.isAuth || this.email)
    },
    /**
     * Выбранный тип билета
     */
    selectTypeTicket: {
      get: function () {
        return this.getSelectTicketTypeId;
      },
      set: function (newValue) {
        let oldId = this.getSelectTicketTypeId;
        this.setSelectTicketType(newValue);
        if (this.getSelectTicketType !== null) {
          if (!this.isAllowedGuest(this.guests.length)) {
            alert('Привышен лимин по данному типу доступна только ' + this.getSelectTicketTypeLimit);
            this.setSelectTicketType(oldId);
          }
        }
      },
    },
    promoCode: {
      get: function () {
        return this.getPromoCodeName;
      },
      set: function (newValue) {
        this.checkPromoCode(newValue);
      },
    },
    /**
     * Стоимость билета
     * @returns {number}
     */
    totalPrice: function () {
      let price = 0;

      if (this.getSelectTicketType !== null && this.guests.length > 0) {
        price = this.getSelectTicketType.price;
        let count = this.getSelectTicketTypeLimit !== null ? 1 : this.guests.length;
        return (price * count) - this.getDiscountByPromoCode;
      }

      return 0;
    },
    /**
     * Кол-во гостей
     * @returns {number}
     */
    countGuests: function () {
      return this.guests.length;
    },
    /**
     * Проверка на добавление нового гостя
     * @returns {boolean}
     */
    isAllowedNewGuest: function () {
      if (this.getSelectTicketType !== null) {
        return this.getSelectTicketTypeLimit === null || this.getSelectTicketTypeLimit >= this.countGuests + 1
      }
      return false;
    },
  },
  methods: {
    ...mapActions('appFestivalTickets', [
      'loadDataForOrderingTickets',
      'setSelectTicketType',
      'checkPromoCode',
      'clearPromoCode'
    ]),
    ...mapActions('appOrder', [
      'goToCreateOrderTicket',
    ]),
    /**
     * Добавить нового гостя
     */
    addGuest: function () {
      if (this.newGuest.length > 0) {
        this.guests.push({value: this.newGuest});
        this.newGuest = null;
      }
    },
    /**
     * Удалить гостя
     * @param index
     */
    delGuest: function (index) {
      this.guests.splice(index, 1);
    },
    /**
     * Заказать билет
     */
    orderTicket: function () {
      let self = this;
      self.showResult = false;
      this.goToCreateOrderTicket({
        'email': this.email,
        'ticket_type_id': this.getSelectTicketTypeId,
        'guests': this.guests,
        'promo_code': this.promoCode,
        'date': this.date,
        'types_of_payment_id': this.selectTypesOfPayment,
        'callback': function (success, textResult) {
          console.log(success)
          console.log(textResult)
          if (success) {
            self.clearData();
          }
          self.success = success;
          self.returnText = textResult;
          self.showResult = true;
        }
      })
    },
    /**
     * Очистить данные
     */
    clearData: function () {
      this.selectTypesOfPayment = null;
      this.guests = [];
      this.newGuest = '';
      this.email = this.getEmail;
      this.promoCode = null;
      this.date = null;
      this.idBuy = null;
      this.confirm = false;
      this.clearPromoCode();
    },
  },
  async created() {
    await this.loadDataForOrderingTickets();
    this.email = this.getEmail;

  },
}
</script>


<style scoped>
body {
  font-family: 'Lato', sans-serif;
}

h1 {
  margin-bottom: 40px;
}

label {
  color: #333;
}

.card {
  margin-left: 10px;
  margin-right: 10px;
}

</style>
