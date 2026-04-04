<template>
  <QuestionnaireTypeFilter/>
  <questionnaire-type-list/>
</template>

<script>
import QuestionnaireTypeFilter from "@/components/QuestionnaireType/QuestionnaireTypeFilter.vue";
import QuestionnaireTypeList from "@/components/QuestionnaireType/QuestionnaireTypeList.vue";

export default {
  name: "QuestionnaireTypeListView",
  components: {QuestionnaireTypeList, QuestionnaireTypeFilter}
}
</script>

<style scoped>
</style>
