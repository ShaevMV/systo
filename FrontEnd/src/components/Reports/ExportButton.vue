<template>
  <div>
    <button 
      class="btn btn-success" 
      @click="exportData"
      :disabled="!selectedConfig || isLoading"
    >
      <span v-if="isLoading">Выгрузка...</span>
      <span v-else>📊 Выгрузить в Google Sheets</span>
    </button>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

export default {
  name: 'ExportButton',
  data() {
    return {
      selectedConfig: null,
      isLoading: false,
    }
  },
  computed: {
    ...mapGetters('appReport', ['getActiveConfigs']),
    currentFilter() {
      return this.$store.getters['appOrder/getCurrentFilter'] || {};
    }
  },
  methods: {
    ...mapActions('appReport', ['exportToGoogle', 'loadConfigs']),
    
    async exportData() {
      if (!this.selectedConfig) {
        const configs = this.getActiveConfigs;
        if (configs.length === 0) {
          alert('Нет активных конфигураций для экспорта');
          return;
        }
        this.selectedConfig = configs[0];
      }
      
      if (!confirm('Выгрузить данные в Google Таблицу?')) return;
      
      this.isLoading = true;
      
      try {
        const filter = {
          festivalId: this.currentFilter.festivalId || this.selectedConfig.filters?.festival_id,
        };
        
        const result = await this.exportToGoogle({
          config_id: this.selectedConfig.id,
          festival_id: filter.festivalId,
          limit: this.selectedConfig.filters?.limit,
        });
        
        alert(`Успешно выгружено ${result.exportedRows} строк!\nURL: ${result.googleSheetUrl}`);
      } catch (error) {
        alert('Ошибка экспорта: ' + (error.response?.data?.message || error.message));
      } finally {
        this.isLoading = false;
      }
    }
  },
  async mounted() {
    await this.loadConfigs();
  }
}
</script>

<style scoped>
</style>
