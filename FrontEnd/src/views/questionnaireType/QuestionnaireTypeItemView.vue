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
  },
  beforeRouteEnter: (to, from, next) => {
    if (to.params.id) {
      window.store.dispatch('appQuestionnaireType/loadItem', {
        id: to.params.id,
      });
    }

    next();
  },
  created() {
    document.title = this.id ? "Редактирование типа анкеты" : "Создать тип анкеты"
  }
}
</script>

<style scoped>
</style>
