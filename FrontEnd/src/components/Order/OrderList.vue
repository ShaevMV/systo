<template>
  <div class="title-block text-center">
    <h1 class="card-title" v-if="!isAdmin">Мои Оргвзносы</h1>
    <h1 class="card-title" v-else> Заказы пользователя </h1>
  </div>
  <div class="row">
    <div class="col-lg-12 mx-auto">
      <div class="card">
        <div class="card-body">
          <p>На этой странице ты можешь просмотреть все свои заказы на регистрацию оргвзносов.<br>
            Чтобы увидеть подробную информацию и сами электронные билеты с qr-кодом кликни на строчку с заказом</p>
          <table class="table table-hover">
            <thead>
            <tr>
              <th scope="col">№ заказа</th>
              <th scope="col" v-if="isAdmin">Email</th>
              <th scope="col">Тип оргвзноса</th>
              <th scope="col">Стоимость</th>
              <th scope="col">Кол-во</th>
              <th scope="col">Дата <span>внесения средств</span></th>
              <th scope="col" v-if="isAdmin">Промо код</th>
              <th scope="col">Метод <span>перевода</span></th>
              <th scope="col" v-if="isAdmin">Информация о платеже</th>
              <th scope="col">Статус</th>
              <th scope="col" v-if="isAdmin">Комментарий</th>
              <th scope="col"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(itemOrder,index) in getOrderList"
                v-bind:key="index"
                @click="goItemOrder(itemOrder.id)">
              <th scope="row">
                {{ itemOrder.kilter }}
              </th>
              <td v-if="isAdmin">{{ itemOrder.email }}</td>
              <td>{{ itemOrder.name }}</td>
              <td>{{ itemOrder.price }} рублей</td>
              <td>{{ itemOrder.count }}</td>
              <td v-if="isAdmin">{{ itemOrder.promoCode }}</td>
              <td>{{ itemOrder.dateBuy }}</td>
              <td>{{ itemOrder.typeOfPaymentName }}</td>
              <td v-if="isAdmin">{{ itemOrder.idBuy }}</td>
              <td :style="styleObject(itemOrder.status)">{{ itemOrder.humanStatus }}</td>
              <td v-if="isAdmin">{{ itemOrder.lastComment }}</td>
              <td>
                <div class="btn-group" v-show="isAdmin && Object.keys(itemOrder.listCorrectNextStatus).length > 0">
                  <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown"
                          aria-haspopup="true"
                          aria-expanded="false">
                    ...
                  </button>
                  <div class="dropdown-menu">
                <span class="dropdown-item btn-link"
                      role="button"
                      v-for="(statusItem, key) in itemOrder.listCorrectNextStatus" v-bind:key="key"
                      @click="chanceStatus(key,itemOrder.id)">{{ statusItem }}</span>
                  </div>
                </div>
              </td>
            </tr>
            </tbody>
          </table>
        </div>
      </div>
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
      'sendToChanceStatus'
    ]),
    styleObject: function (status) {
      let color = 'black';
      switch (status) {
        case 'new':
          color = '#333333';
          break;
        case 'paid':
          color = '#1e871c';
          break;
        case 'cancel':
          color = '#86201c';
          break;
        case 'difficulties_arose':
          color = '#d0ba27';
          break;
        default:
          color = 'black';
      }

      return {
        color: color,
      }
    },
    goItemOrder(idOrderItem) {
      if (!this.isAdmin) {
        this.$router.push({name: 'orderItems', params: {id: idOrderItem}});
      }
    },
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

  }
}
</script>

<style scoped>

</style>
