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
              <th scope="col" class="mobile">№</th>
              <th scope="col" class="mobile"></th>
              <th scope="col">Email</th>
              <th scope="col">Имя</th>
              <th scope="col">Телефон</th>
              <th scope="col">Возраст</th>
              <th scope="col">Telegram-аккаунт:</th>
              <th scope="col">Профайл Вконтакте</th>
              <th scope="col">Сколько раз на Систо?</th>
              <th scope="col">Откуда</th>
              <th scope="col">Отправить повторно</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(item,index) in getQuestionnaireList"
                v-bind:key="index">

              <th scope="row" class="mobile" @click="goItemForUser(item.id)">
                {{ item.id }}
              </th>
              <td class="mobile">
                <div class="btn-group" v-show="item.status !== 'APPROVE' ">
                  <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown"
                          aria-haspopup="true"
                          aria-expanded="false">
                    ...
                  </button>
                  <div class="dropdown-menu">
                  <span class="dropdown-item btn-link"
                        role="button"
                        v-for="(statusItem, key) in listCorrectNextStatus" v-bind:key="key"
                        @click="chanceStatus({
                          id: item.id,
                          status: key
                        })">{{ statusItem }}</span>
                  </div>
                </div>
              </td>
              <td>{{ item.email }}</td>
              <td>{{ item.name }}</td>
              <td>{{ item.phone }}</td>
              <td>{{ item.agy }}</td>
              <td>{{ item.telegram }}</td>
              <td>{{ item.vk }}</td>
              <td>{{ item.howManyTimes }}</td>
              <td>{{ item.whereSysto }}</td>
              <td>
                <span
                    v-show="item.email"
                    @click="sendNotitification(item.id, item.email)"
                >
                  Выслать письмо
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
import {mapGetters, mapActions} from 'vuex';


export default {
  name: "QuestionnaireList",
  computed: {
    ...mapGetters('appQuestionnaire', [
      'getQuestionnaireList'
    ]),
    listCorrectNextStatus: function () {
      return {
        approve: "Подвердить"
      }
    }
  },
  methods: {
    ...mapActions('appQuestionnaire', [
      'loadQuestionnaire',
      'sendNotitificationUser',
      'approve',
    ]),
    goItemForUser(id) {
      const route = this.$router.resolve({ name: 'QuestionnaireEdit', params: { id: id } });
      window.open(route.href, '_blank');
    },
    chanceStatus: function (data) {

      if (data.status === 'approve') {
        this.approve({
          id: data.id,
          callback: function (message) {
            alert(message)
          }
        });
      }
    },
    sendNotitification: function (id, email) {
      this.sendNotitificationUser({
        id: id,
        email: email,
        callback: function () {
          alert('На почту отлитело!')
        }
      });

    }
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