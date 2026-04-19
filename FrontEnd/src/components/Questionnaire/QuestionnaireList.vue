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
              <th scope="col">Телефон</th>
              <th scope="col">Telegram</th>
              <th scope="col">Сколько ты раз был на систо</th>
              <th scope="col" class="mobile">Тип анкеты</th>
              <th scope="col">Статус</th>

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
              <td>{{ item.email || '—' }}</td>
              <td>{{ item.phone || '—' }}</td>
              <td class="mobile">{{ item.telegram || '—' }}</td>
              <td>{{ item.howManyTimes || '—' }}</td>
              <td class="mobile">{{ getQuestionnaireTypeName(item.questionnaire_type_id) }}</td>
              <td>{{ getStatusName(item.status) }}</td>
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
    ...mapGetters('appTicketType', [
      'getQuestionnaireTypeList'
    ]),
    listCorrectNextStatus: function () {
      return {
        approve: "Подтвердить"
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
          alert('На почту отправлено!')
        }
      });

    },
    getQuestionnaireTypeName(questionnaireTypeId) {
      if (!questionnaireTypeId) {
        return '—';
      }
      const type = this.getQuestionnaireTypeList.find(t => t.id === questionnaireTypeId);
      return type ? type.name : '—';
    },
    getStatusName(status) {
      const statusMap = {
        'NEW': 'Новая',
        'APPROVE': 'Одобрена',
      };
      return statusMap[status] || status;
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