<template>
  <div class="container">
    <div class=" text-center mt-5 ">

      <h1>Форма подтверждения оргвзноса</h1>
      <small class="form-text text-muted">
        После того как Вы внесете оргвзнос одним из электронных способов оплаты и заполните эту форму - <br>
        в течение 2-3 дней на ваш e-mail придет подтверждение и ваш QR-cod для входа на фестиваль.
      </small>

    </div>
    <div class="row ">
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
                                 aria-describedby="basic-addon1">
                          <div class="input-group-prepend">
                            <span class="input-group-text btn"
                                  @click="sendPromoCode()">✓
                            </span>
                          </div>
                        </div>
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
      <!-- /.row-->

    </div>
  </div>
</template>

<script>
import {mapGetters, mapActions} from 'vuex';

export default {
  name: "BuyTicket",
  data() {
    return {
      selectTypesOfPayment: null,
      guests: [],
      newGuest: '',
      email: null,
      promoCode: null,
      date: null,
      idBuy: null,
      confirm: false,
    }
  },
  computed: {
    ...mapGetters('appFestivalTickets', [
      'getTypesOfPayment',
      'getTicketType',
      'getSelectTicketType',
      'getSelectTicketTypeId',
      'getSelectTicketTypeLimit',
      'getDiscountByPromoCode'
    ]),
    ...mapGetters('appUser', [
      'isAuth',
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
          if (!this.isAllowedGuest) {
            alert('Привышен лимин по данному типу доступна только ' + this.getSelectTicketTypeLimit);
            this.setSelectTicketType(oldId);
          }
        }
      },
    },
    /**
     * Стоимость билета
     * @returns {number}
     */
    totalPrice: function () {
      let price = 0;

      if (this.getSelectTicketType !== null) {
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
    /**
     * Проверка на соответсвие условием группавого типа билета
     * @returns {boolean}
     */
    isAllowedGuest: function () {
      if (this.getSelectTicketType !== null) {
        return this.getSelectTicketTypeLimit === null || this.getSelectTicketTypeLimit >= this.countGuests
      }
      return false;
    }
  },
  methods: {
    ...mapActions('appFestivalTickets', [
      'loadDataForOrderingTickets',
      'setSelectTicketType',
      'goToOrderTicket',
      'checkPromoCode'
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
      this.goToOrderTicket({
        'email': this.email,
        'typeTicket': this.getSelectTicketTypeId,
        'guests': this.guests,
        'promoCode': this.promoCode,
        'date': this.date,
        'typesOfPayment': this.selectTypesOfPayment,
      })
    },
    /**
     * Отправить промо код
     */
    sendPromoCode: function () {
      if (this.promoCode !== null) {
        this.checkPromoCode(this.promoCode);
      }
    }
  },
  async created() {
    await this.loadDataForOrderingTickets();
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

.btn-send {
  font-weight: 300;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  width: 80%;
  margin-left: 3px;
}

.help-block.with-errors {
  color: #ff5050;
  margin-top: 5px;

}

.card {
  margin-left: 10px;
  margin-right: 10px;
}

</style>
