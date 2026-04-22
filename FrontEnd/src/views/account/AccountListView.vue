<template>
  <AccountFilter/>
  <AccountList/>
</template>

<script>
import AccountList from "@/components/Account/AccountList.vue";
import AccountFilter from "@/components/Account/AccountFilter.vue";

export default {
  name: "AccountListView",
  components: {AccountFilter, AccountList}
}
</script>

<style scoped>

</style>