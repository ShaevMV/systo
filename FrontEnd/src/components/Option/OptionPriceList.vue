<template>
  <div class="card mt-3">
    <div class="card-body">
      <h5 class="card-title">Волны цен опции</h5>
      <p class="text-muted small">
        Актуальной считается ближайшая по дате <code>before_date</code>, у которой дата ≥ сегодня.
        Цена в рублях, целое число.
      </p>

      <table class="table table-sm">
        <thead>
        <tr>
          <th>Цена (₽)</th>
          <th>Действует до</th>
          <th></th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="row in getList" :key="row.id">
          <td><input type="number" min="1" max="999999" class="form-control form-control-sm" v-model.number="row.price"></td>
          <td><input type="date" class="form-control form-control-sm" v-model="row.before_date"></td>
          <td class="text-end">
            <button class="btn btn-sm btn-primary me-1" :disabled="!isValid(row)" @click="save(row)">💾</button>
            <button class="btn btn-sm btn-danger" @click="localRemove(row.id)">🗑️</button>
          </td>
        </tr>
        <tr v-if="!getList || !getList.length">
          <td colspan="3" class="text-muted text-center">Волн нет. Добавьте первую.</td>
        </tr>
        </tbody>
      </table>

      <div class="card bg-light p-3 mt-2">
        <h6 class="mb-2">Новая волна</h6>
        <div class="row g-2">
          <div class="col-md-4">
            <input type="number" min="1" max="999999" class="form-control" placeholder="Цена в рублях" v-model.number="newWave.price">
          </div>
          <div class="col-md-4">
            <input type="date" class="form-control" v-model="newWave.before_date" :min="todayStr">
          </div>
          <div class="col-md-4">
            <button class="btn btn-success w-100" :disabled="!isValid(newWave)" @click="createNew">+ Добавить волну</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

export default {
  name: 'OptionPriceList',
  props: {
    optionId: { type: String, required: true },
  },
  data() {
    return {
      newWave: { price: null, before_date: null },
    };
  },
  computed: {
    ...mapGetters('appOptionPrice', ['getList']),
    todayStr() {
      const d = new Date();
      return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    },
  },
  methods: {
    ...mapActions('appOptionPrice', ['loadList', 'create', 'edit', 'remove']),
    isValid(row) {
      const okPrice = row.price && row.price > 0 && row.price < 1000000;
      const okDate = row.before_date && row.before_date >= this.todayStr;
      return okPrice && okDate;
    },
    async save(row) {
      await this.edit({
        id: row.id,
        data: {
          option_id: this.optionId,
          price: row.price,
          before_date: row.before_date,
        },
      });
      await this.refresh();
    },
    async createNew() {
      await this.create({
        data: {
          option_id: this.optionId,
          price: this.newWave.price,
          before_date: this.newWave.before_date,
        },
      });
      this.newWave = { price: null, before_date: null };
      await this.refresh();
    },
    async localRemove(id) {
      if (!confirm('Удалить волну цены?')) return;
      await this.remove({ id });
      await this.refresh();
    },
    refresh() {
      return this.loadList({ filter: { option_id: this.optionId } });
    },
  },
  async created() {
    await this.refresh();
  },
};
</script>

<style scoped>
</style>
