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
          <th scope="col"></th>
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
          <td>
            <div class="btn-group">
              <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
                      aria-expanded="false">
                ...
              </button>
              <div class="dropdown-menu" v-if="isAdmin">
                <span class="dropdown-item btn-link"
                      role="button"
                      v-for="(statusItem, key) in itemOrder.listCorrectNextStatus" v-bind:key="key"
                      @click="chanceStatus(key,itemOrder.id)">{{ statusItem }}</span>
              </div>
              <div class="dropdown-menu" v-else>
                <span class="dropdown-item btn-link"
                      :class="{ disabled : itemOrder.status !== 'paid'}"
                      :aria-disabled=" itemOrder.status !== 'paid' "
                      role="button"
                      @click="downloadTicket(itemOrder.id)">Скачать PDF</span>
              </div>
            </div>
          </td>
        </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import {mapActions, mapGetters} from 'vuex';

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
  methods: {
    ...mapActions('appOrder', [
      'sendToChanceStatus',
      'getUrlForPdf'
    ]),
    /**
     * Сменить статус
     * @param status
     * @param id
     */
    chanceStatus(status, id) {
      this.sendToChanceStatus({
        'id': id,
        'status': status
      });
    },
    /**
     * Скачать билеты
     * @param id
     */
    downloadTicket(id) {
      this.getUrlForPdf(id);
    }
  }
}
</script>

<style scoped>

</style>
