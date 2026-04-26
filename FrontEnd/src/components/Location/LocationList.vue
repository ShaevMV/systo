<template>
  <div class="title-block text-center">
    <h1 class="card-title"> Локации </h1>
  </div>
  <filter-location/>
  <div class="row mb-3">
    <div class="col-lg-12 mx-auto">
      <button class="btn btn-primary" @click="goToCreate">Создать локацию</button>
    </div>
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
                  @click="orderBy('name')">Название</th>
              <th scope="col">Фестиваль ID</th>
              <th scope="col"
                  style="cursor: pointer"
                  @click="orderBy('sort')">Сортировка</th>
              <th scope="col">Активность</th>
              <th scope="col"
                  style="cursor: pointer"
                  @click="orderBy('created_at')">Дата создания</th>
              <th scope="col" class="mobile"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(item, index) in getList"
                v-bind:key="index">
              <th scope="row" class="mobile" @click="goToItem(item.id)" style="cursor: pointer">
                {{ item.name }}
              </th>
              <td>{{ item.festival_id }}</td>
              <td>{{ item.sort }}</td>
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
import FilterLocation from "@/components/Location/FilterLocation.vue";

export default {
  name: "LocationList",
  components: {DateFormat, FilterLocation},
  computed: {
    ...mapGetters('appLocation', [
        'getList',
        'getFileter',
        'getOrderBy'
    ]),
  },
  methods: {
    ...mapActions('appLocation', [
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
      this.$router.push({ name: 'LocationItemView', params: { id: id } });
    },
    goToCreate() {
      this.$router.push({ name: 'LocationItemView' });
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
