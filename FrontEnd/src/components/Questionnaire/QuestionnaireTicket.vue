<template>
  <div class="row">
    <div class="mb-12" id="quest">

      <div class="quest-item">
        <label for="questionnaire_namey">Твои Имя и Фамилия: *</label>

        <div class="input-group" id="promo-input">
          <input
              type="text"
              id="questionnaire_namey"
              class="form-control"
              placeholder="Твои Имя и Фамилия"
              aria-label="Твои Имя и Фамилия"
              :value="questionnaire.name"
              @input="updateParent('name', $event.target.value)"
              aria-describedby="basic-addon1"
              :disabled="isDisabled ? '' : disabled"
          />
        </div>
      </div>

      <div class="quest-item">
        <label for="questionnaire_namey">Email: *</label>

        <div class="input-group" id="promo-input">
          <input
              type="email"
              id="questionnaire_email"
              class="form-control"
              placeholder="email"
              aria-label="email"
              :value="questionnaire.email"
              @input="updateParent('email', $event.target.value)"
              aria-describedby="basic-addon1"
              :disabled="isDisabled ? '' : disabled"
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
              :disabled="isDisabled ? '' : disabled"
          />
        </div>
      </div>

      <div class="quest-item">
        <label for="questionnaire_tellegram">Telegram-аккаунт: (для доступа в новый закрытый чат Систо)</label>

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
              :disabled="isDisabled ? '' : disabled"
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
              :disabled="isDisabled ? '' : disabled"
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
              :disabled="isDisabled ? '' : disabled"
          />
      </div>
      </div>

      <div class="quest-item" v-show="!isNewUser">
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
              :disabled="isDisabled ? '' : disabled"
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
                        :disabled="isDisabled ? '' : disabled"
                    >
      </div>

      <div class="quest-item">
        <label for="questionnaire_questionForSysto">Ответь кратко на вопрос "Зачем ты едешь на Систо?": *</label>

                    <input
                        type="text"
                        class="form-control"
                        :value="questionnaire.questionForSysto"
                        id="questionnaire_questionForSysto"
                        placeholder="Зачем ты едешь на Систо?"
                        aria-label="Зачем ты едешь на Систо?"
                        @input="updateParent('questionForSysto', $event.target.value)"
                        :disabled="isDisabled ? '' : disabled"
                    >
        </div>

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
            :disabled="isDisabled ? '' : disabled"
        >
      </div>

      <div class="quest-item" v-show="!isNewUser">
        <label for="questionnaire_creationOfSisto"> Считаете ли вы себя участвующим в сотворении Систо?:</label>

        <input
            type="text"
            class="form-control"
            :value="questionnaire.creationOfSisto"
            id="questionnaire_creationOfSisto"
            placeholder="Считаете ли вы себя участвующим в сотворении Систо?"
            aria-label="Считаете ли вы себя участвующим в сотворении Систо?"
            @input="updateParent('creationOfSisto', $event.target.value)"
            :disabled="isDisabled ? '' : disabled"
        >
      </div>

      <div class="quest-item">
        <label for="questionnaire_activeOfEvent"> Готовы принимать более активное или творческое участие в создании события?:</label>

        <input
            type="text"
            class="form-control"
            :value="questionnaire.activeOfEvent"
            id="questionnaire_activeOfEvent"
            placeholder="Готовы принимать более активное или творческое участие в создании события?"
            aria-label="Откуда ты узнал о Систо?"
            @input="updateParent('activeOfEvent', $event.target.value)"
            :disabled="isDisabled ? '' : disabled"
        >
      </div>
      <div class="quest-item form-check"
           id="check-check"
           v-show="!isAdmin"
      >
        <input
            class="form-check-input"
            type="checkbox"
            :value="questionnaire.is_have_in_club"
            id="defaultCheck1"
            :disabled="isDisabled ? '' : disabled"
        />
        <label class="form-check-label" for="defaultCheck1">
          Хочу вступить в приватный клуб (18+)
        </label>
      </div>
    </div>
  </div>

</template>

<script>


export default {
  name: "QuestionnaireTicket",
  props: {
    questionnaire: {
      type: [Object],
      default: null,
    },
    isDisabled: {
      type: [Boolean],
      default: false
    },
    isNewUser: {
      type: [Boolean],
      default: false
    },
  },
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