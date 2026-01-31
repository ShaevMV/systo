<template>
  <questionnaire-item/>
</template>

<script>
import FilterQuestionnaire from "@/components/Questionnaire/FilterQuestionnaire.vue";
import QuestionnaireList from "@/components/Questionnaire/QuestionnaireList.vue";
import QuestionnaireItem from "@/components/Questionnaire/QuestionnaireItem.vue";

export default {
  name: "QuestionnaireItemView",
  components: {QuestionnaireItem},
  created() {
    document.title = "Анкета пользователя"
  },
  props: {
    'id': String
  },

  beforeRouteEnter: (to, from, next) => {
    window.store.dispatch('appQuestionnaire/getQuestionnaire', to.params.id);
    next();
  },
}
</script>

<style scoped>

</style>