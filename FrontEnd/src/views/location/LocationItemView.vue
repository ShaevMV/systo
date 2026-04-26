<template>
  <LocationItem :id="id"/>
</template>

<script>
import LocationItem from "@/components/Location/LocationItem.vue";

export default {
  name: "LocationItemView",
  components: {LocationItem},
  props: {
    id: {
      type: [String],
      default: null,
    }
  },
  beforeRouteEnter: (to, from, next) => {
    window.store.dispatch('appFestivalTickets/getListFestival');
    window.store.dispatch('appTicketType/loadTemplate');
    window.store.dispatch('appLocation/clearItem');
    if (to.params.id) {
      window.store.dispatch('appLocation/loadItem', {
        id: to.params.id,
      });
    }
    next();
  },
  created() {
    document.title = "Локация"
  }
}
</script>

<style scoped>

</style>
