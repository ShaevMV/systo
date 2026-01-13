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
      <h1>Форма подтверждения добровольного оргвзноса</h1>
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
                  <span>ШАГ 1.</span> Введи свои контактные данные, после чего
                  система автоматически создаст тебе аккаунт:
                </div>
                <div class="row y-row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="form_email" class="hidder">Email *</label>
                      <input
                          id="form_email"
                          type="email"
                          name="email"
                          class="form-control"
                          placeholder="Email: *"
                          required="required"
                          v-model="email"
                          v-bind:readonly="isAuth"
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
                          v-bind:readonly="getUserData('phone') !== null"
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
                          v-bind:readonly="getUserData('city') !== null"
                          v-model="city"
                          data-error="Valid phone is required."
                      />
                      <small class="form-text text-muted">
                        {{ getError('city') }}</small
                      >
                    </div>
                  </div>

                </div>
                <div class="pp2 row">Заполни анкетные данные, как основного гостя:</div>
                <div class="quest-item" v-show="!isNotNeedQuestionnaire">
                  <label for="questionnaire_namey">Твои Имя и Фамилия: *</label>

                  <!-- Сюда добавил поле Имя и Фамилия -->
                  <div class="input-group" id="promo-input">
                    <input
                        type="text"
                        id="questionnaire_namey"
                        class="form-control"
                        placeholder="Твои Имя и Фамилия"
                        aria-label="Твои Имя и Фамилия"
                        v-model="masterName"
                        aria-describedby="basic-addon1"
                    />
                  </div>
                </div>
                <label id="my-own" class="row">
                  <input
                      type="checkbox"
                      class="form-check-input"
                      v-model="isNotNeedQuestionnaire"
                  >
                  <span>Я хочу внести оргвзнос только за своих друзей</span>
                </label>
                <div class="row mt-3 mb-3" id="enter-guests">
                  <div class="pp2">Введи данные дополнительных своих друзей, за которых ты хочешь внести оргвзнос:</div>
                  <div class="not-first-guest input-group mb-3">

                    <input
                        type="text"
                        id="newGuest"
                        class="form-control"
                        placeholder="Имя и фамилия твоего друга"
                        aria-label="Имя и фамилия твоего друга"
                        v-model="newGuest"
                        aria-describedby="basic-addon1"
                        @blur="addGuest"
                    />
                    <input
                        type="email"
                        id="newEmailGuest"
                        class="form-control"
                        placeholder="Email твоего друга"
                        aria-label="E-mail этого друга"
                        v-model="newGuestEmail"
                        aria-describedby="basic-addon1"
                        @blur="addGuest"
                    />
                    <div class="input-group-prepend">
                      <span
                          class="input-group-text btn"
                          @click="addGuest()"
                          id="basic-addon1"
                      >Добавить</span
                      >
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
                <div class="col-4">
                  <h4 class="font-weight-normal" id="count-label">
                    Общее количество гостей в твоем заказе:
                    <span>{{ countGuests }}</span>
                  </h4>
                </div>

                <div class="row sub-warn"><b>ВНИМАНИЕ!</b> После оформления заказа на почту твоих друзей придёт ссылка на анкету, которую им необходимо заполнить для активации их QR-кодов при входе на Систо. Сами же QR-коды придут на твою почту и будут доступны в твоём личном кабинете.
                  От твоих друзей требуется только заполнить анкетные данные по аналогии с теми, что ты заполнял выше. Спасибо за понимание.
                </div>

                <div class="pp1 row">
                  <span>ШАГ 2.</span> Выбери тип оргвзноса и введи данные
                  каждого гостя, за которого будешь вносить средства:
                </div>

                <div class="mb-3">
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
                            />
                            <span class="intckt">
                            <p>
                              {{ typeTickets.name }} /
                              {{ typeTickets.price }} руб.
                            </p>
                            <p v-html="typeTickets.description"></p>
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

                <!--                  Промокод-->
                <div class="row">
                  <div class="col-3">
                    <label for="form_promo_cod">Промокод:</label>
                  </div>
                  <div class="col-4">
                    <div class="input-group" id="promo-input">
                      <input
                          type="text"
                          id="form_promo_cod"
                          class="form-control"
                          placeholder="Промокод"
                          aria-label="Промокод"
                          v-model="promoCode"
                          aria-describedby="basic-addon1"
                          @blur="sendPromoCode"
                      />
                      <span
                          class="input-group-text"
                          @click="sendPromoCode"
                          id="basic-addon1"
                      ></span>
                    </div>
                  </div>
                  <div class="col-5">
                    <small
                        class="form-text text-muted id-info"
                        v-show="messageForPromoCode !== null"
                    >
                      {{ messageForPromoCode }}
                    </small>
                  </div>
                </div>



                <div class="row itog-row mb-4" v-show="totalPrice !== null">
                  <div class="col-4">
                    <h4 class="my-lg-2 font-weight-normal">
                      Итого к внесению:
                      <small class="text-muted">{{ totalPrice }} руб.</small>
                    </h4>
                  </div>


                  <div class="col-4" v-show="getDiscountByPromoCode > 0">
                    <h4 class="my-lg-2 font-weight-normal">
                      Скидка по промокоду:
                      <small class="text-muted"
                      >{{
                          getDiscountByPromoCode * countGuests
                        }}
                        рублей</small
                      >
                    </h4>
                  </div>
                </div>

                <div class="pp1 row">
                  <span>ШАГ 3.</span> Выбери куда ты будешь переводить средства,
                  осуществи перевод и заполни данные о платеже!
                </div>
                <div class="row">
                  <div class="col-12">
                    <div class="form-group">
                      <label for="form_need" class="hidder"
                      >Способ оплаты: *</label>
                      <div class="in-choice">
                        <div
                            class="payment-choice"
                            v-for="typesOfPayment in getTypesOfPayment"
                            v-bind:key="typesOfPayment.id"
                        >
                          <div class="form-check">
                            <label
                                class="form-check-label"
                                v-bind:for="typesOfPayment.id"
                            >
                              <input
                                  type="radio"
                                  class="form-check-input"
                                  v-model="selectTypesOfPayment"
                                  v-bind:value="typesOfPayment.id"
                                  v-bind:id="typesOfPayment.id"
                              />
                              <span>
                                {{ typesOfPayment.name }}
                                <i
                                    class="copy-payment"
                                    title="Нажми, чтобы скопировать реквизиты"
                                    @click="
                                    CopyTypesOfPayment(typesOfPayment.card)
                                  "
                                ></i>
                              </span>
                            </label>

                            <small class="form-text text-muted">
                              {{ getError('types_of_payment_id') }}</small
                            >
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="copy-btn">
                      Нажми на <span></span> чтобы скопировать реквизиты
                    </div>
                  </div>
                </div>
                <div v-show="!selectTypesOfPaymentIsBilling">
                <div
                    class="row flex-flex justify-content-center mt-7"
                    style="
                    color: var(--c-red);
                    text-align: center;
                    font-weight: bold;
                  "
                >
                  ТЕПЕРЬ СОВЕРШИ ПЕРЕВОД СРЕДСТВ САМОСТОЯТЕЛЬНО В ПРИЛОЖЕНИИ
                  БАНКА
                </div>
                <div
                    class="row mb-4 flex-flex justify-content-center"
                    style="text-align: center; font-weight: bold"
                >
                  и только после этого заполни поля ниже
                </div>
                <div class="row mb-4 flex-flex">
                  <div class="col-3">
                    <label for="idBuy">Идентификатор платежа:</label>
                  </div>
                  <div class="col-3">
                    <input class="form-control" v-model="idBuy" id="idBuy" />
                  </div>
                  <div class="col-6">
                    <small class="form-text text-muted id-info">
                      При переводах на Сбербанк напиши сюда
                      <b>последние 4 цифры номера карты</b>, с которой был
                      сделан перевод
                    </small>
                  </div>
                  <small class="form-text text-muted">
                    {{ getError('idBuy') }}</small
                  >
                </div>
                <!--                  Дата платежа -->
                <div class="row mt-4">
                  <div class="col-3">
                    <label for="form_message">Когда был сделан платеж?</label>
                  </div>
                  <div class="col-9 flex-flex">
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Например: 18 февраля в 13.20"
                        aria-label="Дата и время перевода"
                        v-model="date"
                    />
                  </div>
                  <small class="form-text text-muted">
                    {{ getError('date') }}</small
                  >
                </div>

                <div class="row">
                  <div class="col-3">
                    <label for="idBuy">Комментарий к платежу:</label>
                  </div>

                  <div class="col-9">
                    <textarea
                        class="form-control order-text"
                        v-model="comment"
                        id="idBuy"
                    ></textarea>
                  </div>
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
                        &nbsp;<a href="/conditions" target="_blank"><b>условиями туристического слёта</b></a>
                        и <a href="/private" target="_blank"><b>Политикой обработки персональных данных.</b></a>
                      </label>
                    </div>
                  <div class="col-12">
                    <button
                        type="button"
                        :disabled="preload || !isNotCorrect"
                        @click="orderTicket"
                        class="btn btn-lg btn-block btn-outline-primary reg-btn"
                    >
                      <span
                          class="spinner-border spinner-border-sm"
                          role="status"
                          aria-hidden="true"
                          v-show="preload"
                      ></span>
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
                      После оплаты в течение 3-4 дней на твой e-mail придет
                      подтверждение оргвзноса и <br /><strong
                    >электронный билет с QR-кодом</strong
                    >
                      для входа на Solar Systo Togathering 2026!
                    </p>
                    <!--p>
                      <b>«Живые билеты» в виде памятной карточки можно будет приобрести в </b>
                      <br />
                      Санкт-Петербурге и Москве ориентировочно к концу января.
                      <br />
                      Все гости, внесшие средства электронно смогут получиться памятные конверты с карточкой и наклейкой на инфоцентре во время Систо.
                      <br />

                      <a href="https://t.me/cacaotemple" target="_blank"
                      >Телеграм </a
                      ><br />

                      <a href="https://cacaotemple.ru" target="_blank">Сайт </a
                      ><br />

                      <a
                          href="https://yandex.ru/navi/org/kakao_templ/156023560596?si=hgcx2kzvw06q9hvz04e4a1dp4g"
                          target="_blank"
                      >Как проехать</a
                      ><br />
                      <br />
                      "Живой билет" является таким же видом оргвзноса, как и
                      электронный, просто в этом случае вы получите конверт
                      внутри которого будет карта участника с номером и
                      сувенирная продукция. При регистрации оргвзноса электронно
                      - отдельно покупать "живой билет" не нужно!
                    </p-->
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
  name: 'BuyTicket',
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
        let select = typesOfPaymentList.find(user => user.id == this.selectTypesOfPayment);
        return select.is_billing ?? false;
      }
      return false;
    },

    /**
     * Проверка на ведение всех данных
     * @returns {false|*|null}
     */
    isNotCorrect: function () {
      let group = true;

      if (this.getSelectTicketType !== null) {
        if (!this.isAllowedGuestMin(this.guests.length)) {
          group = false;
        }
      }

      let result = (
          this.selectTypeTicket !== null &&
          this.selectTypesOfPayment !== null &&
          this.phone !== null &&
          group &&
          (this.isAuth || this.email)
      )

      if(!this.isNotNeedQuestionnaire) {
        result = result && this.masterName.length > 0;
      } else {
        result = result && this.guests.length > 0;
      }

      if(!this.selectTypesOfPaymentIsBilling) {
        result = result &&
            this.date !== null &&
            this.confirm === true &&
            this.idBuy !== null;
      }

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
     * Стоимость билета
     */
    totalPrice: function () {
      let price = null;
      let countTicket = this.guests.length + ((!this.isNotNeedQuestionnaire && this.masterName.length > 0) ? 1 : 0);
      if (this.getSelectTicketType !== null) {
        price = this.getSelectTicketType.price;
        let count =
            this.getSelectTicketTypeLimit !== null ? 1 : countTicket;
        return price * count - this.getDiscountByPromoCode * count;
      }

      return null;
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
    ]),
    ...mapActions('appOrder', ['goToCreateOrderTicket', 'clearError']),
    ...mapActions('appUser', ['loadUserData']),
    CopyTypesOfPayment: function (name) {
      let area = document.createElement('textarea');
      document.body.appendChild(area);
      area.value = name;
      area.select();
      document.execCommand('copy');
      document.body.removeChild(area);
    },
    updateQuestionnaire(updatedQuestionnaire) {
      this.questionnaire = updatedQuestionnaire;
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
      if (this.newGuest.length > 0 && this.newGuestEmail.length > 0) {
        this.guests.push({
          value: this.newGuest,
          email: this.newGuestEmail,
        });
        this.newGuest = '';
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
        masterName: this.masterName,
        guests: guests,
        promo_code: this.promoCode,
        date: this.date,
        id_buy: this.idBuy,
        city: this.city,
        phone: this.phone,
        comment: this.comment,
        types_of_payment_id: this.selectTypesOfPayment,
        festival_id: '9d679bcf-b438-4ddb-ac04-023fa9bff4b8',
        callback: function (result, message, link) {
          if (result) {
            self.clearData();
          }
          if (link !== null) {
            window.location.href = link;
          } else {
            self.message = message;
            document.getElementById('modalOpenBtn').click();
            self.preload = false;
          }
        },
      };
      if(!this.isNotNeedQuestionnaire) {
        data.questionnaire = this.questionnaire;
      }

      this.goToCreateOrderTicket(data);
    },
    /**
     * Очистить данные
     */
    clearData: async function () {
      this.selectTypesOfPayment = null;
      this.guests = [];
      this.preload = false;
      this.newGuest = '';
      this.newGuestEmail = '';
      this.email = this.getEmail;
      this.promoCode = null;
      this.day = null;
      this.mount = null;
      this.date = null;
      this.minute = null;
      this.messageForPromoCode = null;
      this.idBuy = null;
      this.comment = null;
      this.confirm = false;
      this.isFirstGuestAdded = false; // сбросить состояние до первого участника
      this.clearPromoCode();
    },
  },
  async created() {
    await this.loadDataForOrderingTickets({
      festival_id: '9d679bcf-b438-4ddb-ac04-023fa9bff4b8',
    });
    await this.clearError();
    if (this.isAuth) {
      let self = this;
      await this.loadUserData({
        callback: function (data) {
          self.phone = data.phone;
          self.city = data.city;
        },
      });
      this.email = this.getEmail;
    }
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
