<template>
  <div class="ticket-type-price-list mt-4">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title">Волны цен</h4>
        <p class="text-muted mb-3">
          Цена билета меняется по волнам. Активной считается ближайшая волна,
          у которой <code>before_date</code> &ge; сегодня. После прохождения даты
          включается следующая волна.
        </p>

        <div v-if="!ticketTypeId" class="alert alert-info mb-0">
          Сначала сохраните тип билета — после создания появится возможность задавать волны цен.
        </div>

        <template v-else>
          <table class="table table-sm align-middle" v-if="list && list.length">
            <thead>
              <tr>
                <th style="width: 30%">Цена, ₽</th>
                <th style="width: 40%">Действует до (включительно)</th>
                <th style="width: 30%" class="text-end">Действия</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in list" :key="row.id">
                <td>{{ formatPrice(row.price) }}</td>
                <td :class="{'text-danger': isPast(row.before_date)}">
                  {{ formatDate(row.before_date) }}
                  <span v-if="isPast(row.before_date)" class="badge bg-secondary ms-1">завершена</span>
                </td>
                <td class="text-end">
                  <button type="button"
                          class="btn btn-sm btn-outline-primary me-2"
                          @click="startEdit(row)">
                    Изменить
                  </button>
                  <button type="button"
                          class="btn btn-sm btn-outline-danger"
                          @click="remove(row)">
                    Удалить
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-else class="alert alert-warning">
            Волн цен ещё нет. Добавьте первую — она будет использоваться, пока не наступит её дата.
          </div>

          <hr/>

          <h5>{{ isEditing ? 'Редактирование волны' : 'Новая волна' }}</h5>
          <div class="row g-2 align-items-start">
            <div class="col-md-4">
              <label class="form-label">Цена, ₽</label>
              <input type="number"
                     min="1"
                     max="999999"
                     step="1"
                     class="form-control"
                     :class="{'is-invalid': errorOf('price')}"
                     v-model.number="form.price"/>
              <div class="invalid-feedback">{{ errorOf('price') }}</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Действует до</label>
              <input type="date"
                     class="form-control"
                     :class="{'is-invalid': errorOf('before_date')}"
                     :min="todayIso"
                     v-model="form.before_date"/>
              <div class="invalid-feedback">{{ errorOf('before_date') }}</div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <button type="button"
                      class="btn btn-primary me-2"
                      :disabled="!isFormValid"
                      @click="save">
                {{ isEditing ? 'Сохранить' : 'Добавить волну' }}
              </button>
              <button v-if="isEditing"
                      type="button"
                      class="btn btn-link"
                      @click="resetForm">Отмена</button>
            </div>
          </div>
          <div v-if="message" class="alert alert-success mt-3 py-2 px-3 mb-0">
            {{ message }}
          </div>
          <div v-if="errorOf('ticket_type_id')" class="alert alert-danger mt-3 py-2 px-3 mb-0">
            {{ errorOf('ticket_type_id') }}
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
import {mapActions, mapGetters} from "vuex";

const MAX_PRICE = 999999;

export default {
  name: "TicketTypePriceList",
  props: {
    ticketTypeId: {
      type: [String],
      default: null,
    }
  },
  data() {
    return {
      form: this.emptyForm(),
      editingId: null,
    }
  },
  computed: {
    ...mapGetters('appTicketTypePrice', [
      'getList',
      'getError',
      'getMessage',
    ]),
    list() {
      return this.getList || [];
    },
    message() {
      return this.getMessage;
    },
    isEditing() {
      return this.editingId !== null;
    },
    todayIso() {
      const d = new Date();
      const m = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${d.getFullYear()}-${m}-${day}`;
    },
    isFormValid() {
      const price = Number(this.form.price);
      if (!(price > 0) || price >= MAX_PRICE + 1) return false;
      if (!this.form.before_date) return false;
      return !this.isPast(this.form.before_date);
    },
  },
  watch: {
    ticketTypeId: {
      immediate: true,
      handler(id) {
        if (id) {
          this.reload();
        }
      }
    }
  },
  methods: {
    ...mapActions('appTicketTypePrice', [
      'loadList',
      'create',
      'edit',
      'remove',
      'clearError',
      'clearMessage',
    ]),
    emptyForm() {
      return {
        price: null,
        before_date: '',
      };
    },
    errorOf(field) {
      const e = this.getError;
      return e ? e('data.' + field) || e(field) : '';
    },
    formatPrice(value) {
      return Number(value).toLocaleString('ru-RU');
    },
    formatDate(value) {
      if (!value) return '';
      // value может быть ISO либо 'YYYY-MM-DD HH:mm:ss'
      const d = new Date(value);
      if (isNaN(d.getTime())) return value;
      return d.toLocaleDateString('ru-RU');
    },
    isPast(value) {
      if (!value) return false;
      const d = new Date(value);
      if (isNaN(d.getTime())) return false;
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      d.setHours(0, 0, 0, 0);
      return d.getTime() < today.getTime();
    },
    reload() {
      this.clearError();
      this.loadList({filter: {ticket_type_id: this.ticketTypeId}});
    },
    startEdit(row) {
      this.clearError();
      this.editingId = row.id;
      this.form = {
        price: Number(row.price),
        before_date: this.toInputDate(row.before_date),
      };
    },
    toInputDate(value) {
      if (!value) return '';
      const d = new Date(value);
      if (isNaN(d.getTime())) return '';
      const m = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${d.getFullYear()}-${m}-${day}`;
    },
    resetForm() {
      this.form = this.emptyForm();
      this.editingId = null;
      this.clearError();
    },
    save() {
      if (!this.isFormValid) return;
      const payload = {
        ticket_type_id: this.ticketTypeId,
        price: Number(this.form.price),
        before_date: this.form.before_date,
      };
      const onDone = () => {
        this.resetForm();
        this.reload();
      };
      if (this.isEditing) {
        this.edit({id: this.editingId, data: payload}).then(onDone).catch(() => {});
      } else {
        this.create({data: payload}).then(onDone).catch(() => {});
      }
    },
    remove(row) {
      // Защита от дурака: подтверждение перед удалением
      if (!window.confirm('Удалить волну с ценой ' + this.formatPrice(row.price) + ' ₽?')) {
        return;
      }
      this.$store.dispatch('appTicketTypePrice/remove', {id: row.id})
          .then(() => this.reload())
          .catch(() => {});
    }
  }
}
</script>

<style scoped>
.ticket-type-price-list code {
  background: #f5f5f5;
  padding: 0 4px;
  border-radius: 3px;
}
</style>
