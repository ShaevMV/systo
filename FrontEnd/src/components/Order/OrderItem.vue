<template>
    <div class="container-fluid">
      <div class="title-block text-center"><h1 class="card-title">Заказ # {{ getDateKilter }}</h1></div>
      <div class="row">
        <div class="col-lg-12 mx-auto">
          <div class="card">
            <div class="card-body">
              <table class="table table-hover">
                <thead>
                <tr>
                  <th scope="col">Название</th>
                  <th scope="col">Гости</th>
                  <th scope="col" v-if="!getFriendlyId">Тип оплаты</th>
                  <th scope="col" v-if="!getFriendlyId">Дата оплаты</th>
                  <th scope="col" v-if="!getFriendlyId">Скидка</th>
                  <th scope="col">Стоимость{{ (isAdmin && getFriendlyId) ? ' (ред.)' : '' }}</th>
                  <th scope="col">Статус</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                  <td>{{ getName }}</td>
                  <td>
                    <div
                      v-if="!(isAdmin || isPusher)"
                      v-html="getGuests"
                    >
                    </div>
                    <new-ticket
                        v-if="(isAdmin || isPusher)"
                        :oldGuests="getOrderItem.guests"
                    />
                  </td>
                  <td v-if="!getFriendlyId">{{ getTypeOfPayment }}</td>
                  <td v-if="!getFriendlyId">{{ getDateBuy }}</td>
                  <td class="text-right" v-if="!getFriendlyId">{{ getDiscount }}</td>
                  <td class="text-right" v-if="getFriendlyId">
                    <span v-if="!isAdmin">{{ getTotalPrice }}</span>
                    <correct-price
                        v-if="isAdmin"
                        :id="getId"
                        :oldPrice="getTotalPrice"/>
                  </td>
                  <td class="text-right" v-if="!getFriendlyId">{{ getTotalPrice }}</td>


                  <td>{{ getHumanStatus }}</td>
                </tr>
                </tbody>
              </table>

                <order-button
                    :id="getId"
                    :list-tickets="this.getOrderItem.tickets"
                    :status="getStatus"/>

              <button type="button"
                      @click="back"
                      class="btn btn-primary x-button">Назад в МОИ ОРГВЗНОСЫ</button>

              <order-history
                  v-if="isAdmin"
                  :order-id="getId"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
</template>

<script>
import {mapGetters} from "vuex";
import OrderButton from "@/components/Order/OrderButton.vue";
import NewTicket from "@/components/Order/NewTicket.vue";
import OrderHistory from "@/components/Order/OrderHistory.vue";
import CorrectPrice from "@/components/OrderFriendly/CorrectPrice.vue";

export default {
  name: "OrderItem",
  components: {NewTicket, OrderButton, OrderHistory, CorrectPrice},
  computed: {
    ...mapGetters('appOrder', [
      'getOrderItem',
    ]),
    ...mapGetters('appUser', [
      'isAdmin',
      'isPusher',
    ]),
    /**
     * Вывести названия билета
     *
     * @returns {string|null}
     */
    getName: function () {
      return this.getOrderItem.name;
    },
    /**
     * Вывести гоастей
     *
     * @returns {string}
     */
    getGuests: function () {
      let result = '';
      let sign = '';
      this.getOrderItem.guests.forEach(function (elm) {
        result = result + sign + elm.value + ' ' + (elm.email ?? '');
        sign = '<br/> '
      })

      return result;
    },
    /**
     * Вывести стоимость
     *
     * @returns {0|number}
     */
    getTotalPrice: function () {
      return this.getOrderItem.totalPrice;
    },
    /**
     * Вывести скидку
     *
     * @returns {'-'|number}
     */
    getDiscount: function () {
      return this.getOrderItem.discount || '-';
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
    getDateKilter: function () {
      return this.getOrderItem.kilter;
    },
    getId: function () {
      return this.getOrderItem.id;
    },
    getFriendlyId: function () {
      return this.getOrderItem.friendly_id;
    }
  },
  methods: {
    back: function () {
      this.$router.back();
    }
  },
  created() {
    document.title = "Мой заказ"
  },
}
</script>

