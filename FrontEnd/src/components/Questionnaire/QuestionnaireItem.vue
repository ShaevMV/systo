<template>
      <div class="row">
        <div class="col-lg-12 mx-auto">
          <div class="card">
            <div class="card-body">
              <table class="table table-hover">
                <thead>
                <tr>
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
                <tr v-for="getQuestionnaireItem in getQuestionnaireList" v-bind:key="getQuestionnaireItem.id">
                  <td>{{ getQuestionnaireItem.name }}</td>
                  <td>{{ getQuestionnaireItem.phone }}</td>
                  <td>{{ getQuestionnaireItem.agy }}</td>
                  <td>{{ getQuestionnaireItem.telegram }}</td>
                  <td>{{ getQuestionnaireItem.vk }}</td>
                  <td>{{ getQuestionnaireItem.howManyTimes }}</td>
                  <td>{{ getQuestionnaireItem.musicStyles }}</td>
                  <td>{{ getQuestionnaireItem.questionForSysto }}</td>
                  <td>{{ getQuestionnaireItem.whereSysto }}</td>
                  <td><a :href="getQuestionnaireItem.link">Анкета</a></td>
                </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

</template>

<script>
import {mapGetters} from "vuex";

export default {
  name: "QuestionnaireItem",
  computed: {
    ...mapGetters('appOrder', [
      'getQuestionnaireList',
    ]),
  },
}
</script>

