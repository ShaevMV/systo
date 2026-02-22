<template>
  <div class="title-block text-center">
    <h1 class="card-title"> Типы оплат </h1>
  </div>
  <div class="row">
    <div class="col-lg-12 mx-auto" id="filter-results">
      <div class="card">
        <div class="card-body">
          <table class="table table-hover">
            <thead>
            <tr>
              <th scope="col"
                  style="cursor: pointer"
                  @click="orderBy('name')">Имя</th>
              <th scope="col"
                  style="cursor: pointer"
                  @click="orderBy('sort')">Сорт</th>
              <th scope="col">Реализатор</th>
              <th scope="col">Биллинг</th>
              <th scope="col">Активность</th>
              <th scope="col"
                  style="cursor: pointer"
                  @click="orderBy('created_at')">Дата создание</th>
              <th scope="col" class="mobile"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(item,index) in getList"
                v-bind:key="index">
              <th scope="row" class="mobile" @click="goToItem(item.id)" style="cursor: pointer">
                {{ item.name }}
              </th>
              <td>{{ item.sort }}</td>
              <td>{{ item.email_seller }}</td>
              <td>{{ item.is_billing ? 'ДА' : 'НЕТ' }}</td>
              <td>{{ item.active ? 'ДА' : 'НЕТ' }}</td>
              <td> <date-format :date="item.created_at"/> </td>
              <td>
                <span
                    style="cursor: pointer"
                    v-show="item.id"
                    @click="localRemove(item.id)"
                >
                  🗑️
                </span>
              </td>
            </tr>


            </tbody>
          </table>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {mapActions, mapGetters} from "vuex";
import DateFormat from "@/components/Utilite/DateFormat.vue";

export default {
  name: "TypesOfPaymentList",
  components: {DateFormat},
  computed: {
    ...mapGetters('appTypesOfPayment', [
        'getList',
        'getFileter',
        'getOrderBy'
    ]),
  },
  methods: {
    ...mapActions('appTypesOfPayment', [
        'loadList',
        'setOrderBy',
        'remove'
    ]),
    localRemove(id) {
      this.remove({
        id: id,
      });
    },
    async orderBy(name) {
      await this.setOrderBy(name);
      await this.loadList({
        filter: this.getFileter,
        orderBy: this.getOrderBy,
      });
    },
    goToItem(id) {
      const route = this.$router.resolve({ name: 'TypesOfPaymentItemView', params: { id: id } });
      window.open(route.href, '_blank');
    },
  },
  async created() {
    await this.loadList({
      filter: this.getFileter,
      orderBy: this.getOrderBy,
    });
  },
}
</script>

<style scoped>

</style>