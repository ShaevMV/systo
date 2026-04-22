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
            <div class="col-md-4">
              <label for="validationDefault01" class="form-label">Биллинг</label>
              <select class="form-select"
                      v-model="filter.isBilling"
                      id="validationDefault01">
                <option value=null>Выберите</option>
                <option value=true>Да</option>
                <option value=false>Нет</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="validationDefault01" class="form-label">Продовец живых билетов</label>
              <select class="form-select"
                      v-model="filter.userExternalId"
                      id="validationDefault01">
                <option value=null>Выберите</option>
                <option v-for="getlistSeller in getlistSellers"
                        v-bind:key="getlistSeller.id"
                        v-bind:value="getlistSeller">{{ getlistSeller.email }}
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
                      type="submit">Добавить новый
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
  name: "TypesOfPaymentFilter",
  data() {
    return {
      filter: {
        name: null,
        active: null,
        isBilling: null,
        userExternalId: null
      }
    }
  },
  computed: {
    ...mapGetters('appTypesOfPayment', [
      'getOrderBy',
      'getFileter'
    ]),
    ...mapGetters('appAccount', {
      getlistSellers: 'getList'
    }),
  },
  methods: {
    ...mapActions('appTypesOfPayment', [
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
        name: null,
        price: null,
        active: null,
        is_live_ticket: null,
      };
      await this.setFilter(this.filter)
      this.loadList({
        filter: this.getFileter,
        orderBy: this.getOrderBy
      });
    },
    goToItem() {
      const route = this.$router.resolve({name: 'TypesOfPaymentItemView'});
      window.open(route.href, '_blank');
    },
  }

}
</script>

<style scoped>

</style>