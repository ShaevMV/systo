<template>
  <div class="container-fluid order-page">
    <div class="title-block text-center">
      <h1 class="card-title">Заказ № {{ getDateKilter }}</h1>
    </div>

    <div class="row">
      <div class="col-lg-10 col-xl-8 mx-auto">

        <!-- ШАПКА ЗАКАЗА -->
        <div class="card mb-3 order-summary">
          <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start">
              <div class="order-summary__main">
                <span class="badge order-status-badge"
                      :style="{ backgroundColor: statusColor(getStatus) }">
                  {{ getHumanStatus }}
                </span>
                <div class="order-summary__total mt-2">
                  Итого: <strong>{{ formatMoney(getTotalPrice) }} ₽</strong>
                </div>
              </div>
            </div>

            <dl class="row order-meta mt-3 mb-0">
              <dt class="col-5 col-sm-4 text-muted">Покупатель</dt>
              <dd class="col-7 col-sm-8">{{ getEmail || '—' }}</dd>

              <template v-if="!getFriendlyId">
                <dt class="col-5 col-sm-4 text-muted">Тип оплаты</dt>
                <dd class="col-7 col-sm-8">{{ getTypeOfPayment || '—' }}</dd>
              </template>

              <dt class="col-5 col-sm-4 text-muted">Дата создания</dt>
              <dd class="col-7 col-sm-8">{{ getDateCreate || '—' }}</dd>

              <template v-if="!getFriendlyId">
                <dt class="col-5 col-sm-4 text-muted">Дата оплаты</dt>
                <dd class="col-7 col-sm-8">{{ getDateBuy || '—' }}</dd>
              </template>

              <template v-if="getLocationName">
                <dt class="col-5 col-sm-4 text-muted">Локация</dt>
                <dd class="col-7 col-sm-8">{{ getLocationName }}</dd>
              </template>
            </dl>
          </div>
        </div>

        <!-- ГОСТИ (для гостя — только просмотр карточками) -->
        <div v-if="!(isAdmin || isPusher || isCurator || isPusherCurator)">
          <h5 class="section-title">Состав заказа ({{ getGuestList.length }})</h5>

          <div v-for="(guest, index) in getGuestList"
               :key="guest.id || index"
               class="card mb-2 guest-card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div class="guest-card__person">
                  <div class="guest-card__name">
                    {{ isParkingGuest(guest) ? '🚗 ' : '' }}{{ guest.value }}
                  </div>
                  <div v-if="guest.email" class="guest-card__email text-muted small">
                    {{ guest.email }}
                  </div>
                  <div class="guest-card__type">
                    <span class="badge badge-light type-badge">{{ ticketTypeName(guest) }}</span>
                    <span v-if="guest.is_live_ticket && guest.number"
                          class="badge badge-info ml-1">Живой билет № {{ guest.number }}</span>
                    <span v-else-if="guest.is_live_ticket"
                          class="badge badge-secondary ml-1">Живой билет</span>
                  </div>
                </div>
                <div class="guest-card__total text-right">
                  <strong>{{ formatMoney(guestTotal(guest)) }} ₽</strong>
                </div>
              </div>

              <!-- Опции гостя -->
              <ul v-if="guest.options && guest.options.length" class="guest-options mt-2 mb-0">
                <li v-for="(opt, oi) in guest.options" :key="oi"
                    class="d-flex justify-content-between">
                  <span>+ {{ opt.name }}</span>
                  <span class="text-muted">{{ formatMoney(opt.price) }} ₽</span>
                </li>
              </ul>

              <!-- Промокод гостя -->
              <div v-if="guest.promo_code" class="guest-promo mt-2 small">
                Промокод: <span class="badge badge-success">{{ guest.promo_code }}</span>
              </div>

              <!-- Скачать билет (PDF) — только если билет уже создан (заказ оплачен) -->
              <div v-if="guestTicket(guest)" class="mt-2">
                <button type="button"
                        @click="downloadGuestTicket(guest)"
                        class="btn btn-outline-primary btn-sm guest-pdf-btn">
                  📄 Скачать билет (PDF)
                </button>
              </div>

              <!-- Разбивка цены -->
              <div v-if="hasPriceSnapshot(guest)" class="guest-price-breakdown mt-2 small text-muted">
                <span>Билет {{ formatMoney(guest.price_snapshot.base_price) }} ₽</span>
                <span v-if="guest.price_snapshot.options_sum">
                  + опции {{ formatMoney(guest.price_snapshot.options_sum) }} ₽</span>
                <span v-if="guest.price_snapshot.discount">
                  − скидка {{ formatMoney(guest.price_snapshot.discount) }} ₽</span>
                <span class="guest-price-breakdown__eq">
                  = {{ formatMoney(guestTotal(guest)) }} ₽</span>
              </div>
            </div>
          </div>

          <!-- ИТОГ -->
          <div class="card mb-3 order-footer">
            <div class="card-body py-2">
              <div class="d-flex justify-content-between">
                <span class="text-muted">Сумма по гостям</span>
                <span>{{ formatMoney(getGuestsSum) }} ₽</span>
              </div>
              <div v-if="getDiscountValue" class="d-flex justify-content-between text-muted">
                <span>Скидка</span>
                <span>− {{ formatMoney(getDiscountValue) }} ₽</span>
              </div>
              <hr class="my-2">
              <div class="d-flex justify-content-between order-footer__total">
                <strong>Итого к оплате</strong>
                <strong>{{ formatMoney(getTotalPrice) }} ₽</strong>
              </div>
            </div>
          </div>
        </div>

        <!-- Режим редактирования (admin / pusher / curator) -->
        <div v-else class="card mb-3">
          <div class="card-body">
            <h5 class="section-title">Гости заказа</h5>
            <div class="mb-2 text-right">
              <span class="badge badge-light">Итого: {{ formatMoney(getTotalPrice) }} ₽</span>
            </div>
            <new-ticket
              :oldGuests="getGuestList"
              :is-list="getCuratorId !== null"
            />
          </div>
        </div>

        <!-- БИЛЕТЫ / PDF / АНКЕТЫ -->
        <div class="card mb-3">
          <div class="card-body">
            <order-button
              :id="getId"
              :list-tickets="getTickets"
              :status="getStatus"/>
          </div>
        </div>

        <!-- Автомобили (заказы-списки) -->
        <order-autos
          :order-id="getId"
          :curator-id="getCuratorId"
          :autos="getAutos"
        />

        <button type="button"
                @click="back"
                class="btn btn-primary x-button mt-3">Назад в МОИ ОРГВЗНОСЫ</button>

        <!-- Изменение цены (admin) для friendly-заказов -->
        <div v-if="isAdmin && getFriendlyId" class="card mt-3">
          <div class="card-body">
            <h6 class="section-title">Изменить стоимость</h6>
            <correct-price
              :id="getId"
              :oldPrice="getTotalPrice"/>
          </div>
        </div>

        <order-history
          v-if="isAdmin"
          :order-id="getId"
        />
      </div>
    </div>
  </div>
</template>

<script>
import {mapGetters, mapActions} from "vuex";
import OrderButton from "@/components/Order/OrderButton.vue";
import NewTicket from "@/components/Order/NewTicket.vue";
import OrderHistory from "@/components/Order/OrderHistory.vue";
import OrderAutos from "@/components/Order/OrderAutos.vue";
import CorrectPrice from "@/components/OrderFriendly/CorrectPrice.vue";

export default {
  name: "OrderItem",
  components: {NewTicket, OrderButton, OrderHistory, OrderAutos, CorrectPrice},
  computed: {
    ...mapGetters('appOrder', [
      'getOrderItem',
    ]),
    ...mapGetters('appFestivalTickets', [
      'getTicketType',
    ]),
    ...mapGetters('appUser', [
      'isAdmin',
      'isPusher',
      'isCurator',
      'isPusherCurator',
    ]),
    /**
     * Список гостей заказа (формат v2.6.0: у каждого свой тип/опции/цена).
     * @returns {Array}
     */
    getGuestList: function () {
      return this.getOrderItem.guests || [];
    },
    /**
     * Созданные билеты (PDF доступны после оплаты).
     * @returns {Array}
     */
    getTickets: function () {
      return this.getOrderItem.tickets || [];
    },
    getTotalPrice: function () {
      return this.getOrderItem.totalPrice;
    },
    /**
     * Сумма итогов по всем гостям (из price_snapshot.total).
     * @returns {number}
     */
    getGuestsSum: function () {
      return this.getGuestList.reduce((sum, g) => sum + this.guestTotal(g), 0);
    },
    /**
     * Общая скидка заказа (число или 0).
     * @returns {number}
     */
    getDiscountValue: function () {
      return Number(this.getOrderItem.discount) || 0;
    },
    getTypeOfPayment: function () {
      return this.getOrderItem.typeOfPayment;
    },
    getHumanStatus: function () {
      return this.getOrderItem.humanStatus;
    },
    getStatus: function () {
      return this.getOrderItem.status;
    },
    getDateBuy: function () {
      return this.getOrderItem.dateBuy;
    },
    getDateCreate: function () {
      return this.getOrderItem.dateCreate || this.getOrderItem.dateBuy;
    },
    getDateKilter: function () {
      return this.getOrderItem.kilter;
    },
    getEmail: function () {
      return this.getOrderItem.email;
    },
    getId: function () {
      return this.getOrderItem.id;
    },
    getFriendlyId: function () {
      return this.getOrderItem.friendly_id;
    },
    getCuratorId: function () {
      return this.getOrderItem.curator_id || null;
    },
    getLocationName: function () {
      return this.getOrderItem.location_name || null;
    },
    getAutos: function () {
      return this.getOrderItem.autos || [];
    },
  },
  watch: {
    /**
     * Когда заказ загрузился — подтягиваем типы билетов для маппинга UUID → название.
     */
    getGuestList: {
      immediate: true,
      handler(guests) {
        this.ensureTicketTypesLoaded(guests);
      },
    },
  },
  methods: {
    ...mapActions('appFestivalTickets', ['getListPriceFor']),
    ...mapActions('appOrder', ['getUrlForPdf']),
    /**
     * Билет, созданный для этого гостя.
     * В формате v2.6.0 id билета совпадает с id гостя
     * (бэкенд создаёт билет с id гостя в TicketApplication::createList).
     * Если билета ещё нет (заказ не оплачен) — вернёт undefined.
     * @returns {Object|undefined}
     */
    guestTicket(guest) {
      if (!guest || !guest.id) return undefined;
      return this.getTickets.find(t => t.id === guest.id);
    },
    /**
     * Открыть PDF билета гостя в новой вкладке.
     * Использует тот же API-эндпоинт getTicketPdf, что и общий блок скачивания.
     */
    async downloadGuestTicket(guest) {
      const ticket = this.guestTicket(guest);
      if (!ticket) return;
      const win = window.open('about:blank', '_blank');
      const url = await this.getUrlForPdf(ticket.id);
      win.location = url;
    },
    /**
     * Загрузить типы билетов фестиваля (если ещё не загружены),
     * чтобы сопоставить guests[].ticket_type_id → name.
     */
    ensureTicketTypesLoaded(guests) {
      if (this.getTicketType && this.getTicketType.length > 0) return;
      if (!guests || guests.length === 0) return;
      const festivalId = guests[0].festival_id;
      if (!festivalId) return;
      this.getListPriceFor({festival_id: festivalId});
    },
    /**
     * Название типа билета по ticket_type_id гостя.
     * Запасной текст «Билет», если тип не найден (например заказ-список без типа).
     * @returns {string}
     */
    ticketTypeName(guest) {
      if (!guest.ticket_type_id) {
        return this.getLocationName ? this.getLocationName : 'Билет';
      }
      const found = (this.getTicketType || []).find(t => t.id === guest.ticket_type_id);
      return found ? found.name : 'Билет';
    },
    /**
     * Итог строки гостя — из price_snapshot.total (целые рубли).
     * @returns {number}
     */
    guestTotal(guest) {
      if (guest.price_snapshot && guest.price_snapshot.total != null) {
        return Number(guest.price_snapshot.total);
      }
      return 0;
    },
    hasPriceSnapshot(guest) {
      return guest.price_snapshot && guest.price_snapshot.base_price != null;
    },
    /**
     * Парковка определяется по формату value «номер / марка / водитель».
     */
    isParkingGuest(guest) {
      return typeof guest.value === 'string' && guest.value.split('/').length >= 3;
    },
    /**
     * Формат денег: целые рубли с разделителем тысяч.
     * @returns {string}
     */
    formatMoney(value) {
      const n = Number(value) || 0;
      return n.toLocaleString('ru-RU');
    },
    /**
     * Цвет бейджа статуса (по образцу OrderLists/OrderList.vue).
     * @returns {string}
     */
    statusColor(status) {
      switch (status) {
        case 'new':
        case 'new_for_live':
        case 'new_list':
          return '#6c757d';
        case 'paid':
        case 'paid_for_live':
        case 'live_ticket_issued':
        case 'approve_list':
          return '#1e871c';
        case 'cancel':
        case 'cancel_for_live':
        case 'cancel_list':
          return '#86201c';
        case 'difficulties_arose':
        case 'difficulties_arose_list':
          return '#d0ba27';
        default:
          return '#888888';
      }
    },
    back: function () {
      this.$router.back();
    },
  },
  created() {
    document.title = "Мой заказ"
  },
}
</script>

<style scoped>
.order-page {
  padding-bottom: 24px;
}

.section-title {
  margin: 16px 0 10px;
  font-weight: 600;
}

.order-status-badge {
  font-size: 14px;
  padding: 6px 12px;
  color: #fff;
}

.order-summary__total {
  font-size: 18px;
}

.order-meta dt {
  font-weight: 400;
}

.order-meta dd {
  margin-bottom: 4px;
}

.guest-card__name {
  font-size: 16px;
  font-weight: 600;
}

.guest-card__total {
  font-size: 16px;
  white-space: nowrap;
}

.type-badge {
  border: 1px solid #ddd;
  font-size: 13px;
}

.guest-options {
  list-style: none;
  padding-left: 0;
  font-size: 14px;
}

.guest-options li {
  padding: 2px 0;
}

.guest-price-breakdown span {
  margin-right: 6px;
}

.guest-price-breakdown__eq {
  font-weight: 600;
  color: #333;
}

.order-footer__total {
  font-size: 16px;
}

.guest-pdf-btn {
  font-size: 13px;
}

/* Мобильная адаптация: на узких экранах карточки гостя — в столбец */
@media (max-width: 575.98px) {
  .guest-card .d-flex {
    flex-direction: column;
  }
  .guest-card__total {
    text-align: left !important;
    margin-top: 6px;
  }
  .order-status-badge {
    font-size: 13px;
  }
}
</style>
