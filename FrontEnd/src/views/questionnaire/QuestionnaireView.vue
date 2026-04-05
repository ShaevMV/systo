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
            <a class="nav-item"
                 v-if="isAdmin || isManager"
                 @click="goList"
            >
              Все анкеты
            </a>

            <questionnaire-ticket
                :questionnaire="currentQuestionnaire"
                :questionnaire-type="questionnaireType"
                :is-disabled="isAdmin || isManager"
                @update-questionnaire="updateQuestionnaire"
            />
            <div class="form-check"
                 id="check-check"
                 v-show="!isAdmin || !isManager"
            >
              <input
                  class="form-check-input"
                  type="checkbox"
                  value=""
                  v-model="confirm"
                  id="defaultCheck1"
              />
              <label class="form-check-label" for="defaultCheck1">
                Отправляя заявку на вступление в клуб, ты соглашаешься с
                &nbsp;<a href="/conditions" target="_blank"><b>условиями туристического слёта</b></a>
                и <a href="/private" target="_blank"><b>Политикой обработки персональных данных.</b></a>
              </label>
            </div>
            <div class="col-12" v-show="!isAdmin || !isManager">
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
  name: 'QuestionnaireView',
  components: {
    QuestionnaireTicket
  },
  props: {
    'order_id': String,
    'ticket_id': String,
    'id': String,
  },
  data() {
    return {
      questionnaire: {},
      confirm: false,
      isLoaded: false,
    }
  },
  computed: {
    ...mapGetters('appUser', [
      'isAdmin',
      'isManager'
    ]),
    ...mapGetters('appQuestionnaire', [
      'getQuestionnaireItem'
    ]),
    ...mapGetters('appQuestionnaireType', [
      'getItem'
    ]),
    questionnaireType() {
      return this.getItem || null;
    },
    // Единый источник данных анкеты
    currentQuestionnaire() {
      if (this.isAdmin || this.isManager) {
        return this.getQuestionnaireItem || {};
      }
      return this.questionnaire;
    },
    isCorrect() {
      if (!this.questionnaireType || !this.questionnaireType.questions) return false;

      let questions = this.questionnaireType.questions;
      if (typeof questions === 'string') {
        questions = JSON.parse(questions);
      }

      for (let q of questions) {
        if (q.required && (this.currentQuestionnaire[q.name] === null || this.currentQuestionnaire[q.name] === undefined || this.currentQuestionnaire[q.name] === '')) {
          return false;
        }
      }

      return this.confirm;
    }
  },
  watch: {
    // Копируем данные анкеты из Vuex в локальный state (для админа)
    getQuestionnaireItem: {
      handler(item) {
        if (item && Object.keys(item).length > 0) {
          this.questionnaire = { ...item };
        }
      },
      immediate: true,
    },
    // Инициализируем поля вопросов
    questionnaireType: {
      immediate: true,
      handler(type) {
        if (type && type.questions) {
          let questions = type.questions;
          if (typeof questions === 'string') {
            try { questions = JSON.parse(questions); } catch (e) { questions = []; }
          }
          const q = {};
          questions.forEach(question => {
            // Если значение уже есть — сохраняем, если нет — null
            const existingValue = this.questionnaire[question.name];
            q[question.name] = existingValue !== undefined && existingValue !== null
              ? existingValue
              : null;
          });
          this.questionnaire = { ...this.questionnaire, ...q };
          // Удаляем поля которых нет в новых вопросах (кроме стандартных)
          const standardFields = ['id', 'status', 'link', 'message', 'user_id', 'order_id', 'ticket_id', 'questionnaire_type_id'];
          Object.keys(this.questionnaire).forEach(key => {
            if (!questions.find(q => q.name === key) && !standardFields.includes(key)) {
              delete this.questionnaire[key];
            }
          });
          this.isLoaded = true;
        }
      }
    }
  },
  methods: {
    ...mapActions('appQuestionnaire', [
      'sendQuestionnaire',
      'editQuestionnaire'
    ]),
    goList() {
      this.$router.push({name: 'QuestionnaireList'});
    },
    send() {
      let self = this;
      if (this.order_id && this.ticket_id) {
        this.sendQuestionnaire({
          questionnaire: this.currentQuestionnaire,
          orderId: this.order_id,
          ticketId: this.ticket_id,
          callback: function () {
            document.getElementById('modalOpenBtn').click();
            // Сбросить значения
            Object.keys(self.questionnaire).forEach(key => { self.questionnaire[key] = null; });
            self.confirm = false;
          },
        })
      }

      if (this.id) {
        this.editQuestionnaire({
          questionnaire: this.currentQuestionnaire,
          id: this.id,
          callback: function () {
            document.getElementById('modalOpenBtn').click();
          },
        })
      }

    },
    updateQuestionnaire(updatedQuestionnaire) {
      if (this.isAdmin || this.isManager) {
        // Для админа обновляем Vuex store
        this.$store.commit('appQuestionnaire/setQuestionnaireItem', updatedQuestionnaire);
      } else {
        this.questionnaire = updatedQuestionnaire;
      }
    }
  },
  created() {
    document.title = "Анкета участника Solar Systo Togathering"
  },
  beforeRouteEnter: (to, from, next) => {
    // Если есть order_id и ticket_id, загружаем тип анкеты по заказу/билету
    if (to.params.order_id && to.params.ticket_id) {
      window.store.dispatch('appQuestionnaireType/loadQuestionnaireTypeByOrderTicket', {
        orderId: to.params.order_id,
        ticketId: to.params.ticket_id,
      }).catch(() => {
        // Игнорируем ошибку, страница всё равно загрузится
      }).finally(() => {
        // Загружаем анкету если есть id
        if (to.params.id) {
          window.store.dispatch('appQuestionnaire/getQuestionnaire', {
            id: to.params.id,
          });
        }
        next();
      });
    } else if (to.params.id) {
      // Режим редактирования анкеты — сначала анкета, потом тип
      window.store.dispatch('appQuestionnaire/getQuestionnaire', {
        id: to.params.id,
      }).then((questionnaire) => {
        if (questionnaire && questionnaire.questionnaire_type_id) {
          // Возвращаем промис — ждём загрузки типа перед рендером
          return window.store.dispatch('appQuestionnaireType/loadItem', {
            id: questionnaire.questionnaire_type_id,
          });
        }
        // Fallback: если questionnaire_type_id нет, пробуем загрузить гостевую
        return window.store.dispatch('appQuestionnaireType/loadQuestionnaireTypeByCode', {
          code: 'guest',
        });
      }).catch(() => {
        // Игнорируем ошибку
      }).finally(() => {
        next();
      });
    } else {
      // Фоллбэк: загружаем гостевую анкету по коду
      window.store.dispatch('appQuestionnaireType/loadQuestionnaireTypeByCode', {
        code: 'guest',
      }).catch(() => {
        // Игнорируем ошибку
      }).finally(() => {
        next();
      });
    }
  },
}
</script>