<template>
  <div v-if="!getError('error')">
    <promo-code-item :id="id"/>
  </div>
  <div v-else>
    {{ getError('error') }}
  </div>
</template>

<script>
import PromoCodeItem from "@/components/PromoCode/PromoCodeItem.vue";
import {mapGetters} from "vuex";
export default {
  name: "PromoCodeItemView",
  components: {PromoCodeItem},
  props: {
    'id': String
  },
  computed: {
    ...mapGetters('appPromoCode', [
      'getError',
    ]),
  },
  beforeRouteEnter: (to, from, next) => {
    window.store.dispatch('appPromoCode/loadPromoCodeItem', to.params.id);
    next();
  },
}
</script>

<style scoped>

</style>