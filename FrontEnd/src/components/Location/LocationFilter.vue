<template>
  <div class="title-block text-center">
    <h1 class="card-title"> Фильтр локаций </h1>
  </div>
  <div class="row">
    <div class="col-lg-12 mx-auto">
      <div class="card">
        <div class="card-body">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Название</label>
                <input type="text" class="form-control" v-model="filter.name" placeholder="Название локации">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Фестиваль</label>
                <select class="form-control" v-model="filter.festival_id">
                  <option value="">Все</option>
                  <option v-for="f in getFestivalList" :key="f.id" :value="f.id">
                    {{ f.name }} {{ f.year }}
                  </option>
                </select>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>Активность</label>
                <select class="form-control" v-model="filter.active">
                  <option value="">Все</option>
                  <option value="1">Активные</option>
                  <option value="0">Неактивные</option>
                </select>
              </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <button class="btn btn-primary me-2" @click="applyFilter">Применить</button>
              <button class="btn btn-secondary" @click="resetFilter">Сбросить</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

export default {
  name: 'LocationFilter',
  data() {
    return {
      filter: {
        name: '',
        festival_id: '',
        active: '',
      },
    };
  },
  computed: {
    ...mapGetters('appLocation', ['getFilter', 'getOrderBy']),
    ...mapGetters('appFestivalTickets', ['getFestivalList']),
  },
  methods: {
    ...mapActions('appLocation', ['loadList', 'setFilter']),
    ...mapActions('appFestivalTickets', ['getListFestival']),
    applyFilter() {
      this.setFilter(this.filter);
      this.loadList({
        filter: this.filter,
        orderBy: this.getOrderBy,
      });
    },
    resetFilter() {
      this.filter = { name: '', festival_id: '', active: '' };
      this.setFilter(this.filter);
      this.loadList({
        filter: this.filter,
        orderBy: this.getOrderBy,
      });
    },
  },
  created() {
    this.filter = { ...{ name: '', festival_id: '', active: '' }, ...this.getFilter };
    this.getListFestival();
  },
};
</script>

<style scoped>
</style>
