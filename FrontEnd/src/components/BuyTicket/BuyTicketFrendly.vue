<template>
  <div class="container-fluid">
    <button
        type="button"
        class="btn btn-primary"
        v-show="false"
        data-toggle="modal"
        id="modalOpenBtn"
        data-target="#exampleModal"
    >
      Launch demo modal
    </button>

    <div
        class="modal fade"
        id="exampleModal"
        tabindex="-1"
        role="dialog"
        aria-labelledby="exampleModalLabel"
        aria-hidden="true"
    >
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Успех</h5>
            <button
                type="button"
                class="close"
                data-dismiss="modal"
                aria-label="Close"
            >
              <span aria-hidden="true">х</span>
            </button>
          </div>
          <div class="modal-body" v-html="message"></div>
          <div class="modal-footer">
            <button
                type="button"
                class="btn btn-secondary"
                data-dismiss="modal"
            >
              Закрыть
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="text-center title-block">
      <h1>Форма подтверждения дружеского оргвзноса</h1>
      <small class="form-text text-muted"
      >на создание туристического слёта Solar Systo Togathering 2026</small
      >
    </div>
    <div class="row" id="main-form">
      <div class="col-md-10 mx-auto">
        <div class="card mt-2 mx-auto">
          <div class="card-body">
            <div id="contact-form" role="form">
              <div class="controls">
                <div class="pp1 row">
                  <span>ШАГ 1.</span> Введи данные основного гостя, который тебе вносит средства:
                </div>
                <div class="row y-row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="form_email" class="hidder">Email куда прийдёт билет *</label>
                      <input
                          id="form_email"
                          type="email"
                          name="email"
                          class="form-control"
                          placeholder="Email: *"
                          required="required"
                          v-model="email"
                          data-error="Valid email is required."
                      />
                      <small class="form-text text-muted">
                        {{ getError('email') }}</small
                      >
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="form_phone" class="hidder">Телефон *</label>
                      <input
                          id="form_phone"
                          type="email"
                          name="phone"
                          class="form-control"
                          placeholder="Телефон:*"
                          required="required"
                          v-model="phone"
                          data-error="Valid phone is required."
                      />
                      <small class="form-text text-muted">
                        {{ getError('phone') }}</small
                      >
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="form_phone" class="hidder">Город *</label>
                      <input
                          id="form_phone"
                          type="text"
                          name="city"
                          class="form-control"
                          placeholder="Город:*"
                          required="required"
                          v-model="city"
                          data-error="Valid phone is required."
                      />
                      <small class="form-text text-muted">
                        {{ getError('city') }}</small
                      >
                    </div>
                  </div>

                </div>

                <div class="pp1 row">
                  <span>ШАГ 2.</span> Выбери тип оргвзноса:
                </div>

                <div class="mb-3" id="org-type">
                  <div class="col-3">
                    <label for="form_need">Тип оргвзноса: *</label>
                  </div>

                  <div class="mt-1 col-9">
                    <div class="in-choice">
                      <div
                          class="ticket-choice"
                          v-for="typeTickets in getTicketType"
                          v-bind:key="typeTickets.id"
                      >
                        <div class="form-check">
                          <label
                              class="form-check-label"
                              v-bind:for="typeTickets.id"
                          >
                            <input
                                type="radio"
                                class="form-check-input"
                                v-model="selectTypeTicket"
                                v-bind:value="typeTickets.id"
                                v-bind:id="typeTickets.id"
                                @change="sendTicketType()"
                            />
                            <span class="intckt">
                            <p>{{ typeTickets.name }}</p>
                           </span>
                          </label>
                          <small class="form-text text-muted">
                            {{ getError('ticket_type_id') }}
                          </small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row mt-3 mb-3" id="enter-guests">
                  <div class="pp2">Введи данные всех гостей, включая основного гостя!:</div>
                  <div class="not-first-guest input-group mb-3">
                    <input
                        type="text"
                        id="newGuest"
                        class="form-control"
                        placeholder="Имя и фамилия гостя"
                        aria-label="Имя и фамилия гостя"
                        v-model="newGuest"
                        aria-describedby="basic-addon1"

                    />
                    <input
                        type="email"
                        id="newEmailGuest"
                        class="form-control"
                        placeholder="Email этого гостя"
                        aria-label="E-mail этого гостя"
                        v-model="newGuestEmail"
                        aria-describedby="basic-addon1"

                    />
                    <!--small v-show="getSelectTicketType?.isLiveTicket">Номер живого билета(без нулей в начале)</small-->
                    <input
                        type="number"
                        id="newEmailGuest"
                        class="form-control"
                        placeholder="Номер живого билета(без нулей в начале)"
                        aria-label="Номер живого билета(без нулей в начале)"
                        v-model="newGuestNumber"
                        aria-describedby="basic-addon1"
                        v-show="getSelectTicketType?.isLiveTicket"
                    />

                    <div class="input-group-prepend">
                      <span
                          class="input-group-text btn"
                          @click="addGuest()"
                          id="basic-addon1"
                      >Добавить</span>
                    </div>
                  </div>
                </div>
                <div class="row x-row" v-show="guests.length > 0" id="adding-guests">
                  <div class="col-12">
                    <div class="form-group">
                      <div
                          class="input-group mb-3"
                          v-for="(itemGuest, index) in guests"
                          v-bind:key="index"
                      >
                        <input
                            type="text"
                            class="form-control"
                            readonly
                            v-bind:value="itemGuest.value"
                            aria-describedby="basic-addon2"
                        />
                        <input
                            type="email"
                            class="form-control"
                            readonly
                            v-bind:value="itemGuest.email"
                            aria-describedby="basic-addon2"
                        />
                        <input
                            type="number"
                            class="form-control"
                            readonly
                            v-bind:value="itemGuest.number"
                            aria-describedby="basic-addon2"
                        />
                        <div class="input-group-prepend">
                          <span
                              class="input-group-text btn"
                              @click="delGuest(index)"
                              id="basic-addon2"
                          >
                            <i class="fa fa-trash"></i>
                          </span>
                        </div>
                      </div>
                      <small class="form-text text-muted">
                        {{ getError('guests') }}</small
                      >
                    </div>
                  </div>
                </div>
                <div class="row col-12">
                  <h4 class="font-weight-normal" id="count-label">
                    Общее количество гостей в твоем заказе:
                    <span>{{ countGuests }}</span>
                  </h4>
                </div>

                <div class="row sub-warn"><b>ВНИМАНИЕ!</b> После оформления заказа на почты гостей придёт ссылка на анкету, которую необходимо заполнить, чтобы
                  активировать QR-коды, а также получить доступ к новому закрытому чату Solar Systo Togathering 2026.

                </div>



                <div class="row itog-row mb-4">
                  <div class="col-12">
                    <h4 class="my-lg-2 font-weight-normal">
                      Внесите стоимость одного билета <b>(за вычетом вашей комиссии)</b>:

                      <input
                          type="number"
                          id="newEmailGuest"
                          class="form-control"
                          placeholder="Внесите стоимость одного билета <b>(за вычетом вашей комиссии)</b>"
                          aria-label="Внесите стоимость одного билета <b>(за вычетом вашей комиссии)</b>"
                          v-model="price"
                          aria-describedby="basic-addon1"
                      />
                    </h4>
                  </div>

                </div>


                <div class="row" style="justify-content: center">
                  <div class="form-check" id="check-check">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        value=""
                        v-model="confirm"
                        id="defaultCheck1"
                    />
                    <label class="form-check-label" for="defaultCheck1">
                      Регистрируя добровольный оргвзнос, ты соглашаешься с
                      &nbsp;<a href="/conditions" target="_blank"><b>Правилами и условиями участия в туристическом слёте</b></a>
                      и <a href="/private" target="_blank"><b>Политикой обработки персональных данных.</b></a>
                    </label>
                  </div>
                  <div class="col-12">
                    <button
                        type="button"
                        :disabled="!isNotCorrect"
                        @click="orderTicket"
                        class="btn btn-lg btn-block btn-outline-primary reg-btn"
                    >
                      Зарегистрировать оргвзнос
                    </button>
                  </div>
                </div>



                <div
                    class="row justify-content-center"
                    v-if="!isNotCorrect"
                    style="text-align: center"
                >
                  Если кнопка не активна проверь все ли поля заполнены!
                </div>
                <div class="row mt-4" id="sub-order">
                  <div class="after-order">
                    <p>
                      После подтверждения перевода на e-mail придет <strong>электронный файл с QR-кодом</strong><br> для входа на Solar Systo Togathering 2026! А также ссылка на анкету для добавления в новый закрытый чат
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
              <button
                  type="button"
                  class="close"
                  data-dismiss="modal"
                  aria-label="Close"
              >
                <span aria-hidden="true">x</span>
              </button>
            </div>
            <div class="modal-body">
              <p>Modal body text goes here.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-primary">
                Save changes
              </button>
              <button
                  type="button"
                  class="btn btn-secondary"
                  data-dismiss="modal"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

export default {
  name: 'BuyTicketFrendly',
  props: {
    'userId': String
  },
  data() {
    return {
      isNotNeedQuestionnaire: false,
      preload: false,
      day: null,
      mount: null,
      hour: null,
      minute: null,
      selectTypesOfPayment: null,
      guests: [],
      masterName: '',
      newGuest: '',
      newGuestEmail: '',
      newGuestNumber: 0,
      isFirstGuestAdded: false,
      email: null,
      date: null,
      phone: null,
      city: null,
      idBuy: null,
      confirm: false,
      message: null,
      promoCode: null,
      messageForPromoCode: null,
      comment: null,
      price: 0,
    };
  },
  watch: {
    getTypesOfPayment: {
      immediate: true,
      handler(types) {
        if (types && types.length > 0) {
          this.selectTypesOfPayment = types[0].id;
        }
      },
    },
  },
  computed: {
    ...mapGetters('appFestivalTickets', [
      'getTypesOfPayment',
      'getTicketType',
      'isAllowedGuest',
      'isAllowedGuestMin',
      'getSelectTicketType',
      'getSelectTicketTypeId',
      'getSelectTicketTypeLimit',
      'getDiscountByPromoCode',
      'getPromoCodeName',
    ]),
    ...mapGetters('appUser', ['isAuth', 'getEmail', 'getUserData']),
    ...mapGetters('appOrder', ['getError']),
    selectTypesOfPaymentIsBilling: function () {
      let typesOfPaymentList = this.getTypesOfPayment;
      if(this.selectTypesOfPayment !== null ) {
        let select = typesOfPaymentList?.find(user => user.id == this.selectTypesOfPayment);
        return select?.is_billing ?? false;
      }
      return false;
    },

    /**
     * Проверка на ведение всех данных
     * @returns {false|*|null}
     */
    isNotCorrect: function () {

      let result = (
          this.selectTypeTicket !== null &&
          this.selectTypesOfPayment !== null &&
          this.phone !== null &&
          this.confirm === true &&
          (this.email)
      )
      result = result && this.guests.length > 0;


      return result;
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
            alert(
                'Привышен лимин по данному типу доступна только ' +
                this.getSelectTicketTypeLimit
            );
            this.setSelectTicketType(oldId);
          }
        }
      },
    },
    /**
     * Кол-во гостей
     * @returns {number}
     */
    countGuests: function () {
      return this.guests.length + ((!this.isNotNeedQuestionnaire && this.masterName.length > 0) > 0 ? 1 : 0);
    },
    /**
     * Проверка на добавление нового гостя
     * @returns {boolean}
     */
    isAllowedNewGuest: function () {
      if (this.getSelectTicketType !== null) {
        return (
            this.getSelectTicketTypeLimit === null ||
            this.getSelectTicketTypeLimit >= this.countGuests + 2
        );
      }
      return false;
    },
  },
  methods: {
    ...mapActions('appFestivalTickets', [
      'loadDataForOrderingTickets',
      'setSelectTicketType',
      'checkPromoCode',
      'clearPromoCode',
      'loadTypesOfPayment',
    ]),
    ...mapActions('appOrder', ['goToCreateFrendlyOrderTicket', 'clearError']),
    ...mapActions('appUser', ['loadUserData']),
    CopyTypesOfPayment: function (name) {
      let area = document.createElement('textarea');
      document.body.appendChild(area);
      area.value = name;
      area.select();
      document.execCommand('copy');
      document.body.removeChild(area);
    },
    sendTicketType: function () {
      this.guests = [];
      let select = this.selectTypeTicket;
      this.loadTypesOfPayment({
        ticket_type_id: select
      })
    },
    /**
     * Отправить промо код
     */
    sendPromoCode: function () {
      let self = this;
      this.checkPromoCode({
        promoCode: this.promoCode,
        typeOrder: this.getSelectTicketTypeId,
        callback: function (message) {
          self.messageForPromoCode = message;
        },
      });
    },
    /**
     * Добавить нового гостя
     */
    addGuest: function () {
      if (
          // Два поля всегда обязательны
          this.newGuest.length > 0 &&
          this.newGuestEmail.length > 0 &&
          // Третье поле обязательно только если флаг true
          (!this.getSelectTicketType?.isLiveTicket || this.newGuestNumber > 0)
      ) {
        this.guests.push({
          value: this.newGuest,
          email: this.newGuestEmail,
          number: this.newGuestNumber > 0 ? this.newGuestNumber : null,
        });
        this.newGuest = '';
        this.newGuestNumber = 0;
        this.newGuestEmail = '';
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
      let guests = this.guests;
      let data = {
        email: this.email,
        ticket_type_id: this.getSelectTicketTypeId,
        name: this.masterName,
        guests: guests,
        promo_code: this.promoCode,
        date: this.date,
        id_buy: this.idBuy,
        city: this.city,
        phone: this.phone,
        comment: this.comment,
        price: this.price,
        invite: this.$route.params.userId,
        types_of_payment_id: this.selectTypesOfPayment,
        festival_id: '9d679bcf-b438-4ddb-ac04-023fa9bff4b8',
        callback: function (result, message, link) {
          document.getElementById('modalOpenBtn').click();
          self.message = message;
          if (result) {
            self.clearData();
          }
          if (link !== null) {
            //  window.location.href = link;
          } else {
            self.message = message;
            document.getElementById('modalOpenBtn').click();
            self.preload = false;
          }
        },
      };
      console.log(data);
      this.goToCreateFrendlyOrderTicket(data);
    },
    /**
     * Очистить данные
     */
    clearData: async function () {
      this.guests = [];
      this.preload = false;
      this.newGuest = '';
      this.newGuestEmail = '';
      this.email = '';
      this.city = '';
      this.phone = '';
      this.messageForPromoCode = null;
      this.comment = null;
      this.confirm = false;
      this.price = 0;
      this.isFirstGuestAdded = false; // сбросить состояние до первого участника
      this.clearPromoCode();
    },
  },
  async created() {
    await this.loadDataForOrderingTickets({
      festival_id: '9d679bcf-b438-4ddb-ac04-023fa9bff4b8',
    });
    await this.clearError();
  },
};
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
