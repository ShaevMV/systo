<template>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title" v-if="!isAdmin">Мои заказы</h5>
      <h5 class="card-title" v-else> Заказы пользователя </h5>
      <table class="table table-hover">
        <thead>
        <tr>
          <th scope="col">#</th>
          <th scope="col" v-if="isAdmin">Email</th>
          <th scope="col">Название</th>
          <th scope="col">Стоимость</th>
          <th scope="col">Кол-во билетов</th>
          <th scope="col">Промо код</th>
          <th scope="col" v-if="isAdmin">Способ покупки билета</th>
          <th scope="col">Дата покупики билета</th>
          <th scope="col">Статус</th>
          <th scope="col">Комментарий</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(itemOrder,index) in getOrderList" v-bind:key="index">
          <th scope="row">
            <router-link
                class="nav-link"
                active-class="active"
                :to="{ name: 'orderItems', params: { id: itemOrder.id }}">{{ index + 1 }}
            </router-link>
          </th>
          <td v-if="isAdmin">{{ itemOrder.email }}</td>
          <td>{{ itemOrder.name }}</td>
          <td>{{ itemOrder.price }}</td>
          <td>{{ itemOrder.count }}</td>
          <td>{{ itemOrder.promoCode }}</td>
          <td v-if="isAdmin">{{ itemOrder.typeOfPaymentName }}</td>
          <td>{{ itemOrder.dateBuy }}</td>
          <td>{{ itemOrder.humanStatus }}</td>
          <td>{{ itemOrder.lastComment }}</td>
        </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import {mapGetters} from 'vuex';

export default {
  name: "OrderList",
  props: {
    isAdmin: {
      type: Boolean,
      default: false,
    }
  },
  computed: {
    ...mapGetters('appOrder', [
      'getOrderList'
    ]),
  },
}
</script>

<style scoped>

</style>
