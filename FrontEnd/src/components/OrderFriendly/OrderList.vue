<template>
  <div class="title-block text-center">
    <h1 class="card-title"> Заказы "пушеров" </h1>
  </div>
  <div class="row">
    <div class="col-lg-12 mx-auto" id="filter-results">
      <div class="card">
        <div class="card-body">
          <p>На этой странице ты можешь просмотреть все свои заказы на регистрацию оргвзносов.<br>
            Чтобы увидеть подробную информацию и сами электронные билеты с qr-кодом кликни на строчку с заказом</p>
          <table class="table table-hover">
            <thead>
            <tr>
              <th scope="col" class="mobile">№ заказа</th>
              <th scope="col" class="mobile"></th>
              <th scope="col">Продовец</th>
              <th scope="col">Email</th>
              <th scope="col">Гости</th>
              <th scope="col">Тип оргвзноса</th>
              <th scope="col">Стоимость</th>
              <th scope="col">Кол-во</th>
              <th scope="col" class="mobile">Телефон</th>
              <th scope="col" class="mobile"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(itemOrder,index) in getOrderList"
                v-bind:key="index"
                @click="goItemOrderForUser(itemOrder.id, itemOrder.status)">
              <th scope="row" class="mobile">
                {{ itemOrder.kilter }}
              </th>
              <td class="mobile">
                <div class="btn-group" v-show="Object.keys(itemOrder.listCorrectNextStatus).length > 0">
                  <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown"
                          aria-haspopup="true"
                          :style="{'background-color': activeColor(itemOrder.status)}"
                          aria-expanded="false">
                    ...
                  </button>
                  <div class="dropdown-menu">
                  <span class="dropdown-item btn-link"
                        role="button"
                        v-for="(statusItem, key) in itemOrder.listCorrectNextStatus" v-bind:key="key"
                        @click="chanceStatus(key,itemOrder)">{{ statusItem }}</span>
                  </div>
                </div>
              </td>
              <td>{{ itemOrder.pusher_name }} <br/> {{ itemOrder.pusher_email }}</td>
              <td>{{ itemOrder.email }}</td>
              <td :title="getListQuests(itemOrder.guests, true) ">
                {{ getListQuests(itemOrder.guests, false) }}
              </td>
              <td>{{ itemOrder.name }}</td>
              <td>{{ itemOrder.price }} рублей</td>
              <td>{{ itemOrder.count }}</td>
              <td :style="styleObject(itemOrder.status)" class="mobile" style="text-align: left;">
                {{ itemOrder.phone }}
              </td>
              <td class="mobile" style="text-align: left;">
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

    <button type="button" class="btn btn-primary" v-show="false" data-toggle="modal" id="modalOpenBtnLive"
            data-target="#exampleModalLive">
      Launch demo modal
    </button>


    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
         aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Комментарий для пользователя</h5>
            <button type="button" class="close"
                    data-dismiss="modal"
                    id="closeModal"
                    aria-label="Close">
              <span aria-hidden="true">
                Х
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
                    class="btn btn-secondary">Сменить статус
            </button>
          </div>
        </div>
      </div>
    </div>


    <div class="modal fade" id="exampleModalLive" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabelLive"
         aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Введите номера билетов для каждого гостя</h5>
            <button type="button" class="close"
                    data-dismiss="modal"
                    id="closeModalLive"
                    aria-label="Close">
              <span aria-hidden="true">×</span>
            </button>
          </div>
          <div class="modal-body">
            <table class="table">
              <thead>
              <tr>
                <th>Имя</th>
                <th>Номер билета</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="guest in getListGuest" :key="guest.id">
                <td>{{ guest.value }}</td>
                <td>
                  <input type="text"
                         v-model="liveNumber[guest.id]"
                         class="form-control"
                         placeholder="Введите номер билета">
                </td>
              </tr>
              </tbody>
            </table>
            <small class="form-text text-muted">{{ getError('liveNumber') }}</small>
          </div>
          <div class="modal-footer">
            <button type="button" @click="sendLive" class="btn btn-secondary">
              Сменить статус
            </button>
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
  data() {
    return {
      comment: null,
      selectId: null,
      selectStatus: null,
      selectItem: {},
      liveNumber: {},
    }
  },
  computed: {
    ...mapGetters('appOrder', [
      'getOrderList',
      'getError'
    ]),
    ...mapGetters('appUser', [
      'isAdmin'
    ]),
    getListGuest: function () {
      return this.selectItem?.guests ?? [];
    },

  },
  // #1e871c - зеленый, #86201c - красный, #d0ba27 - желтый
  methods: {
    ...mapActions('appOrder', [
      'sendToChanceStatus'
    ]),
    styleObject: function (status) {
      return {
        color: this.activeColor(status),
      }
    },
    goItemOrderForUser(idOrderItem, status) {
      if (!this.isAdmin) {
        if (status !== 'cancel') {
          this.$router.push({name: 'orderItems', params: {id: idOrderItem}});
        }
      }
    },
    activeColor: function (status) {
      let color = '#86201c';

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
        case 'live_ticket_issued':
        case 'difficulties_arose':
          color = '#d0ba27';
          break;
        default:
          color = 'red';
      }

      return color
    },
    getListQuests: function (quests, isAll = true) {
      let result = '';
      let max = isAll ? quests.length : 3;
      quests.forEach(function (item, i) {
        if (i < max) {
          result = result + item.value + '(' + item.number + ')' + " | ";
        }
      });

      return result;
    },
    cuttedText: function (text) {
      if (text !== null && text.length > 25) {
        return text.slice(0, 25) + "...";
      }
      return text
    },
    /**
     * Сменить статус
     * @param status
     * @param id
     */
    chanceStatus(status, itemOrder) {
      this.selectId = itemOrder.id;
      this.selectStatus = status;

      if (['difficulties_arose'].includes(status)) {
        document.getElementById('modalOpenBtn').click();
      } else if (['live_ticket_issued'].includes(status)) {
        this.selectItem = itemOrder;

        // Инициализируем объект liveNumber для всех гостей
        const guests = itemOrder.guests || [];
        this.liveNumber = {};
        guests.forEach(guest => {
          this.liveNumber[guest.id] = '';
        });

        // Ждем, пока Vue обновит DOM, и только потом открываем модалку
        this.$nextTick(() => {
          document.getElementById('modalOpenBtnLive').click();
        });
      } else {
        this.sendToChanceStatus({
          'id': itemOrder.id,
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
        'status': this.selectStatus,
        'comment': this.comment,
        'callback': function () {
          document.getElementById('closeModal').click();
          self.selectId = null;
          self.selectStatus = null;
          self.comment = null;
        }
      });
    },
    /**
     * Сменить статус на возникли трудности и отправить сообщение для пользователя
     */
    sendLive() {
      let self = this;
      this.sendToChanceStatus({
        'id': this.selectId,
        'status': this.selectStatus,
        'liveList': this.liveNumber,
        'callback': function () {
          document.getElementById('closeModalLive').click();
          self.selectId = null;
          self.selectStatus = null;
          self.comment = null;
        }
      });
    }

  }
}
</script>

<style scoped>

</style>