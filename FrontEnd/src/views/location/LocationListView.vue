<template>
  <LocationFilter />
  <LocationList />
</template>

<script>
import LocationFilter from '@/components/Location/LocationFilter.vue';
import LocationList from '@/components/Location/LocationList.vue';

export default {
  name: 'LocationListView',
  components: { LocationList, LocationFilter },
  created() {
    document.title = 'Локации';
  },
};
</script>

<style scoped>
</style>
