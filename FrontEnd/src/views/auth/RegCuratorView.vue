<template>
  <reg-curator-auth/>
</template>

<script>
import RegCuratorAuth from "@/components/Auth/RegCuratorAuth.vue";

export default {
  name: "RegCuratorView",
  components: {RegCuratorAuth},
  created() {
    document.title = "Регистрация куратора";
  },
};
</script>
