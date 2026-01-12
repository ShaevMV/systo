<template>
  <invite-link/>
</template>

<script>
import InviteLink from "@/components/Auth/InviteLink.vue";

export default {
  name: "InviteLinkView",
  components: {InviteLink},
  created() {
    document.title = "Странница получение персональной ссылке"
  },
}
</script>

<style scoped>

</style>