<template>
  <div class="row" id="filter">
    <div class="col-lg-12 mx-auto mb-5">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Фильтр</h5>

          <div class="d-flex flex-wrap">
            <div class="col-md-4">
              <label class="form-label">Email участника</label>
              <div class="input-group">
                <span class="input-group-text">@</span>
                <input type="text"
                       v-model="email"
                       class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Статус заказа</label>
              <select class="form-select" v-model="status">
                <option value="">Все статусы</option>
                <option value="new_for_list">Новый</option>
                <option value="pending_curator">На модерации</option>
                <option value="difficulties_arose_curator">Трудности куратора</option>
                <option value="paid">Оплачен</option>
                <option value="cancel">Отменён</option>
              </select>
            </div>
            <div class="col-md-4" v-if="isAdmin">
              <label class="form-label">Куратор</label>
              <select class="form-select" v-model="curatorId">
                <option :value="null">Все кураторы</option>
                <option v-for="curator in getCuratorList"
                        :key="curator.id"
                        :value="curator.id">{{ curator.name || curator.email }} ({{ curator.email }})
                </option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Фестиваль</label>
              <select class="form-select" v-model="festival_id">
                <option v-for="festivalItem in getFestivalList"
                        :key="festivalItem.id"
                        :value="festivalItem.id">{{ festivalItem.name }} {{ festivalItem.year }}
                </option>
              </select>
            </div>
          </div>

          <div class="row b-row mt-2">
            <button class="btn btn-primary"
                    @click="sendFilter"
                    :disabled="getIsLoading"
                    type="button">
              <span v-if="getIsLoading">Загрузка...</span>
              <span v-else>Применить</span>
            </button>
            <button class="btn btn-primary"
                    @click="clearFilter"
                    type="button">Сбросить фильтр</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {mapActions, mapGetters} from 'vuex';

export default {
  name: "FilterOrderCurator",
  data() {
    return {
      email: null,
      status: '',
      curatorId: null,
      selectFestivalId: null,
    };
  },
  computed: {
    ...mapGetters('appFestivalTickets', ['getFestivalList']),
    ...mapGetters('appOrder', ['getIsLoading']),
    ...mapGetters('appUser', ['isAdmin']),
    ...mapGetters('appAccount', ['getList']),
    getCuratorList() {
      return this.getList.filter(item =>
        item.role === 'curator' || item.role === 'curator_pusher'
      );
    },
    festival_id: {
      get() {
        return this.selectFestivalId ?? '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';
      },
      set(val) {
        this.selectFestivalId = val;
      },
    },
  },
  methods: {
    ...mapActions('appFestivalTickets', ['getListFestival']),
    ...mapActions('appOrder', ['getOrderListForCurator', 'loading']),
    ...mapActions('appAccount', ['loadList']),
    sendFilter() {
      this.loading();
      this.getOrderListForCurator({
        email: this.email,
        status: this.status,
        festivalId: this.festival_id,
        curatorId: this.curatorId,
      });
    },
    clearFilter() {
      this.email = null;
      this.status = '';
      this.curatorId = null;
      this.getOrderListForCurator({ festivalId: this.festival_id });
    },
  },
  async created() {
    await this.getListFestival();
    if (this.isAdmin) {
      await this.loadList({
        filter: { role: 'curator' },
        orderBy: {},
      });
    }
    this.getOrderListForCurator({ festivalId: this.festival_id });
  },
};
</script>
