<template>
  <div class="row" id="filter">
    <div class="col-lg-12 mx-auto mb-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Фильтр</h5>

          <div class="d-flex flex-wrap gap-3">
            <div class="col-md-3">
              <label class="form-label">Название</label>
              <input type="text"
                     v-model="name"
                     class="form-control"
                     placeholder="Поиск по названию">
            </div>
            <div class="col-md-3">
              <label class="form-label">Фестиваль</label>
              <select class="form-select" v-model="festival_id">
                <option :value="null">Все фестивали</option>
                <option v-for="festival in getFestivalList"
                        :key="festival.id"
                        :value="festival.id">
                  {{ festival.name }} {{ festival.year }}
                </option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Активность</label>
              <select class="form-select" v-model="active">
                <option :value="null">Все</option>
                <option value="true">Активные</option>
                <option value="false">Неактивные</option>
              </select>
            </div>
          </div>

          <div class="row b-row mt-2">
            <button class="btn btn-primary"
                    @click="sendFilter"
                    type="button">
              Применить
            </button>
            <button class="btn btn-primary"
                    @click="clearFilter"
                    type="button">
              Сбросить
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
  name: "FilterLocation",
  data() {
    return {
      name: null,
      festival_id: null,
      active: null,
    };
  },
  computed: {
    ...mapGetters('appFestivalTickets', ['getFestivalList']),
    ...mapGetters('appLocation', ['getOrderBy']),
  },
  methods: {
    ...mapActions('appLocation', ['setFilter', 'loadList']),
    ...mapActions('appFestivalTickets', ['getListFestival']),
    sendFilter() {
      const filter = {};
      if (this.name) filter.name = this.name;
      if (this.festival_id) filter.festival_id = this.festival_id;
      if (this.active !== null) filter.active = this.active;

      this.setFilter(filter);
      this.loadList({ filter, orderBy: this.getOrderBy });
    },
    clearFilter() {
      this.name = null;
      this.festival_id = null;
      this.active = null;
      this.setFilter({});
      this.loadList({ filter: {}, orderBy: this.getOrderBy });
    },
  },
  async created() {
    await this.getListFestival();
  },
};
</script>
