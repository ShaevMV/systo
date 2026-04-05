<template>
  <div class="container-fluid" id="main-quest">
    <button
        type="button"
        class="btn btn-primary"
        v-show="false"
        data-toggle="modal"
        id="modalOpenBtn"
        data-target="#exampleModal"
    >
      Launch demo modal
    </button>
    <div
        class="modal fade"
        id="exampleModal"
        tabindex="-1"
        role="dialog"
        aria-labelledby="exampleModalLabel"
        aria-hidden="true"
    >
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Успех</h5>
            <button
                type="button"
                class="close"
                data-dismiss="modal"
                aria-label="Close"
            >
              <span aria-hidden="true">х</span>
            </button>
          </div>
          <div class="modal-body">Спасибо большое, ваши анкетные данные зарегистрированы, ждем Вас на Систо</div>
          <div class="modal-footer">
            <button
                type="button"
                class="btn btn-secondary"
                data-dismiss="modal"
            >
              Закрыть
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="text-center title-block">
      <h1>Заполни анкетные данные для подтверждения участия в туристическом слёте:</h1>
    </div>
    <div class="row">
      <div class="col-10 col-md-12 col mx-auto">
        <div class="card">
          <div class="card-body pt-3">
            <questionnaire-ticket
                :questionnaire="questionnaire"
                :questionnaire-type="questionnaireType"
                :is-newUser="true"
                @update-questionnaire="updateQuestionnaire"
            />
            <div class="form-check"
                 id="check-check"
            >
              <input
                  class="form-check-input"
                  type="checkbox"
                  value=""
                  v-model="confirm"
                  id="defaultCheck2"
              />
              <label class="form-check-label" for="defaultCheck2">
                Отправляя заявку на вступление в клуб, ты соглашаешься с
                &nbsp;<a href="/conditions" target="_blank"><b>условиями туристического слёта</b></a>
                и <a href="/private" target="_blank"><b>Политикой обработки персональных данных.</b></a>
              </label>
            </div>
            <div class="col-12" v-show="!isAdmin">
              <button
                  type="button"
                  @click="send"
                  :disabled="!isCorrect"
                  class="btn btn-lg btn-block btn-outline-primary reg-btn"
              >
                Зарегистрировать анкету
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>


<script>

import QuestionnaireTicket from "@/components/Questionnaire/QuestionnaireTicket.vue";
import {mapActions, mapGetters} from "vuex";

export default {
  name: 'QuestionnaireRegView',
  components: {
    QuestionnaireTicket
  },
  data() {
    return {
      questionnaire: {},
      confirm: false,
    }
  },
  computed: {
    ...mapGetters('appQuestionnaireType', [
      'getItem'
    ]),
    questionnaireType() {
      return this.getItem || null;
    },
    isCorrect() {
      if (!this.questionnaireType || !this.questionnaireType.questions) return false;

      let questions = this.questionnaireType.questions;
      if (typeof questions === 'string') {
        questions = JSON.parse(questions);
      }

      for (let q of questions) {
        if (q.required && (this.questionnaire[q.name] === null || this.questionnaire[q.name] === undefined || this.questionnaire[q.name] === '')) {
          return false;
        }
      }

      return this.confirm;
    }
  },
  watch: {
    questionnaireType: {
      immediate: true,
      handler(type) {
        if (type && type.questions) {
          let questions = type.questions;
          if (typeof questions === 'string') {
            try { questions = JSON.parse(questions); } catch (e) { questions = []; }
          }
          const q = {};
          questions.forEach(question => { q[question.name] = null; });
          this.questionnaire = { ...this.questionnaire, ...q };
          Object.keys(this.questionnaire).forEach(key => {
            if (!questions.find(q => q.name === key)) {
              delete this.questionnaire[key];
            }
          });
        }
      }
    }
  },
  methods: {
    ...mapActions('appQuestionnaire', [
      'sendNewUserQuestionnaire',
    ]),
    send() {
      let self = this;
      this.sendNewUserQuestionnaire({
        questionnaire: this.questionnaire,
        callback: function () {
          document.getElementById('modalOpenBtn').click();
          Object.keys(self.questionnaire).forEach(key => { self.questionnaire[key] = null; });
          self.confirm = false;
        },
      })
    },
    updateQuestionnaire(updatedQuestionnaire) {
      this.questionnaire = updatedQuestionnaire;
    }
  },
  created() {
    document.title = "Анкета участника Solar Systo Togathering"
  },
  beforeRouteEnter: (to, from, next) => {
    // Загружаем тип анкеты "Анкета нового пользователя" по коду
    window.store.dispatch('appQuestionnaireType/loadQuestionnaireTypeByCode', {
      code: 'new_user'
    }).catch(() => {
      // Игнорируем ошибку, страница всё равно загрузится
    }).finally(() => {
      next();
    });
  },
}
</script>