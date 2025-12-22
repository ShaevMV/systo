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
          <div class="modal-body" v-html="getMessageForQuestionnaire"></div>
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
                @update-questionnaire="updateQuestionnaire"
            />
            <div class="col-12">
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

import QuestionnaireTicket from "@/components/BuyTicket/QuestionnaireTicket.vue";
import {mapActions, mapGetters} from "vuex";

export default {
  name: 'QuestionnaireView',
  components: {
    QuestionnaireTicket
  },
  props: {
    'order_id': String,
    'ticket_id': String,
  },
  data() {
    return {
      questionnaire: {
        agy: null,
        telegram: null,
        vk: null,
        phone: null,
        howManyTimes: null,
        musicStyles: null,
        questionForSysto: null,
      }
    }
  },
  computed: {
    ...mapGetters('appOrder', [
      'getMessageForQuestionnaire',
    ]),
    isCorrect() {
      return this.questionnaire.agy !== null &&
          this.questionnaire.howManyTimes !== null &&
          this.questionnaire.questionForSysto !== null
    }
  },
  methods: {
    ...mapActions('appOrder', [
      'sendQuestionnaire'
    ]),
    send(){
      let self = this;
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
          };
      },
      })
    },
    updateQuestionnaire(updatedQuestionnaire) {
      this.questionnaire = updatedQuestionnaire;
    }
  },
  created() {
    document.title = "Анкета участника Systo"
  },
}
</script>