<template>
  <div>
    <promo-code-list/>
  </div>
</template>

<script>
import PromoCodeList from "@/components/PromoCode/PromoCodeList.vue";

export default {
  name: "PromoCodeView",
  components: {PromoCodeList},
  created() {
    document.title = "Промокод"
  }
}
</script>

<style scoped>

</style>