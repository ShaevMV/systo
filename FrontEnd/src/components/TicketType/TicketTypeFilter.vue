<template>
  <div class="row" id="filter">
    <div class="col-lg-12 mx-auto mb-5">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Фильтр</h5>

          <div class="d-flex flex-wrap">
            <!--  email -->
            <div class="col-md-4">
              <label for="validationDefaultUsername" class="form-label">Название</label>
              <div class="input-group">
                <span class="input-group-text" id="inputGroupPrepend2"></span>
                <input type="text"
                       v-model="filter.name"
                       class="form-control"
                       id="validationDefaultUsername"
                       aria-describedby="inputGroupPrepend2">
              </div>
            </div>
            <!--  telegram -->
            <div class="col-md-4">
              <label for="validationDefaultUsername" class="form-label">Активность</label>
              <select class="form-select"
                      v-model="filter.active"
                      id="validationDefault01">
                <option value=null>Выберите</option>
                <option value=true>Да</option>
                <option value=false>Нет</option>
              </select>
            </div>
            <!--  vk -->
            <div class="col-md-4">
              <label for="validationDefault01" class="form-label">Для живых билетов </label>
              <select class="form-select"
                      v-model="filter.is_live_ticket"
                      id="validationDefault01">
                <option value=null>Выберите</option>
                <option value=true>Да</option>
                <option value=false>Нет</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="validationDefault05" class="form-label">Выберите фестиваль</label>
              <select class="form-select"
                      v-model="filter.festival_id"
                      id="validationDefault05">
                <option v-for="(festivalItem) in getFestivalList"
                        v-bind:key="festivalItem.id"
                        :selected="festivalItem.id == festival_id"
                        v-bind:value="festivalItem.id">{{ festivalItem.name }} {{ festivalItem.year }}
                </option>
              </select>
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
              <button class="btn btn-primary"
                      @click="goToItem"
                      type="submit">Добавить новый тип
              </button>
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
  name: "TicketTypeFilter",
  data() {
    return {
      filter: {
        name: null,
        active: null,
        is_live_ticket: null,
        festival_id: null,
      }
    }
  },
  computed: {
    ...mapGetters('appTicketType', [
      'getOrderBy',
        'getFileter'
    ]),
    ...mapGetters('appFestivalTickets', [
      'getFestivalList',
    ]),
  },
  methods: {
    ...mapActions('appTicketType',[
        'loadList',
        'setFilter',
    ]),
    ...mapActions('appFestivalTickets', [
      'getListFestival',
    ]),
     sendFilter: async function () {
       await this.setFilter(this.filter)
       this.loadList({
        filter: this.getFileter,
         orderBy: this.getOrderBy
      })
    },
    clearFilter: async function () {
      this.filter = {
        name: null,
        active: null,
        festival_id: null,
      };
      await this.setFilter(this.filter)
      this.loadList({
        filter: this.getFileter,
        orderBy: this.getOrderBy
      });
    },
    goToItem() {
      const route = this.$router.resolve({ name: 'TicketTypeItemView' });
      window.open(route.href, '_blank');
    },
  },
  async created() {
    await this.getListFestival();
  },
}
</script>

<style scoped>

</style>