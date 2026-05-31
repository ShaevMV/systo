<template>
  <div class="title-block text-center">
    <h1 class="card-title"> Опции к билетам </h1>
    <router-link :to="{ name: 'OptionItemView' }" class="btn btn-success mb-2">+ Создать опцию</router-link>
  </div>
  <div class="row">
    <div class="col-lg-12 mx-auto" id="filter-results">
      <div class="card">
        <div class="card-body">
          <table class="table table-hover">
            <thead>
            <tr>
              <th scope="col" style="cursor: pointer" @click="orderByLocal('name')">Название</th>
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
              <td>{{ festivalName(item.festival_id) }}</td>
              <td>
                <span :class="item.active ? 'badge bg-success' : 'badge bg-secondary'">
                  {{ item.active ? 'ДА' : 'НЕТ' }}
                </span>
              </td>
              <td><date-format :date="item.created_at" /></td>
              <td>
                <span style="cursor: pointer" v-show="item.id" @click="localRemove(item.id)">
                  🗑️
                </span>
              </td>
            </tr>
            <tr v-if="!getList || !getList.length">
              <td colspan="5" class="text-muted text-center">Опций ещё нет. Создайте первую.</td>
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
  name: 'OptionList',
  components: { DateFormat },
  computed: {
    ...mapGetters('appOption', ['getList', 'getFilter', 'getOrderBy']),
    ...mapGetters('appFestivalTickets', ['getFestivalList']),
  },
  methods: {
    ...mapActions('appOption', ['loadList', 'setOrderBy', 'remove']),
    ...mapActions('appFestivalTickets', ['getListFestival']),
    festivalName(id) {
      const f = (this.getFestivalList || []).find((x) => x.id === id);
      return f ? `${f.name} ${f.year ?? ''}`.trim() : id;
    },
    localRemove(id) {
      if (!confirm('Удалить опцию? Привязки к типам билетов и волны цен будут удалены каскадно.')) return;
      this.remove({ id: id });
    },
    async orderByLocal(name) {
      await this.setOrderBy(name);
      await this.loadList({ filter: this.getFilter, orderBy: this.getOrderBy });
    },
    goToItem(id) {
      this.$router.push({ name: 'OptionItemView', params: { id: id } });
    },
  },
  async created() {
    this.getListFestival();
    await this.loadList({ filter: this.getFilter, orderBy: this.getOrderBy });
  },
};
</script>

<style scoped>
</style>
