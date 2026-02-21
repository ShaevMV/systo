<template>
  <div class="title-block text-center">
    <h1 class="card-title"> Типы оргвзносов </h1>
  </div>
  <div class="row">
    <div class="col-lg-12 mx-auto" id="filter-results">
      <div class="card">
        <div class="card-body">
          <table class="table table-hover">
            <thead>
            <tr>
              <th scope="col">Имя</th>
              <th scope="col">Стоимость</th>
              <th scope="col">Лимит на кол-во</th>
              <th scope="col">Сорт</th>
              <th scope="col">Для живых билетов</th>
              <th scope="col">Активность</th>
              <th scope="col" class="mobile"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(item,index) in getList"
                v-bind:key="index">
              <th scope="row" class="mobile" @click="goToItem(item.id)">
                {{ item.name }}
              </th>
              <td>{{ item.price }}</td>
              <td>{{ item.groupLimit }}</td>
              <td>{{ item.sort }}</td>
              <td>{{ item.is_live_ticket }}</td>
              <td>{{ item.active }}</td>
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

export default {
  name: "TicketTypeList",
  computed: {
    ...mapGetters('appTicketType', [
      'getList'
    ]),
  },
  methods: {
    ...mapActions('appTicketType', [
        'loadList',
        'remove'
    ]),
    localRemove(id) {
      this.remove({
        id: id,
      });
    },
    goToItem(id) {
      const route = this.$router.resolve({ name: 'TicketTypeItemView', params: { id: id } });
      window.open(route.href, '_blank');
    },
  },
  async created() {
    await this.loadList({
      filter: {}
    });
  },
}
</script>

<style scoped>

</style>