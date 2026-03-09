<template>
  <div class="row" id="filter">
    <div class="col-lg-12 mx-auto mb-5">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Фильтр</h5>

          <div class="d-flex flex-wrap">
            <div class="col-md-4">
              <label for="validationDefault01" class="form-label">Тип оргвзноса</label>
              <select class="form-select"
                      v-model="typeOrder"
                      id="validationDefault01">
                <option value=null>Выберите тип оргвзноса</option>
                <option v-for="(typeTickets) in getTicketType"
                        v-bind:key="typeTickets.price"
                        v-bind:value="typeTickets">{{ typeTickets.name }} /
                  {{ typeTickets.price }} руб.
                </option>
              </select>
            </div>

            <div class="col-md-4">
              <label for="validationDefaultUsername" class="form-label">Email</label>
              <div class="input-group">
                <span class="input-group-text" id="inputGroupPrepend2">@</span>
                <input type="text"
                       v-model="email"
                       class="form-control"
                       id="validationDefaultUsername"
                       aria-describedby="inputGroupPrepend2">
              </div>
            </div>
            <div class="col-md-4">
              <label for="validationDefaultUsername" class="form-label">Имя</label>
              <div class="input-group">
                <span class="input-group-text" id="inputGroupPrepend2">@</span>
                <input type="text"
                       v-model="name"
                       class="form-control"
                       id="validationDefaultUsername"
                       aria-describedby="inputGroupPrepend2">
              </div>
            </div>
            <div class="col-md-4">
              <label for="validationDefault01" class="form-label">Статус заказа</label>
              <select class="form-select"
                      v-model="status"
                      id="validationDefault01">
                <option value="">Выберите статус заказа</option>
                <option value="new">Новый</option>
                <option value="paid">Оплаченный</option>
                <option value="cancel">Отменёный</option>
                <option value="difficulties_arose">Возникли трудности</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="validationDefault04" class="form-label">Город</label>
              <input type="text"
                     v-model="city"
                     class="form-control"
                     id="validationDefault04">
            </div>
            <div class="col-md-4">
              <label for="validationDefault05" class="form-label">Выберите фестиваль</label>
              <select class="form-select"
                      v-model="festival_id"
                      id="validationDefault05">
                <option v-for="(festivalItem) in getFestivalList"
                        v-bind:key="festivalItem.id"
                        :selected="festivalItem.id == festival_id"
                        v-bind:value="festivalItem.id">{{ festivalItem.name }} {{ festivalItem.year }}
                </option>
              </select>
            </div>
            <div class="col-md-4" v-show="isAdmin">
              <label for="validationDefault06" class="form-label">Продовец</label>
              <select class="form-select"
                      v-model="friendlyId"
                      id="validationDefault06">
                <option :value="null">Выберите пользователя</option>
                <option v-for="pusher in getPusherList"
                        :key="pusher.id"
                        :value="pusher.id">{{ pusher.name }} ({{ pusher.email }})
                </option>
              </select>
            </div>
          </div>

          <div class="row b-row mt-2">
            <button class="btn btn-primary"
                    @click="sendFilter" :disabled="getIsLoading"
                    type="submit"><span v-if="getIsLoading">Загрузка...</span>
              <span v-else>Отправить</span>
            </button>
            <button class="btn btn-primary"
                    @click="clearFilter"
                    type="submit">Сбросить фильтр
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
  name: "FilterOrder",
  data() {
    return {
      name: null,
      email: null,
      typeOrder: null,
      status: '',
      city: null,
      selectFestivalId: null,
      questionnaire: '',
      friendlyId: null,
    }
  },
  computed: {
    ...mapGetters('appFestivalTickets', [
      'getFestivalList',
      'getTicketType',
    ]),
    ...mapGetters('appOrder', [
      'getIsLoading'
    ]),
    ...mapGetters('appUser', [
      'isAdmin'
    ]),
    ...mapGetters('appAccount', ['getList']),
    getPusherList: function () {
      return this.getList.filter(item => item.role === 'pusher');
    },
    festival_id: {
      get: function () {
        if (this.selectFestivalId === null) {
          return '9d679bcf-b438-4ddb-ac04-023fa9bff4b8'
        }
        return this.selectFestivalId;
      },
      set: function (newValue) {
        this.selectFestivalId = newValue;
      },
    }
  },
  methods: {
    ...mapActions('appFestivalTickets', [
      'getListFestival',
    ]),
    ...mapActions('appOrder', [
      'getOrderListForFrendly',
      'loading'
    ]),
    ...mapActions('appAccount', ['loadList']),
    /**
     * Отправить данные для фильтра
     */
    sendFilter: function () {
        this.loading();
        let typePrice = this.typeOrder !== null ? this.typeOrder.id : null;
        let self = this;
        this.getOrderListForFrendly({
          'name': self.email,
          'typePrice': typePrice,
          'email': self.email,
          'status': self.status,
          'festivalId': self.festival_id,
          'city': self.city,
          'friendlyId': self.friendlyId,
        });
      },
    clearFilter: function () {
      this.name = null;
      this.email = null;
      this.status = '';
      this.typeOrder = null;
      this.city = null;
      this.friendlyId = null;
      let festivalId = this.festival_id;
      this.getOrderListForFrendly({
        'festivalId': festivalId,
      });
    }
  },
  async created() {
    await this.getListFestival();
    await this.loadList({
      filter: {
        'role': 'pusher'
      },
      orderBy: {},
    });
  },
}
</script>

<style scoped>

</style>
