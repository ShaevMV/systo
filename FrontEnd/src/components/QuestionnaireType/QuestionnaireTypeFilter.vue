<template>
  <div class="title-block text-center">
    <h1 class="card-title"> Фильтр типов анкет </h1>
  </div>
  <div class="row">
    <div class="col-lg-12 mx-auto">
      <div class="card">
        <div class="card-body">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Название</label>
                <input type="text" class="form-control" v-model="filter.name" placeholder="Название типа анкеты">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Активность</label>
                <select class="form-control" v-model="filter.active">
                  <option value="">Все</option>
                  <option value="1">Активные</option>
                  <option value="0">Неактивные</option>
                </select>
              </div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
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
import {mapActions, mapGetters} from "vuex";

export default {
  name: "QuestionnaireTypeFilter",
  data() {
    return {
      filter: {
        name: '',
        active: '',
      }
    }
  },
  computed: {
    ...mapGetters('appQuestionnaireType', [
        'getFilter',
        'getOrderBy'
    ]),
  },
  methods: {
    ...mapActions('appQuestionnaireType', [
        'loadList',
        'setFilter'
    ]),
    applyFilter() {
      this.setFilter(this.filter);
      this.loadList({
        filter: this.filter,
        orderBy: this.getOrderBy,
      });
    },
    resetFilter() {
      this.filter = {
        name: '',
        active: '',
      };
      this.setFilter(this.filter);
      this.loadList({
        filter: this.filter,
        orderBy: this.getOrderBy,
      });
    },
  },
  created() {
    this.filter = {...this.getFilter};
  },
}
</script>

<style scoped>
</style>
