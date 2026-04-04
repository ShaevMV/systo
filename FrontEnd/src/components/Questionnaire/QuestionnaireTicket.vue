<template>
  <div class="row">
    <div class="mb-12" id="quest">
      <div v-if="questionsList.length === 0" class="text-center">
        <p>Загрузка вопросов...</p>
      </div>
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
    </div>
    <div class="col-12" v-if="canApprove">
      <button class="btn btn-success" @click="handleApprove">Подтвердить анкету</button>
    </div>
  </div>
</template>

<script>
import { mapActions } from 'vuex';

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
    canApprove() {
      return this.questionnaire && this.questionnaire.id && this.questionnaire.status !== 'APPROVE';
    },
    questionsList() {
      if (!this.questionnaireType || !this.questionnaireType.questions) {
        return [];
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
