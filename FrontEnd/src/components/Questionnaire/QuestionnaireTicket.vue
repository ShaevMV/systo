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
        <template v-if="question.type === 'text'">
          <textarea
              :id="'questionnaire_' + question.name"
              class="form-control"
              :class="{'is-invalid': getError(question.name)}"
              :placeholder="question.title"
              :value="getFieldValue(question.name)"
              @input="updateField(question.name, $event.target.value)"
              :disabled="isDisabled"
          ></textarea>
        </template>

        <!-- Число -->
        <template v-else-if="question.type === 'number'">
          <div class="input-group" id="promo-input">
            <input
                type="number"
                :id="'questionnaire_' + question.name"
                class="form-control"
                :class="{'is-invalid': getError(question.name)}"
                :placeholder="question.title"
                :value="getFieldValue(question.name)"
                @input="updateField(question.name, $event.target.value)"
                :disabled="isDisabled"
            />
          </div>
        </template>

        <!-- Строка -->
        <template v-else>
          <div class="input-group" id="promo-input">
            <input
                :type="question.name === 'email' ? 'email' : 'text'"
                :id="'questionnaire_' + question.name"
                class="form-control"
                :class="{'is-invalid': getError(question.name)}"
                :placeholder="question.title"
                :value="getFieldValue(question.name)"
                @input="updateField(question.name, $event.target.value)"
                :disabled="isDisabled"
            />
          </div>
        </template>

        <!-- Сообщение об ошибке -->
        <div class="messager text-danger" v-if="getError(question.name)">
          {{ getError(question.name) }}
        </div>
      </div>
    </div>
    <div class="col-12" v-if="canApprove">
      <button class="btn btn-success" @click="handleApprove">Подтвердить анкету</button>
    </div>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

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
