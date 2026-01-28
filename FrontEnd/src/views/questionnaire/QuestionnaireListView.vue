<template>
  <filter-questionnaire/>
  <questionnaire-list/>
</template>

<script>
import FilterQuestionnaire from "@/components/Questionnaire/FilterQuestionnaire.vue";
import QuestionnaireList from "@/components/Questionnaire/QuestionnaireList.vue";

export default {
  name: "QuestionnaireListView",
  components: {QuestionnaireList, FilterQuestionnaire},
  created() {
    document.title = "Все Анкеты"
  },
}
</script>

<style scoped>

</style>