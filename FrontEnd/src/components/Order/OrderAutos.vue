<template>
  <div class="card mt-3" v-if="canShow">
    <div class="card-body">
      <h5 class="card-title">🚗 Автомобили</h5>

      <div class="input-group mb-3" v-if="canEdit">
        <input
          type="text"
          class="form-control"
          placeholder="Номер автомобиля"
          v-model="newNumber"
          @keyup.enter="onAdd"
        />
        <div class="input-group-prepend">
          <button type="button"
                  class="btn btn-outline-primary"
                  :disabled="busy || !newNumber.trim()"
                  @click="onAdd">
            Добавить
          </button>
        </div>
      </div>

      <ul class="list-group" v-if="list.length > 0">
        <li class="list-group-item d-flex justify-content-between align-items-center"
            v-for="auto in list" :key="auto.id">
          <span>{{ auto.number }}</span>
          <button type="button"
                  v-if="canEdit"
                  class="btn btn-sm btn-outline-danger"
                  :disabled="busy"
                  @click="onRemove(auto.id)">
            🗑️
          </button>
        </li>
      </ul>
      <p class="text-muted mt-2 mb-0" v-else>Автомобилей нет</p>
    </div>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

export default {
  name: 'OrderAutos',
  props: {
    orderId: { type: String, required: true },
    curatorId: { type: String, default: null },
    autos: { type: Array, default: () => [] },
  },
  data() {
    return {
      list: [...this.autos],
      newNumber: '',
      busy: false,
    };
  },
  watch: {
    autos(next) {
      this.list = [...(next || [])];
    },
  },
  computed: {
    ...mapGetters('appUser', ['isAdmin', 'isCurator', 'isPusherCurator', 'getIdUser']),
    /**
     * Блок отображается только для заказов-списков (есть curator_id).
     */
    canShow() {
      return !!this.curatorId;
    },
    /**
     * Управлять авто может admin или куратор-создатель заказа.
     */
    canEdit() {
      if (this.isAdmin) return true;
      if ((this.isCurator || this.isPusherCurator) && this.getIdUser === this.curatorId) return true;
      return false;
    },
  },
  methods: {
    ...mapActions('appOrder', ['addAuto', 'removeAuto']),
    onAdd() {
      const number = (this.newNumber || '').trim();
      if (!number || this.busy) return;
      this.busy = true;
      this.addAuto({
        orderId: this.orderId,
        number,
        callback: (res) => {
          this.busy = false;
          if (res && res.success && res.auto) {
            this.list.push(res.auto);
            this.newNumber = '';
          }
        },
      });
    },
    onRemove(autoId) {
      if (this.busy) return;
      this.busy = true;
      this.removeAuto({
        orderId: this.orderId,
        autoId,
        callback: (res) => {
          this.busy = false;
          if (res && res.success) {
            this.list = this.list.filter((a) => a.id !== autoId);
          }
        },
      });
    },
  },
};
</script>

<style scoped>
.card-title { margin-bottom: 12px; }
</style>
