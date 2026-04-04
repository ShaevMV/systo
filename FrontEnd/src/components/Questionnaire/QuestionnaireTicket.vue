<template>
  <div class="row">
    <div class="mb-12" id="quest">
      <div v-for="(question, index) in questionsList" :key="index" class="quest-item">
        <label :for="'questionnaire_' + question.name">
          {{ question.title }}
          <span v-if="question.required">*</span>
        </label>

        <!-- Текстовое поле (большое) -->
        <textarea
            v-if="question.type === 'text'"
            :id="'questionnaire_' + question.name"
            class="form-control"
            :placeholder="question.title"
            :value="getFieldValue(question.name)"
            @input="updateField(question.name, $event.target.value)"
            :disabled="isDisabled ? '' : disabled"
        ></textarea>

        <!-- Число -->
        <div v-else-if="question.type === 'number'" class="input-group" id="promo-input">
          <input
              type="number"
              :id="'questionnaire_' + question.name"
              class="form-control"
              :placeholder="question.title"
              :value="getFieldValue(question.name)"
              @input="updateField(question.name, $event.target.value)"
              :disabled="isDisabled ? '' : disabled"
          />
        </div>

        <!-- Строка -->
        <div v-else class="input-group" id="promo-input">
          <input
              :type="question.name === 'email' ? 'email' : 'text'"
              :id="'questionnaire_' + question.name"
              class="form-control"
              :class="{'is-invalid': getError('questionnaire.' + question.name)}"
              :placeholder="question.title"
              :value="getFieldValue(question.name)"
              @input="updateField(question.name, $event.target.value)"
              :disabled="isDisabled ? '' : disabled"
          />
        </div>

        <div class="messager text-danger" v-show="getError('questionnaire.' + question.name)">
          {{ getError('questionnaire.' + question.name) }}
        </div>
      </div>

      <!-- Чекбокс "Хочу вступить в клуб" -->
      <div class="quest-item form-check"
           id="check-check"
           v-show="!isAdmin"
      >
        <input
            class="form-check-input"
            type="checkbox"
            :checked="questionnaire.is_have_in_club"
            id="defaultCheck1"
            @change="updateField('is_have_in_club', $event.target.checked)"
            :disabled="isDisabled ? '' : disabled"
        />
        <label class="form-check-label" for="defaultCheck1">
          Хочу вступить в приватный клуб (18+)
        </label>
      </div>
    </div>
    <div class="col-12" v-if="canApprove">
      <button class="btn btn-success" @click="handleApprove">Подтвердить анкету</button>
    </div>
  </div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex';

export default {
  name: "QuestionnaireTicket",
  props: {
    questionnaire: {
      type: Object,
      default: null,
    },
    questionnaireType: {
      type: Object,
      default: null,
    },
    isDisabled: {
      type: Boolean,
      default: false
    },
    isNewUser: {
      type: Boolean,
      default: false
    },
  },
  computed: {
    ...mapGetters('appQuestionnaire', ['getError']),
    canApprove() {
      return this.questionnaire && this.questionnaire.id && this.questionnaire.status !== 'APPROVE';
    },
    questionsList() {
      if (!this.questionnaireType || !this.questionnaireType.questions) {
        // Фоллбэк на стандартные поля если тип анкеты не загружен
        return [
          { title: 'Твои Имя и Фамилия', name: 'name', type: 'string', required: true },
          { title: 'Email', name: 'email', type: 'string', required: true },
          { title: 'Возраст', name: 'agy', type: 'number', required: true },
          { title: 'Telegram', name: 'telegram', type: 'string', required: false },
          { title: 'Номер телефона', name: 'phone', type: 'string', required: true },
          { title: 'Профайл Вконтакте', name: 'vk', type: 'string', required: false },
          { title: 'Бывал ли ты ранее на Систо', name: 'howManyTimes', type: 'text', required: true },
          { title: 'Стили музыки, которые предпочитаешь в лесу', name: 'musicStyles', type: 'text', required: false },
          { title: 'Зачем ты едешь на Систо?', name: 'questionForSysto', type: 'text', required: true },
          { title: 'Откуда ты узнал о Систо?', name: 'whereSysto', type: 'text', required: false },
          { title: 'Считаете ли вы себя участвующим в сотворении Систо?', name: 'creationOfSisto', type: 'text', required: false },
          { title: 'Готовы принимать более активное или творческое участие?', name: 'activeOfEvent', type: 'text', required: false },
        ];
      }
      
      let questions = this.questionnaireType.questions;
      if (typeof questions === 'string') {
        try {
          questions = JSON.parse(questions);
        } catch (e) {
          questions = [];
        }
      }
      return questions || [];
    }
  },
  methods: {
    ...mapActions('appQuestionnaire', ['approve', 'getQuestionnaire']),
    handleApprove() {
      if (!this.questionnaire || !this.questionnaire.id) return;
      this.approve({
        id: this.questionnaire.id,
        callback: (message) => {
          console.log(message);
          this.getQuestionnaire({ id: this.questionnaire.id });
        }
      });
    },
    getFieldValue(fieldName) {
      if (!this.questionnaire) return null;
      return this.questionnaire[fieldName] || null;
    },
    updateField(fieldName, value) {
      const updated = { ...this.questionnaire, [fieldName]: value };
      this.$emit('update-questionnaire', updated);
    }
  }
}
</script>

<style scoped>
</style>
