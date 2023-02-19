<template>
  <div class="title-block text-center">
    <h1 class="card-title" v-if="!isAdmin">Мои Оргвзносы</h1>
    <h1 class="card-title" v-else> Заказы пользователей </h1>
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
              <th scope="col" class="mobile">№ заказа</th>
              <th scope="col" v-if="isAdmin"></th>
              <th scope="col" v-if="isAdmin">Email</th>
              <th scope="col">Тип оргвзноса</th>
              <th scope="col">Стоимость</th>
              <th scope="col">Кол-во</th>
              <th scope="col">Дата <span>внесения средств</span></th>
              <th scope="col" v-if="isAdmin">Промо код</th>
              <th scope="col">Метод <span>перевода</span></th>
              <th scope="col" v-if="isAdmin">Информация о платеже</th>
              <th scope="col" class="mobile">Статус</th>
              <th scope="col" v-if="isAdmin">Комментарий</th>
              <th scope="col" v-if="isAdmin"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(itemOrder,index) in getOrderList"
                v-bind:key="index"
                @click="goItemOrderForUser(itemOrder.id, itemOrder.status)">
              <th scope="row" class="mobile">
                {{ itemOrder.kilter }}
              </th>
              <td v-if="isAdmin">
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
              <td v-if="isAdmin">{{ itemOrder.email }}</td>
              <td>{{ itemOrder.name }}</td>
              <td>{{ itemOrder.price }} рублей</td>
              <td>{{ itemOrder.count }}</td>
              <td>{{ itemOrder.dateBuy }}</td>
              <td v-if="isAdmin">{{ itemOrder.promoCode }}</td>

              <td>{{ itemOrder.typeOfPaymentName }}</td>
              <td v-if="isAdmin">{{ itemOrder.idBuy }}</td>
              <td :style="styleObject(itemOrder.status)" class="mobile">{{ itemOrder.humanStatus }}</td>
              <td v-if="isAdmin">{{ itemOrder.lastComment }}</td>
              <td v-if="isAdmin">
                <router-link
                    :to="{
                    name: 'orderItems',
                    params: {id: itemOrder.id}
                }">
                  Перейти к билету
                </router-link>
                </td>
            </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <button type="button" class="btn btn-primary" v-show="false" data-toggle="modal" id="modalOpenBtn"
            data-target="#exampleModal">
      Launch demo modal
    </button>


    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
         aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Сообщения для пользователя</h5>
            <button type="button" class="close"
                    data-dismiss="modal"
                    id="closeModal"
                    aria-label="Close">
              <span aria-hidden="true">
              </span>
            </button>
          </div>
          <div class="modal-body">
            <textarea class="form-control" v-model="comment"></textarea>
            <small class="form-text text-muted"> {{ getError('comment') }}</small>
          </div>
          <div class="modal-footer">
            <button type="button"
                    @click="sendDifficultiesArose"
                    class="btn btn-secondary">Сменить статус на возникли трудности</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {mapActions, mapGetters} from 'vuex';

export default {
  name: "OrderList",
  data(){
    return {
      comment: null,
      selectId: null,
    }
  },
  props: {
    isAdmin: {
      type: Boolean,
      default: false,
    }
  },
  computed: {
    ...mapGetters('appOrder', [
      'getOrderList',
      'getError'
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
    goItemOrderForUser(idOrderItem, status) {
      if (!this.isAdmin) {
        if(status !== 'cancel') {
          this.$router.push({name: 'orderItems', params: {id: idOrderItem}});
        }
      }
    },
    /**
     * Сменить статус
     * @param status
     * @param id
     */
    chanceStatus(status, id) {
      if(status === 'difficulties_arose') {
        this.selectId = id;
        document.getElementById('modalOpenBtn').click();
      } else {
        this.sendToChanceStatus({
          'id': id,
          'status': status,
          'comment': null
        });
      }
    },
    /**
     * Сменить статус на возникли трудности и отправить сообщение для пользователя
     */
    sendDifficultiesArose() {
      let self = this;
      this.sendToChanceStatus({
        'id': this.selectId,
        'status': 'difficulties_arose',
        'comment': this.comment,
        'callback': function () {
          document.getElementById('closeModal').click();
          self.selectId = null;
          self.comment = null;
        }
      });


    }
  }
}
</script>

<style scoped>

</style>
