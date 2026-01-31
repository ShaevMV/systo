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
                <th scope="col">Стили музыки</th>
                <th scope="col">"Зачем ты на Систо?"</th>
                <th scope="col">Откуда</th>
                <th scope="col">Ссылка на анкету</th>
              </tr>
            </thead>
            <tbody>
            <tr v-for="(item,index) in getQuestionnaireList"
                v-bind:key="index"
                @click="goItemOrderForUser(item.id)">
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
  },
  methods: {
    ...mapActions('appQuestionnaire', [
      'loadQuestionnaire',
    ]),
    goItemOrderForUser(idOrderItem) {
      this.$router.push({name: 'orderItems', params: {id: idOrderItem}});
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