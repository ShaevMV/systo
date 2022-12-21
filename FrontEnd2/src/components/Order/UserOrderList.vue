<template>
  <table class="table">
    <thead>
    <tr>
      <th scope="col">#</th>
      <th scope="col">Название</th>
      <th scope="col">Стоимость</th>
      <th scope="col">Кол-во билетов</th>
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
      <td>{{ itemOrder.name }}</td>
      <td>{{ itemOrder.price }}</td>
      <td>{{ itemOrder.count }}</td>
      <td>{{ itemOrder.dateCreate }}</td>
      <td>{{ itemOrder.humanStatus }}</td>
      <td>{{ itemOrder.lastComment }}</td>
    </tr>
    </tbody>
  </table>
</template>

<script>
import {mapGetters} from 'vuex';

export default {
  name: "UserOrderList",
  computed: {
    ...mapGetters('appOrder', [
      'getOrderList'
    ]),
  },
}
</script>

<style scoped>

</style>
