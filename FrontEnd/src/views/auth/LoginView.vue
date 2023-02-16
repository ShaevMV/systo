<template>
    <LoginAuth/>
</template>

<script>
import LoginAuth from "@/components/Auth/LoginAuth";

export default {
  name: "LoginView",
  components: {LoginAuth},
  created() {
    document.title = "Авторизоваться"
  },
}
</script>

<style scoped>

</style>
