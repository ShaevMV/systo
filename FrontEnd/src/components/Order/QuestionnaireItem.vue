<template>
      <div class="row">
        <div class="col-lg-12 mx-auto">
          <div class="card">
            <div class="card-body">
              <table class="table table-hover">
                <thead>
                <tr>
                  <th scope="col">Возраст</th>
                  <th scope="col">Telegram-аккаунт:</th>
                  <th scope="col">Профайл Вконтакте</th>
                  <th scope="col">Сколько раз ты уже бывал на Систо?</th>
                  <th scope="col">Стили музыки, которые предпочитаешь в лесу</th>
                  <th scope="col">Ответь кратко и честно на простой вопрос "Зачем ты едешь на Систо?"</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                  <td>{{ getQuestionnaireItem.agy }}</td>
                  <td>{{ getQuestionnaireItem.telegram }}</td>
                  <td>{{ getQuestionnaireItem.vk }}</td>
                  <td>{{ getQuestionnaireItem.howManyTimes }}</td>
                  <td>{{ getQuestionnaireItem.musicStyles }}</td>
                  <td>{{ getQuestionnaireItem.questionForSysto }}</td>
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
      'getQuestionnaireItem',
    ]),
  },
}
</script>

