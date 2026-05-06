<template>
  <div class="title-block text-center">
    <h1 class="card-title"> ❤️❤️ НАШИ ЛЮБИМЫе ПОЛЬЗОВАТЕЛИ ❤️❤️ </h1>
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
                  @click="orderBy('email')">email
              </th>
              <th scope="col"
                  style="cursor: pointer"
                  @click="orderBy('phone')">Телефон
              </th>
              <th scope="col"
                  style="cursor: pointer"
                  @click="orderBy('city')">город
              </th>
              <th scope="col"
                  style="cursor: pointer"
                  @click="orderBy('role')">роль
              </th>
              <th scope="col"
                  style="cursor: pointer"
                  @click="orderBy('created_at')">Дата создание
              </th>
              <th scope="col">Сменить роль</th>
              <th scope="col" class="mobile"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(item,index) in getList"
                v-bind:key="index">
              <td>{{ item.email }}</td>
              <td>{{ item.phone }}</td>
              <td>{{ item.city }}</td>
              <td>{{ listRole[item.role] }}</td>
              <td>
                <date-format :date="item.created_at"/>
              </td>
              <td class="mobile">
                <div class="btn-group">
                  <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown"
                          aria-haspopup="true"
                          aria-expanded="false">
                    ...
                  </button>
                  <div class="dropdown-menu">
                  <span class="dropdown-item btn-link"
                        role="button"
                        v-for="(role, key) in listRole" v-bind:key="key"
                        @click="chance(item.id,key)">{{ role }}</span>
                  </div>
                </div>
              </td>
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
  name: "AccountList",
  components: {DateFormat},
  computed: {
    ...mapGetters('appAccount', [
      'getList',
      'getFileter',
      'getOrderBy'
    ]),
    listRole() {
      return {
        admin: 'Админ',
        seller: 'Продовец живых билетов',
        pusher: 'Френдли Продовец ',
        guest: 'Гость',
        manager: 'Менеджер',
        curator: 'Куратор (списки)',
        pusher_curator: 'Френдли + Куратор',
      };
    },
  },
  methods: {
    ...mapActions('appAccount', [
      'loadList',
      'setOrderBy',
      'chanceRole',
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
    async chance(id, role) {
      let self = this;
      await this.chanceRole({
        id: id,
        role: role,
        calback: function () {
          self.loadList({
            filter: self.getFileter,
            orderBy: self.getOrderBy,
          });
        }
      });
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