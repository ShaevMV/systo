<template>
  <questionnaire-filter/>
  <questionnaire-list/>
</template>

<script>
import QuestionnaireFilter from "@/components/Questionnaire/QuestionnaireFilter.vue";
import QuestionnaireList from "@/components/Questionnaire/QuestionnaireList.vue";

export default {
  name: "QuestionnaireListView",
  components: {QuestionnaireList, QuestionnaireFilter},
  created() {
    document.title = "Все Анкеты"
  },
}
</script>

<style scoped>

</style>