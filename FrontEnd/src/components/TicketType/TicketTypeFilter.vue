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
            <!--  статус -->
            <div class="col-md-4">
              <label for="validationDefault01" class="form-label">Стоимость</label>
              <input type="text"
                     v-model="filter.price"
                     class="form-control"
                     id="validationDefaultUsername"
                     aria-describedby="inputGroupPrepend2">
            </div>
            <!--  telegram -->
            <div class="col-md-4">
              <label for="validationDefaultUsername" class="form-label">Активность</label>
              <select class="form-select"
                      v-model="filter.active"
                      id="validationDefault01">
                <option value="null">Выберите</option>
                <option value="true">Да</option>
                <option value="true">Нет</option>
              </select>
            </div>
            <!--  vk -->
            <div class="col-md-4">
              <label for="validationDefault01" class="form-label">Для живых билетов </label>
              <select class="form-select"
                      v-model="filter.is_live_ticket"
                      id="validationDefault01">
                <option value="null">Выберите</option>
                <option value="1">Да</option>
                <option value="0">Нет</option>
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
import {mapActions} from "vuex";

export default {
  name: "TicketTypeFilter",
  data() {
    return {
      filter: {
        name: null,
        price: null,
        active: null,
        is_live_ticket: null,
      }
    }
  },
  methods: {
    ...mapActions('appTicketType',[
      'loadList'
    ]),
    sendFilter: function () {
      this.loadList({
        filter: this.filter,
      })
    },
    clearFilter: function () {
      this.filter = {
        name: null,
        price: null,
        active: null,
        is_live_ticket: null,
      };
      this.loadList({
        'filter': this.filter,
      });
    },
    goToItem() {
      const route = this.$router.resolve({ name: 'TicketTypeItemView' });
      window.open(route.href, '_blank');
    },

  },
}
</script>

<style scoped>

</style>