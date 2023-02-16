<template>
  <reg-auth/>
</template>

<script>
import RegAuth from "@/components/Auth/RegAuth.vue";

export default {
  name: "RegView",
  components: {RegAuth},
  created() {
    document.title = "Зарегистрироваться"
  },
}
</script>

<style scoped>

</style>
