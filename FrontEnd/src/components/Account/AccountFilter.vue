<template>
  <div class="row" id="filter">
    <div class="col-lg-12 mx-auto mb-5">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Фильтр</h5>

          <div class="d-flex flex-wrap">
            <!--  email -->
            <div class="col-md-4">
              <label for="validationDefaultUsername" class="form-label">email</label>
              <div class="input-group">
                <span class="input-group-text" id="inputGroupPrepend2"></span>
                <input type="email"
                       v-model="filter.email"
                       class="form-control"
                       id="validationDefaultUsername"
                       aria-describedby="inputGroupPrepend2">
              </div>
            </div>
            <div class="col-md-4">
              <label for="validationDefaultUsername" class="form-label">Имя</label>
              <div class="input-group">
                <span class="input-group-text" id="inputGroupPrepend2"></span>
                <input type="text"
                       v-model="filter.name"
                       class="form-control"
                       id="validationDefaultUsername"
                       aria-describedby="inputGroupPrepend2">
              </div>
            </div>
            <div class="col-md-4">
              <label for="validationDefaultUsername" class="form-label">Телефон</label>
              <div class="input-group">
                <span class="input-group-text" id="inputGroupPrepend2"></span>
                <input type="tel"
                       v-model="filter.phone"
                       class="form-control"
                       id="validationDefaultUsername"
                       aria-describedby="inputGroupPrepend2">
              </div>
            </div>
            <div class="col-md-4">
              <label for="validationDefaultUsername" class="form-label">Город</label>
              <div class="input-group">
                <span class="input-group-text" id="inputGroupPrepend2"></span>
                <input type="text"
                       v-model="filter.city"
                       class="form-control"
                       id="validationDefaultUsername"
                       aria-describedby="inputGroupPrepend2">
              </div>
            </div>
            <div class="col-md-4">
              <label for="validationDefaultUsername" class="form-label">Роль</label>
              <select class="form-select"
                      v-model="filter.role"
                      id="validationDefault01">
                <option value=null>Выберите</option>
                <option value="admin">Админ</option>
                <option value="seller">реализатор живых билетов</option>
                <option value="pusher">реализатор френдли билетов</option>
                <option value="manager">менеджер</option>
                <option value="curator">куратор (списки)</option>
                <option value="pusher_curator">Френдли + Куратор</option>
                <option value="guest">ГОСТь фестиваля</option>
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
  name: "AccountFilter",
  data() {
    return {
      filter: {
        email: null,
        name: null,
        phone: null,
        role: null,
        city: null,
      }
    }
  },
  computed: {
    ...mapGetters('appAccount', [
      'getOrderBy',
      'getFileter'
    ]),
  },
  methods: {
    ...mapActions('appAccount', [
      'loadList',
      'setFilter',
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
        email: null,
        name: null,
        phone: null,
        role: null,
        city: null,
      };
      await this.setFilter(this.filter)
      this.loadList({
        filter: this.getFileter,
        orderBy: this.getOrderBy
      });
    },
  }

}
</script>

<style scoped>

</style>