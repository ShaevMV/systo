<template>
  <div class="card mb-4">
    <div class="card-header pb-0">
      <h6>Мои заказы "{{ getName }}" от {{ getDateBuy }}</h6>
    </div>
    <div class="card-body px-0 pt-0 pb-2">
      <div class="table-responsive p-0">
        <div v-if="!getError('error')">
          <table class="table align-items-center justify-content-center mb-0" >
            <thead>
            <tr>
              <th
                  class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7"
              >Название</th>
              <th
                  class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"
              >Гости</th>
              <th
                  class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"
              >Тип оплаты</th>
              <th
                  class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"
              >Дата оплаты</th>
              <th
                  class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"
              >Скидка</th>
              <th
                  class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"
              >Стоимость</th>
              <th
                  class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"
              >Статус</th>
            </tr>
            </thead>
            <tbody>
            <tr>
              <td>
                <span class="text-xs font-weight-bold">{{ getName }}</span></td>
              <td>
                <p class="text-sm font-weight-bold mb-0">
                {{ getGuests }}
                </p>
              </td>
              <td class="align-middle text-center">{{ getTypeOfPayment }}</td>
              <td class="align-middle">{{ getDateBuy }}</td>
              <td class="text-right">{{ getDiscount }}</td>
              <td class="text-right">{{ getTotalPrice }}</td>
              <td class="align-middle">{{ getHumanStatus }}</td>
            </tr>
            </tbody>
          </table>
          <div class="card-header pb-0">
            <h6>Комментарии к заказу</h6>
          </div>
          <div class="col-lg-12">
            <order-comment :id="id"/>
          </div>
        </div>
        <div v-else>
          <h1> {{ getError('error') }} </h1>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import {mapGetters} from "vuex";
import OrderComment from "@/components/Order/OrderComment";

export default {
  name: "OrderItem",
  components: {OrderComment},
  computed: {
    ...mapGetters('appOrder', [
      'getOrderItem',
      'getError'
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

