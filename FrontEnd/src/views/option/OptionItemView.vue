<template>
  <OptionItem :id="id" />
</template>

<script>
import OptionItem from '@/components/Option/OptionItem.vue';

export default {
  name: 'OptionItemView',
  components: { OptionItem },
  props: {
    id: { type: String, default: null },
  },
  beforeRouteEnter: (to, from, next) => {
    if (to.params.id) {
      window.store.dispatch('appOption/loadItem', { id: to.params.id });
    } else {
      window.store.commit('appOption/setItem', {});
    }
    next();
  },
  created() {
    document.title = this.id ? 'Редактирование опции' : 'Создать опцию';
  },
};
</script>

<style scoped>
</style>
