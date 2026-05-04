<template>
  <div class="title-block text-center">
    <h1 class="card-title"> Локации (сцены) </h1>
    <router-link :to="{ name: 'LocationItemView' }" class="btn btn-success mb-2">+ Создать локацию</router-link>
  </div>
  <div class="row">
    <div class="col-lg-12 mx-auto" id="filter-results">
      <div class="card">
        <div class="card-body">
          <table class="table table-hover">
            <thead>
            <tr>
              <th scope="col" style="cursor: pointer" @click="orderByLocal('name')">Название</th>
              <th scope="col">Описание</th>
              <th scope="col">Фестиваль</th>
              <th scope="col">Активность</th>
              <th scope="col" style="cursor: pointer" @click="orderByLocal('created_at')">Дата создания</th>
              <th scope="col"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(item, index) in getList" v-bind:key="index">
              <th scope="row" @click="goToItem(item.id)" style="cursor: pointer">
                {{ item.name }}
              </th>
              <td>{{ item.description }}</td>
              <td>{{ festivalName(item.festival_id) }}</td>
              <td>{{ item.active ? 'ДА' : 'НЕТ' }}</td>
              <td><date-format :date="item.created_at" /></td>
              <td>
                <span style="cursor: pointer" v-show="item.id" @click="localRemove(item.id)">
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
import { mapActions, mapGetters } from 'vuex';
import DateFormat from '@/components/Utilite/DateFormat.vue';

export default {
  name: 'LocationList',
  components: { DateFormat },
  computed: {
    ...mapGetters('appLocation', ['getList', 'getFilter', 'getOrderBy']),
    ...mapGetters('appFestivalTickets', ['getFestivalList']),
  },
  methods: {
    ...mapActions('appLocation', ['loadList', 'setOrderBy', 'remove']),
    ...mapActions('appFestivalTickets', ['getListFestival']),
    festivalName(id) {
      const f = (this.getFestivalList || []).find((x) => x.id === id);
      return f ? f.name : id;
    },
    localRemove(id) {
      if (!confirm('Удалить локацию?')) return;
      this.remove({ id: id });
    },
    async orderByLocal(name) {
      await this.setOrderBy(name);
      await this.loadList({
        filter: this.getFilter,
        orderBy: this.getOrderBy,
      });
    },
    goToItem(id) {
      this.$router.push({ name: 'LocationItemView', params: { id: id } });
    },
  },
  async created() {
    this.getListFestival();
    await this.loadList({
      filter: this.getFilter,
      orderBy: this.getOrderBy,
    });
  },
};
</script>

<style scoped>
</style>
