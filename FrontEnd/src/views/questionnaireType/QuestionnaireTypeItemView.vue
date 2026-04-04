<template>
  <QuestionnaireTypeItem :id="id"/>
</template>

<script>
import QuestionnaireTypeItem from "@/components/QuestionnaireType/QuestionnaireTypeItem.vue";

export default {
  name: "QuestionnaireTypeItemView",
  components: {QuestionnaireTypeItem},
  props: {
    id: {
      type: String,
      default: null
    }
  }
}
</script>

<style scoped>
</style>
