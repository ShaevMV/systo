<template>
  <ForgotPasswordAuth/>
</template>

<script>
import ForgotPasswordAuth from "@/components/Auth/ForgotPasswordAuth.vue";

export default {
  name: "ForgotPasswordView",
  components: {ForgotPasswordAuth},
  created() {
    document.title = "Восстановить пароль"
  },
}
</script>

<style scoped>

</style>
