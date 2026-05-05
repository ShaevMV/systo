<template>
  <div class="row" id="filter-lists">
    <div class="col-lg-12 mx-auto mb-3">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Фильтр</h5>
          <div class="d-flex flex-wrap">
            <div class="col-md-4">
              <label class="form-label">Email</label>
              <input type="text" class="form-control" v-model="email">
            </div>
            <div class="col-md-4">
              <label class="form-label">Имя гостя</label>
              <input type="text" class="form-control" v-model="name">
            </div>
            <div class="col-md-4">
              <label class="form-label">Статус</label>
              <select class="form-select" v-model="status">
                <option value="">Любой</option>
                <option value="new_list">Список ожидает проверки</option>
                <option value="approve_list">Список одобрен</option>
                <option value="cancel_list">Список отменён</option>
                <option value="difficulties_arose_list">Возникли трудности</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Локация</label>
              <select class="form-select" v-model="locationId">
                <option :value="null">Все</option>
                <option v-for="loc in getLocationList" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Фестиваль</label>
              <select class="form-select" v-model="festival_id">
                <option v-for="f in getFestivalList" :key="f.id" :value="f.id">
                  {{ f.name }} {{ f.year }}
                </option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Проект</label>
              <input type="text" class="form-control" v-model="project" placeholder="Часть названия проекта">
            </div>
          </div>
          <div class="row b-row mt-2">
            <button class="btn btn-primary me-2" @click="sendFilter" :disabled="getIsLoading" type="button">
              <span v-if="getIsLoading">Загрузка...</span>
              <span v-else>Применить</span>
            </button>
            <button class="btn btn-secondary" @click="clearFilter" type="button">Сбросить</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

export default {
  name: 'FilterListOrder',
  props: {
    /**
     * Какой action диспатчить: 'getOrderListsList' (admin/manager) или 'getOrderListForCurator' (curator)
     */
    actionName: { type: String, default: 'getOrderListsList' },
  },
  data() {
    return {
      email: null,
      name: null,
      status: '',
      locationId: null,
      project: null,
      selectFestivalId: null,
    };
  },
  computed: {
    ...mapGetters('appFestivalTickets', ['getFestivalList']),
    ...mapGetters('appOrder', ['getIsLoading']),
    ...mapGetters('appLocation', { getLocationList: 'getList' }),
    festival_id: {
      get() {
        return this.selectFestivalId ?? '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';
      },
      set(v) { this.selectFestivalId = v; },
    },
  },
  methods: {
    ...mapActions('appFestivalTickets', ['getListFestival']),
    ...mapActions('appOrder', ['getOrderListsList', 'getOrderListForCurator', 'loading']),
    ...mapActions('appLocation', { loadLocations: 'loadList' }),
    dispatchFilter(filter) {
      if (this.actionName === 'getOrderListForCurator') {
        return this.getOrderListForCurator(filter);
      }
      return this.getOrderListsList(filter);
    },
    sendFilter() {
      this.loading();
      this.dispatchFilter({
        festivalId: this.festival_id,
        email: this.email,
        name: this.name,
        status: this.status,
        locationId: this.locationId,
        project: this.project,
      });
    },
    clearFilter() {
      this.email = null;
      this.name = null;
      this.status = '';
      this.locationId = null;
      this.project = null;
      this.dispatchFilter({ festivalId: this.festival_id });
    },
  },
  async created() {
    await this.getListFestival();
    await this.loadLocations({
      filter: { festival_id: this.festival_id, active: '1' },
      orderBy: { name: 'asc' },
    });
  },
};
</script>

<style scoped>
</style>
