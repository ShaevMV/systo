<template>
    <div class="container-fluid">
      <div class="title-block text-center"><h1 class="card-title">Заказ # {{ getDateBuy }}</h1></div>
      <div class="row">
        <div class="col-lg-12 mx-auto">
          <div class="card">
            <div class="card-body">
              <table class="table table-hover">
                <thead>
                <tr>
                  <th scope="col">Название</th>
                  <th scope="col">Гости</th>
                  <th scope="col">Тип оплаты</th>
                  <th scope="col">Дата оплаты</th>
                  <th scope="col">Скидка</th>
                  <th scope="col">Стоимость</th>
                  <th scope="col">Статус</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                  <td>{{ getName }}</td>
                  <td>{{ getGuests }}</td>
                  <td>{{ getTypeOfPayment }}</td>
                  <td>{{ getDateBuy }}</td>
                  <td class="text-right">{{ getDiscount }}</td>
                  <td class="text-right">{{ getTotalPrice }}</td>
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
            </div>
          </div>
        </div>
      </div>
    </div>
</template>

<script>
import {mapGetters} from "vuex";
import OrderButton from "@/components/Order/OrderButton.vue";

export default {
  name: "OrderItem",
  components: {OrderButton},
  computed: {
    ...mapGetters('appOrder', [
      'getOrderItem',
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
        result = result + sign + elm.value;
        sign = ', '
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
      return this.getOrderItem.kilter;
    },
    getId: function () {
      return this.getOrderItem.id;
    }
  },
  methods: {
    back: function () {
      location.href='/myOrders';
    }
  }
}
</script>

