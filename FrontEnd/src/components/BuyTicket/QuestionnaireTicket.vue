<template>
  <div class="row">
    <div class="mb-12" id="quest">


<!--      <div class="col-5">-->
<!--        <label for="masterName" class="reg-label">Твои имя и Фамилия *</label>-->
<!--        <input-->
<!--            type="text"-->
<!--            id="masterName"-->
<!--            class="form-control"-->
<!--            placeholder="Твои имя и фамилия"-->
<!--            aria-label="Твои имя и фамилия"-->
<!--            v-model="masterName"-->
<!--            aria-describedby="basic-addon1"-->
<!--        />-->
<!--        <small class="form-text text-muted">-->
<!--          {{ getError('masterName') }}</small-->
<!--        >-->
<!--      </div>-->

      <div class="quest-item">
        <label for="questionnaire_namey">Твои Имя и Фамилия: *</label>

        <!-- Сюда добавил поле Имя и Фамилия -->
        <div class="input-group" id="promo-input">
          <input
              type="number"
              id="questionnaire_namey"
              class="form-control"
              placeholder="Твои Имя и Фамилия"
              aria-label="Твои Имя и Фамилия"
              :value="questionnaire.namey"
              @input="updateParent('namey', $event.target.value)"
              aria-describedby="basic-addon1"
          />
        </div>
      </div>

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
              @input="updateParent('agy', $event.target.value)"
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
              @input="updateParent('telegram', $event.target.value)"
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
              @input="updateParent('phone', $event.target.value)"
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
              @input="updateParent('vk', $event.target.value)"
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
              @input="updateParent('howManyTimes', $event.target.value)"
              aria-describedby="basic-addon1"
          />
      </div>
      </div>

      <div class="quest-item">
        <label for="questionnaire_musicStyles">Стили музыки, которые предпочитаешь в лесу:</label>

                    <input
                        type="text"
                        class="form-control"
                        :value="questionnaire.musicStyles"
                        id="questionnaire_musicStyles"
                        placeholder="Стили музыки, которые предпочитаешь в лесу?"
                        aria-label="Стили музыки, которые предпочитаешь в лесу?"
                        @input="updateParent('musicStyles', $event.target.value)"
                    >
      </div>

      <div class="quest-item">
        <label for="questionnaire_questionForSysto">Ответь кратко и честно на простой вопрос "Зачем ты едешь на Систо?": *</label>

                    <input
                        type="text"
                        class="form-control"
                        :value="questionnaire.questionForSysto"
                        id="questionnaire_questionForSysto"
                        placeholder="Зачем ты едешь на Систо?"
                        aria-label="Зачем ты едешь на Систо?"
                        @input="updateParent('questionForSysto', $event.target.value)"
                    >
        </div>

      <!-- Добавил вопрос Откуда ты узнал о Систо? -->
      <div class="quest-item">
        <label for="questionnaire_whereSysto">Откуда ты узнал о Систо?:</label>

        <input
            type="text"
            class="form-control"
            :value="questionnaire.whereSysto"
            id="questionnaire_whereSysto"
            placeholder="Откуда ты узнал о Систо?"
            aria-label="Откуда ты узнал о Систо?"
            @input="updateParent('whereSysto', $event.target.value)"
        >
      </div>
    </div>
  </div>

</template>

<script>


export default {
  name: "QuestionnaireTicket",
  props: ['questionnaire'],
  methods: {
    updateParent(field, value) {
      const updated = {
        ...this.questionnaire,
        [field]: value
      };
      this.$emit('update-questionnaire', updated);
    }
  }
}
</script>

<style scoped>

</style>