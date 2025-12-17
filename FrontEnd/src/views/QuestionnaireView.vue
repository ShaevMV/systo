<template>
  <div class="container-fluid" id="main-quest">
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
    <div class="row">

    </div>
  </div>
</template>


<script>

import QuestionnaireTicket from "@/components/BuyTicket/QuestionnaireTicket.vue";
import {mapActions} from "vuex";

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
      this.sendQuestionnaire({
        questionnaire: this.questionnaire,
        orderId: this.order_id,
        ticketId: this.ticket_id,
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