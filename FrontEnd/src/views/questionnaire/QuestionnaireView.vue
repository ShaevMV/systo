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
                :questionnaire="(isAdmin || isManager)? getQuestionnaireItem : questionnaire"
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
      questionnaire: {
        name: null,    // Добавил дату для имени и фамилии
        agy: null,
        email: null,
        telegram: null,
        vk: null,
        phone: null,
        howManyTimes: null,
        musicStyles: null,
        questionForSysto: null,
        creationOfSisto: null,
        is_have_in_club: false,
        activeOfEvent: null,
        whereSysto: null  // Добавил дату для вопроса Откуда узнал о Систо?
      },
      confirm: false,
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
    isCorrect() {
      return this.questionnaire.agy !== null &&
          this.questionnaire.howManyTimes !== null &&
          this.questionnaire.questionForSysto !== null &&
          this.questionnaire.email !== null &&
          this.questionnaire.phone !== null &&
          this.confirm;
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
          questionnaire: this.questionnaire,
          orderId: this.order_id,
          ticketId: this.ticket_id,
          callback: function () {
            document.getElementById('modalOpenBtn').click();
            self.questionnaire = {
              agy: null,
              telegram: null,
              vk: null,
              phone: null,
              howManyTimes: null,
              musicStyles: null,
              questionForSysto: null,
              creationOfSisto: null,
              activeOfEvent: null,
            };
          },
        })
      }

      if (this.id) {
        this.editQuestionnaire({
          questionnaire: this.questionnaire,
          id: this.id,
          callback: function () {
            document.getElementById('modalOpenBtn').click();
          },
        })
      }

    },
    updateQuestionnaire(updatedQuestionnaire) {
      this.questionnaire = updatedQuestionnaire;
    }
  },
  created() {
    document.title = "Анкета участника Solar Systo Togathering"
  },
  beforeRouteEnter: (to, from, next) => {
    if (to.params.id) {
      window.store.dispatch('appQuestionnaire/getQuestionnaire', {
        id: to.params.id,
      });
    }

    next();
  },
}
</script>