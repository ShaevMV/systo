<template>
  <div class="card">
    <div class="card-body">
        <h5 class="card-title">Заказ от {{ getDateBuy }}</h5>
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
    </div>
  </div>
</template>

<script>
import {mapGetters} from "vuex";

export default {
  name: "OrderItem",
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
    getDateBuy: function () {
      return this.getOrderItem.dateBuy;
    }
  },
}
</script>

