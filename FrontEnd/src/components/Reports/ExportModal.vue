<template>
  <div class="modal fade" :class="{ show: modelValue }" :style="displayStyle" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Настройка выгрузки отчёта по френдли</h5>
          <button type="button" class="close" @click="close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveConfig">
            <div class="form-group">
              <label>Название отчёта *</label>
              <input v-model="config.name" class="form-control" required placeholder="Ежедневный отчёт по френдли" />
            </div>
            
            <div class="form-group">
              <label>Google Spreadsheet ID *</label>
              <input 
                v-model="config.spreadsheet_id" 
                class="form-control" 
                required 
                placeholder="1BxiMvs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms"
              />
              <small class="form-text text-muted">ID таблицы из URL Google Sheets</small>
            </div>
            
            <div class="form-row">
              <div class="form-group col-md-6">
                <label>Sheet Name</label>
                <input v-model="config.sheet_name" class="form-control" />
              </div>
              
              <div class="form-group col-md-6">
                <label>Start Row</label>
                <input type="number" v-model.number="config.start_row" class="form-control" min="1" />
              </div>
            </div>
            
            <div class="form-group">
              <label>Фестиваль</label>
              <select v-model="config.filters.festival_id" class="form-control">
                <option :value="null">Все фестивали</option>
                <option v-for="fest in festivals" :key="fest.id" :value="fest.id">
                  {{ fest.name }} {{ fest.year }}
                </option>
              </select>
            </div>
            
            <div class="form-group">
              <label>Лимит строк (оставьте пустым для всех)</label>
              <input type="number" v-model.number="config.filters.limit" class="form-control" placeholder="Без лимита" />
            </div>
            
            <div class="form-group">
              <label>Расписание (Cron) *</label>
              <input 
                v-model="config.cron_expression" 
                class="form-control" 
                required 
                placeholder="0 2 * * *"
              />
              <small class="form-text text-muted">Формат: минута час день месяц день-недели</small>
              <div class="mt-2">
                <button type="button" class="btn btn-sm btn-outline-secondary mr-2" @click="setCron('0 2 * * *')">Ежедневно в 2:00</button>
                <button type="button" class="btn btn-sm btn-outline-secondary mr-2" @click="setCron('0 9 * * 1')">Понедельник в 9:00</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="setCron('0 */6 * * *')">Каждые 6 часов</button>
              </div>
            </div>
            
            <div class="form-group">
              <label>Часовой пояс *</label>
              <select v-model="config.timezone" class="form-control">
                <option value="Europe/Moscow">Москва (UTC+3)</option>
                <option value="Europe/Samara">Самара (UTC+4)</option>
                <option value="Asia/Yekaterinburg">Екатеринбург (UTC+5)</option>
                <option value="Asia/Omsk">Омск (UTC+6)</option>
                <option value="Asia/Krasnoyarsk">Красноярск (UTC+7)</option>
                <option value="Asia/Irkutsk">Иркутск (UTC+8)</option>
                <option value="Asia/Yakutsk">Якутск (UTC+9)</option>
                <option value="Asia/Vladivostok">Владивосток (UTC+10)</option>
                <option value="Asia/Magadan">Магадан (UTC+11)</option>
                <option value="Asia/Kamchatka">Камчатка (UTC+12)</option>
                <option value="UTC">UTC</option>
              </select>
            </div>
            
            <div class="form-group form-check">
              <input type="checkbox" v-model="config.is_active" class="form-check-input" id="isActiveCheck" />
              <label class="form-check-label" for="isActiveCheck">Активна</label>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="close">Отмена</button>
          <button type="button" class="btn btn-primary" @click="saveConfig">Сохранить</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-backdrop fade show" v-if="modelValue"></div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

export default {
  name: 'ExportModal',
  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['update:modelValue'],
  data() {
    return {
      config: {
        name: '',
        report_type: 'friendly_summary',
        spreadsheet_id: '',
        sheet_name: 'Sheet1',
        start_row: 1,
        filters: {
          festival_id: null,
          limit: null,
        },
        cron_expression: '0 2 * * *',
        timezone: 'Europe/Moscow',
        is_active: true,
      }
    }
  },
  computed: {
    ...mapGetters('appFestival', ['getFestivals']),
    festivals() {
      return this.getFestivals;
    },
    displayStyle() {
      return this.modelValue ? { display: 'block' } : { display: 'none' };
    }
  },
  methods: {
    ...mapActions('appReport', ['saveConfig', 'loadConfigs']),
    
    setCron(expression) {
      this.config.cron_expression = expression;
    },
    
    async saveConfig() {
      try {
        await this.saveConfig({ ...this.config });
        alert('Конфигурация сохранена!');
        this.close();
        this.loadConfigs();
      } catch (error) {
        alert('Ошибка при сохранении: ' + error);
      }
    },
    
    close() {
      this.$emit('update:modelValue', false);
      this.resetConfig();
    },
    
    resetConfig() {
      this.config = {
        name: '',
        report_type: 'friendly_summary',
        spreadsheet_id: '',
        sheet_name: 'Sheet1',
        start_row: 1,
        filters: {
          festival_id: null,
          limit: null,
        },
        cron_expression: '0 2 * * *',
        timezone: 'Europe/Moscow',
        is_active: true,
      };
    }
  }
}
</script>

<style scoped>
</style>
