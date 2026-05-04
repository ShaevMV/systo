<template>
  <LocationItem :id="id" />
</template>

<script>
import LocationItem from '@/components/Location/LocationItem.vue';

export default {
  name: 'LocationItemView',
  components: { LocationItem },
  props: {
    id: {
      type: String,
      default: null,
    },
  },
  beforeRouteEnter: (to, from, next) => {
    if (to.params.id) {
      window.store.dispatch('appLocation/loadItem', { id: to.params.id });
    } else {
      window.store.commit('appLocation/setItem', {});
    }
    next();
  },
  created() {
    document.title = this.id ? 'Редактирование локации' : 'Создать локацию';
  },
};
</script>

<style scoped>
</style>
