<template>
  <div class="container-fluid">
    <button type="button" class="btn btn-primary" v-show="false" data-toggle="modal" id="modalOpenBtn"
            data-target="#exampleModal">
      Launch demo modal
    </button>


    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
         aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Успех</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">х</span>
            </button>
          </div>
          <div class="modal-body" v-html="massage">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
          </div>
        </div>
      </div>
    </div>
    <div class="text-center title-block">
      <h1>Форма подтверждения добровольного оргвзноса</h1>
      <small class="form-text text-muted">на создание туристического слёта Солар Систо 2023 с 18 по 22 мая</small>
    </div>
    <div class="row" id="main-form">
      <div class="col-md-10 mx-auto">
        <div class="card mt-2 mx-auto">
          <div class="card-body">
              <div id="contact-form" role="form">
                <div class="controls">
                  <div class="pp1 row">
                    <span>ШАГ 1.</span> Введи свои контактные данные, после чего система автоматически создаст тебе
                    аккаунт:
                  </div>
                  <div class="row y-row">
                    <div class="col-md-4">
                      <div class="form-group">
                        <label for="form_email" class="hidder">Email *</label>
                        <input id="form_email"
                               type="email"
                               name="email"
                               class="form-control"
                               placeholder="Email: *"
                               required="required"
                               v-model="email"
                               v-bind:readonly="isAuth"
                               data-error="Valid email is required.">
                        <small class="form-text text-muted"> {{ getError('email') }}</small>
                      </div>
                    </div>

                    <div class="col-md-4">
                      <div class="form-group">
                        <label for="form_phone" class="hidder">Телефон *</label>
                        <input id="form_phone"
                               type="email"
                               name="phone"
                               class="form-control"
                               placeholder="Телефон:*"
                               required="required"
                               v-bind:readonly="getUserData('phone') !== null"
                               v-model="phone"
                               data-error="Valid phone is required.">
                        <small class="form-text text-muted"> {{ getError('phone') }}</small>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label for="form_phone" class="hidder">Город *</label>
                        <input id="form_phone"
                               type="text"
                               name="city"
                               class="form-control"
                               placeholder="Город:*"
                               required="required"
                               v-bind:readonly="getUserData('city') !== null"
                               v-model="city"
                               data-error="Valid phone is required.">
                        <small class="form-text text-muted"> {{ getError('city') }}</small>
                      </div>
                    </div>
                  </div>
                  <div class="pp1 row">
                    <span>ШАГ 2.</span> Выбери тип оргвзноса и введи данные каждого гостя, за которого будешь вносить
                    средства:
                  </div>

                  <div class="row mb-3">
                    <div class="col-3">
                      <label for="form_need">Тип оргвзноса: *</label>
                    </div>

                    <div class="col-9">
                      <select id="form_need"
                              name="need"
                              class="col-md-8 form-select"
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
                      <small class="form-text text-muted"> {{ getError('ticket_type_id') }}</small>
                    </div>
                  </div>


                  <!--                  Промокод-->
                  <div class="row">
                    <div class="col-3">
                        <label for="form_promo_cod">Промокод:</label>
                    </div>
                    <div class="col-4">
                        <div class="input-group" id="promo-input">
                          <input type="text"
                                 id="form_promo_cod"
                                 class="form-control"
                                 placeholder="Промокод"
                                 aria-label="Промокод"
                                 v-model="promoCode"
                                 aria-describedby="basic-addon1"
                                 @blur="sendPromoCode">
                          <span class="input-group-text"
                                @click="sendPromoCode"
                                id="basic-addon1"></span>
                        </div>
                  </div>
                    <div class="col-5">
                      <small class="form-text text-muted id-info" v-show="massageForPromoCode!==null">
                        {{ massageForPromoCode }}
                      </small>
                    </div>
                    </div>

                  <div class="row mt-3 mb-3">
                    <div class="col-5">
                      <label for="newGuest" class="reg-label">Данные о гостях:</label>
                    </div>
                    <div class="input-group mb-3">
                      <input type="text"
                             id="newGuest"
                             class="form-control"
                             placeholder="Введи Имя и Фамилию гостя и нажми Добавить"
                             aria-label="Введи Имя и Фамилию гостя и нажми Добавить"
                             v-model="newGuest"
                             :disabled="!isAllowedNewGuest"
                             aria-describedby="basic-addon1"
                             @blur="addGuest">
                      <div class="input-group-prepend">
                              <span class="input-group-text btn"
                                    @click="addGuest()"
                                    id="basic-addon1">Добавить</span>
                      </div>
                    </div>
                  </div>

                  <div class="row x-row" v-show="guests.length > 0">
                    <div class="col-12">
                      <div class="form-group">
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
                        <small class="form-text text-muted"> {{ getError('guests') }}</small>
                      </div>
                    </div>
                  </div>

                  <div class="row itog-row mb-4" v-show="totalPrice > 0">
                    <div class="col-4">
                      <h4 class="my-lg-2 font-weight-normal">Итого к внесению: {{ totalPrice }} <small class="text-muted">
                        руб.</small></h4>

                    </div>
                    <div class="col-4">
                      <h4 class="my-lg-2 font-weight-normal">Кол-во гостей: <small class="text-muted">{{ countGuests }}</small></h4>
                    </div>

                    <div class="col-4" v-show="totalPrice > 0 && getDiscountByPromoCode > 0">
                      <h4 class="my-lg-2 font-weight-normal">Скидка по промокоду: <small class="text-muted">{{ getDiscountByPromoCode * countGuests }}  рублей</small></h4>
                    </div>

                  </div>



                  <div class="pp1 row">
                    <span>ШАГ 3.</span> Выбери куда ты будешь переводить средства, осуществи перевод и заполни данные о
                    платеже!
                  </div>
                  <div class="row">
                    <div class="col-12">
                      <div class="form-group">
                        <label for="form_need" class="hidder">Способ оплаты: *</label>
                        <div class="in-choice">
                          <div class="payment-choice" v-for="typesOfPayment in getTypesOfPayment"
                               v-bind:key="typesOfPayment.id">
                            <div class="form-check">
                              <label class="form-check-label" v-bind:for="typesOfPayment.id">
                                <input type="radio"
                                       class="form-check-input"
                                       v-model="selectTypesOfPayment"
                                       v-bind:value="typesOfPayment.id"
                                       v-bind:id="typesOfPayment.id">
                                <span>
                              {{ typesOfPayment.name }}
                              <i class="copy-payment" title="Нажми, чтобы скопировать реквизиты"
                                 @click="CopyTypesOfPayment(typesOfPayment.name)"></i>
                                </span>
                              </label>

                              <small class="form-text text-muted"> {{ getError('types_of_payment_id') }}</small>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="copy-btn">Нажми на <span></span> чтобы скопировать реквизиты</div>
                    </div>
                  </div>
                  <div class="row flex-flex justify-content-center mt-2" style="color:var(--c-red); text-align: center;  font-weight: bold;">ТЕПЕРЬ СОВЕРШИ ПЕРЕВОД СРЕДСТВ САМОСТОЯТЕЛЬНО В ПРИЛОЖЕНИИ БАНКА</div>
                  <div class="row mb-4 flex-flex justify-content-center" style="text-align: center;  font-weight: bold;">и только после этого заполни поля ниже</div>
                  <div class="row mb-4 flex-flex">
                    <div class="col-3">
                      <label for="idBuy">Идентификатор платежа:</label>
                    </div>
                    <div class="col-3">
                      <input class="form-control" v-model="idBuy" id="idBuy">
                    </div>
                    <div class="col-6">
                      <small class="form-text text-muted id-info">
                        При переводах на Сбербанк напиши сюда <b>последние 4 цифры номера карты</b>, с которой был сделан перевод
                      </small>
                    </div>
                    <small class="form-text text-muted"> {{ getError('idBuy') }}</small>
                  </div>
                  <!--                  Дата платежа -->
                  <div class="row mt-4">
                    <div class="col-3">
                      <label for="form_message">Когда был сделан платеж?</label>
                    </div>
                    <div class="col-9 flex-flex">
                      <input type="text" class="form-control"
                             placeholder="Например: 18 февраля в 13.20"
                             aria-label="Дата и время перевода"
                             v-model="date">
                    </div>
                    <small class="form-text text-muted"> {{ getError('date') }}</small>
                  </div>

                  <div class="row">
                    <div class="col-3">
                      <label for="idBuy">Комментарий к заказу:</label>
                    </div>

                    <div class="col-9">
                      <textarea class="form-control order-text" v-model="comment" id="idBuy"></textarea>
                    </div>


                  </div>
                  <div class="row mt-4">
                    <div class="form-check" id="check-check">
                      <input class="form-check-input"
                             type="checkbox"
                             value=""
                             v-model="confirm"
                             id="defaultCheck1">
                      <label class="form-check-label" for="defaultCheck1">
                        Регистрируя добровольный оргвзнос, ты соглашаешься с &nbsp;<a
                          href="/conditions" target="_blank"><b>условиями туристического слёта</b></a>
                      </label>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12">
                      <button type="button"
                              :disabled="preload || !isNotCorrect"
                              @click="orderTicket"
                              class="btn btn-lg btn-block btn-outline-primary reg-btn">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" v-show="preload"></span>
                        Зарегистрировать оргвзнос</button>
                    </div>
                  </div>
                  <div class="row justify-content-center" v-if="!isNotCorrect" style="text-align: center;">Если кнопка не активна проверь все ли поля заполнены!</div>
                  <div class="row mt-4">
                    <div class="after-order">
                      <p>
                      После оплаты в течение 3-4 дней на твой e-mail придет подтверждение оргвзноса и <br><strong>электронный билет с QR-кодом</strong> для входа на Солар Систо 2023!
                      </p>
                      <p>
                      <b>Будь внимателен!</b> В этом году вход будет осуществляться только при предъявлении билета с кодом на
                      экране телефона или в распечатанном виде (как в аэропортах).
                      Позаботься об этом заранее! Прежняя система с ID и фамилией действовать не будет.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
          </div>
        </div>
        <!-- /.8 -->
      </div>
      <!-- /.row-->
      <div class="modal" tabindex="-1" role="dialog" id="myModal">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Успех</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">x</span>
              </button>
            </div>
            <div class="modal-body">
              <p>Modal body text goes here.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-primary">Save changes</button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</template>

<script>
import {mapGetters, mapActions} from 'vuex';
export default {
  name: "BuyTicket",
  data() {
    return {
      preload: false,
      day: null,
      mount: null,
      mounts: {
        2: 'Февраль',
        3: 'Март',
        4: 'Апрель',
        5: 'Май',
      },
      hour: null,
      minute: null,
      selectTypesOfPayment: null,
      guests: [],
      newGuest: null,
      email: null,
      date: null,
      phone: null,
      city: null,
      idBuy: null,
      confirm: true,
      massage: null,
      promoCode: null,
      massageForPromoCode: null,
      comment: null,
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
      'getUserData'
    ]),
    ...mapGetters('appOrder', [
      'getError'
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
          this.phone !== null &&
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
    /**
     * Стоимость билета
     * @returns {number}
     */
    totalPrice: function () {
      let price = 0;

      if (this.getSelectTicketType !== null) {
        price = this.getSelectTicketType.price;
        let count = this.getSelectTicketTypeLimit !== null ? 1 : this.guests.length;
        return (price * count) - (this.getDiscountByPromoCode * count);
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
      'clearError',
    ]),
    ...mapActions('appUser', [
      'loadUserData',
    ]),
    CopyTypesOfPayment: function (name) {
      let card = name.replace(/[^0-9, ]/g, "");
      let area = document.createElement('textarea');

      document.body.appendChild(area);
      area.value = card;
      area.select();
      document.execCommand("copy");
      document.body.removeChild(area);
    },
    /**
     * Отправить промо код
     */
    sendPromoCode: function () {
      let self = this;
      this.checkPromoCode({
        promoCode: this.promoCode,
        typeOrder: this.getSelectTicketTypeId,
        callback: function (massage) {
          self.massageForPromoCode = massage;
        }
      });
    },
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
      this.preload = true;
      this.goToCreateOrderTicket({
        'email': this.email,
        'ticket_type_id': this.getSelectTicketTypeId,
        'guests': this.guests,
        'promo_code': this.promoCode,
        'date': this.date,
        'id_buy': this.idBuy,
        'city': this.city,
        'phone': this.phone,
        'comment': this.comment,
        'types_of_payment_id': this.selectTypesOfPayment,
        'callback': function (result, massage) {
          if (result) {
            self.clearData();
          }
          self.massage = massage;
          document.getElementById('modalOpenBtn').click();
          self.preload = false;
        }
      })
    },
    /**
     * Очистить данные
     */
    clearData: function () {
      this.selectTypesOfPayment = null;
      this.guests = [];
      this.preload = false;
      this.newGuest = '';
      this.email = this.getEmail;
      this.promoCode = null;
      this.day = null;
      this.mount = null;
      this.date = null;
      this.minute = null;
      this.massageForPromoCode = null;
      this.idBuy = null;
      this.city = null;
      this.phone = null;
      this.comment = null;
      this.confirm = true;
      this.clearPromoCode();
    },
  },
  async created() {
    await this.loadDataForOrderingTickets();
    await this.clearError();
    if (this.isAuth) {
      let self = this;
      await this.loadUserData({
        callback: function (data) {
          self.phone = data.phone;
          self.city = data.city;
        }
      });
      this.email = this.getEmail;
    }
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
