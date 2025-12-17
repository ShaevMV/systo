<template>
  <div class="container-fluid" id="main-quest">
    <div class="text-center title-block">
      <h1>Заполни анкетные данные для подтверждения участия в туристическом слёте:</h1>
    </div>
    <div class="row">
      <div class="col-10 col-md-12 col mx-auto">
        <div class="card">
          <div class="card-body pt-3">
            <questionnaire-ticket :questionnaire="questionnaire"/>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


<script>

import QuestionnaireTicket from "@/components/BuyTicket/QuestionnaireTicket.vue";

export default {
  name: 'QuestionnaireView',
  components: {
    QuestionnaireTicket
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
  created() {
    document.title = "Анкета участника Systo"
  },
}
</script>