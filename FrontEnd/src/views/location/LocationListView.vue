<template>
  <location-list/>
</template>

<script>
import LocationList from "@/components/Location/LocationList.vue";

export default {
  name: "LocationListView",
  components: {LocationList},
  created() {
    document.title = "Локации";
  },
  beforeRouteEnter: (to, from, next) => {
    window.store.dispatch('appFestivalTickets/getListFestival');
    window.store.dispatch('appLocation/loadList', { filter: {}, orderBy: {} });
    next();
  },
}
</script>

<style scoped>

</style>
