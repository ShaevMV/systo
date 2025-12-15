<template>
  <div>
    <div class="pp1 row">
      <span>ШАГ 4.</span> Заполни анкетные данные для подтверждения участия в туристическом слёте:
    </div>
    <div class="mb-12">
      <div class="col-3">
        <label for="questionnaire_agy">Возраст: *</label>
      </div>

      <div class="col-4">
        <div class="input-group" id="promo-input">
          <input
              type="number"
              id="questionnaire_agy"
              class="form-control"
              placeholder="Возраст"
              aria-label="Возраст"
              :value="questionnaire.agy"
              aria-describedby="basic-addon1"
          />
        </div>
      </div>

      <div class="col-3">
        <label for="questionnaire_tellegram">Telegram-аккаунт:</label>
      </div>

      <div class="col-4">
        <div class="input-group" id="promo-input">
          <input
              type="text"
              id="questionnaire_tellegram"
              class="form-control"
              placeholder="Telegram-аккаунт"
              aria-label="Telegram-аккаунт"
              :value="questionnaire.telegram"
              aria-describedby="basic-addon1"
          />
        </div>
      </div>

      <div class="col-3">
        <label for="questionnaire_vk">Профайл Вконтакте:</label>
      </div>

      <div class="col-4">
        <div class="input-group" id="promo-input">
          <input
              type="text"
              id="questionnaire_tellegram"
              class="form-control"
              placeholder="Профайл Вконтакте"
              aria-label="Профайл Вконтакте"
              :value="questionnaire.vk"
              aria-describedby="basic-addon1"
          />
        </div>
      </div>

      <div class="col-3">
        <label for="questionnaire_howManyTimes">Сколько раз ты уже бывал на Систо? *</label>
      </div>

      <div class="col-4">
        <div class="input-group" id="promo-input">
          <input
              type="number"
              id="questionnaire_howManyTimes"
              class="form-control"
              placeholder="Сколько раз ты уже бывал на Систо? *"
              aria-label="Сколько раз ты уже бывал на Систо? *"
              :value="questionnaire.howManyTimes"
              aria-describedby="basic-addon1"
          />
        </div>
      </div>

      <div class="col-3">
        <label for="questionnaire_musicStyles">Стили музыки, которые предпочитаешь в лесу:</label>
      </div>

      <div class="col-9">
                    <textarea
                        class="form-control order-text"
                        :value="questionnaire.musicStyles"
                        id="questionnaire_musicStyles"
                    ></textarea>
      </div>

      <div class="col-3">
        <label for="questionnaire_questionForSysto">Ответь кратко и честно на простой вопрос "Зачем ты едешь на Систо?": *</label>
      </div>

      <div class="col-9">
                    <textarea
                        class="form-control order-text"
                        :value="questionnaire.questionForSysto"
                        id="questionnaire_musicStyles"
                    ></textarea>
      </div>
    </div>
  </div>

</template>

<script>
export default {
  name: "QuestionnaireTicket",
  props: ['questionnaire'],
}
</script>

<style scoped>

</style>