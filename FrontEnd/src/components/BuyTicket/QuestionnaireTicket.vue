<template>
  <div>
    <div class="mb-12" id="quest">
      <div class="quest-item">
        <label for="questionnaire_agy">Возраст: *</label>

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

      <div class="quest-item">
        <label for="questionnaire_tellegram">Telegram-аккаунт:</label>

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

      <div class="quest-item phone-item">
        <label for="questionnaire_phone">Номер телефона:</label>

        <div class="input-group" id="promo-input">
          <input
              type="text"
              id="questionnaire_phone"
              class="form-control"
              placeholder="Номер телефона"
              aria-label="Номер телефона"
              :value="questionnaire.phone"
              aria-describedby="basic-addon1"
          />
        </div>
      </div>

      <div class="quest-item">
        <label for="questionnaire_vk">Профайл Вконтакте:</label>

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

      <div class="quest-item">
        <label for="questionnaire_howManyTimes">Сколько раз ты уже бывал на Систо? *</label>

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

      <div class="quest-item">
        <label for="questionnaire_musicStyles">Стили музыки, которые предпочитаешь в лесу:</label>

                    <textarea
                        class="form-control order-text"
                        :value="questionnaire.musicStyles"
                        id="questionnaire_musicStyles"
                    ></textarea>
      </div>

      <div class="quest-item last-item">
        <label for="questionnaire_questionForSysto">Ответь кратко и честно на простой вопрос "Зачем ты едешь на Систо?": *</label>

                    <textarea
                        class="form-control order-text"
                        :value="questionnaire.questionForSysto"
                        id="questionnaire_questionForSysto"
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