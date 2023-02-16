<template>
 <profile/>
</template>

<script>
import Profile from "@/components/User/UserProfile.vue";

export default {
  name: "ProfileView",
  components: {Profile},
  created() {
    document.title = "Профиль пользователя"
  },
}
</script>

<style scoped>

</style>
