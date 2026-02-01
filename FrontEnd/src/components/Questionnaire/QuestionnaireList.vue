<template>
  <div class="title-block text-center">
    <h1 class="card-title"> Анкеты пользователей </h1>
  </div>
  <div class="row">
    <div class="col-lg-12 mx-auto" id="filter-results">
      <div class="card">
        <div class="card-body">

          <table class="table table-hover">
            <thead>
              <tr>
                <th scope="col" class="mobile">№ заказа</th>
                <th scope="col" class="mobile"></th>
                <th scope="col">Email</th>
                <th scope="col">Имя</th>
                <th scope="col">Телефон</th>
                <th scope="col">Возраст</th>
                <th scope="col">Telegram-аккаунт:</th>
                <th scope="col">Профайл Вконтакте</th>
                <th scope="col">Сколько раз на Систо?</th>
                <th scope="col">Откуда</th>
                <th scope="col">Ссылка на анкету</th>
              </tr>
            </thead>
            <tbody>
            <tr v-for="(item,index) in getQuestionnaireList"
                v-bind:key="index"
                @click="goItemForUser(item.id)">

              <th scope="row" class="mobile">
                {{ item.id }}
              </th>

              <td class="mobile">
                <div class="btn-group" v-show="item.status !== 'APPRUVE' ">
                  <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown"
                          aria-haspopup="true"
                          aria-expanded="false">
                    ...
                  </button>
                  <div class="dropdown-menu">
                  <span class="dropdown-item btn-link"
                        role="button"
                        v-for="(statusItem, key) in listCorrectNextStatus" v-bind:key="key"
                        @click="chanceStatus(key,itemOrder.id)">{{ statusItem }}</span>
                  </div>
                </div>
              </td>


              <td>{{ item.email }}</td>

              <td>{{ item.name }}</td>
              <td>{{ item.phone }} рублей</td>
              <td>{{ item.agy }}</td>
              <td>{{ item.telegram }}</td>
              <td>{{ item.vk }}</td>
              <td>{{ item.howManyTimes }}</td>
              <td>{{ item.whereSysto }}</td>
            </tr>


            </tbody>
          </table>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {mapGetters, mapActions} from 'vuex';

export default {
  name: "QuestionnaireList",
  computed: {
    ...mapGetters('appQuestionnaire', [
       'getQuestionnaireList'
    ]),
    listCorrectNextStatus: function () {
      return [{
        "APPRUVE" : "Подвердить"
      }]
    }
  },
  methods: {
    ...mapActions('appQuestionnaire', [
      'loadQuestionnaire',
    ]),
    goItemForUser(id) {
      this.$router.push({name: 'QuestionnaireEdit', params: {id: id}});
    },
  },
  async created() {
    await this.loadQuestionnaire({
      filter: {}
    });
  },
}
</script>

<style scoped>

</style>