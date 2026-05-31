<template>
  <OptionFilter />
  <OptionList />
</template>

<script>
import OptionFilter from '@/components/Option/OptionFilter.vue';
import OptionList from '@/components/Option/OptionList.vue';

export default {
  name: 'OptionListView',
  components: { OptionList, OptionFilter },
  created() {
    document.title = 'Опции к билетам';
  },
};
</script>

<style scoped>
</style>
