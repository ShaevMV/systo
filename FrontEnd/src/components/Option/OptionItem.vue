<template>
  <div class="container-fluid">
    <div class="title-block text-center">
      <h1 class="card-title"> {{ isEdit ? 'Редактирование опции' : 'Создание опции' }} </h1>
    </div>
    <div class="row">
      <div class="col-lg-12 mx-auto">

        <div class="card">
          <div class="card-body">
            <div class="row mb-3">
              <label class="col-4 col-form-label">Название опции:</label>
              <div class="col-8">
                <input type="text" class="form-control" v-model="name" placeholder="Например: Саженец">
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-4 col-form-label">Фестиваль:</label>
              <div class="col-8">
                <select class="form-control" v-model="festivalId">
                  <option value="" disabled>— выберите фестиваль —</option>
                  <option v-for="f in getFestivalList" :key="f.id" :value="f.id">
                    {{ f.name }} {{ f.year }}
                  </option>
                </select>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-4 col-form-label">Активность:</label>
              <div class="col-8">
                <select class="form-select" v-model="active">
                  <option :value="true">Активна (видна гостям)</option>
                  <option :value="false">Скрыта</option>
                </select>
                <small class="form-text text-muted">
                  Скрытая опция не появится в форме покупки, но уже оплаченные заказы её сохранят.
                </small>
              </div>
            </div>

            <hr class="my-4">

            <h5>Привязка к типам билетов</h5>
            <p class="text-muted small">
              Отметьте типы билетов, к которым эта опция применима. Для каждой связки задайте
              своё <strong>описание</strong> — гость увидит его в форме покупки.
            </p>

            <div class="card mb-3" v-for="tt in availableTicketTypes" :key="tt.id">
              <div class="card-body py-2">
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    :id="'tt_' + tt.id"
                    :checked="isBound(tt.id)"
                    @change="toggleBinding(tt.id, $event.target.checked)"
                  >
                  <label class="form-check-label fw-bold" :for="'tt_' + tt.id">
                    {{ tt.name }}
                  </label>
                </div>
                <div v-if="isBound(tt.id)" class="mt-2">
                  <label class="form-label small">Описание для этого типа билета:</label>
                  <textarea
                    class="form-control form-control-sm"
                    rows="2"
                    :value="getDescription(tt.id)"
                    @input="setDescription(tt.id, $event.target.value)"
                    placeholder="Например: Один саженец местного питомника в подарок"
                  ></textarea>
                </div>
              </div>
            </div>
            <div v-if="!availableTicketTypes.length" class="alert alert-warning">
              Нет типов билетов для выбранного фестиваля. Сначала создайте типы билетов.
            </div>

            <hr class="my-4">
            <div class="row messager">{{ getMessage }}</div>
            <div class="row b-row mt-2">
              <button type="submit" @click="save" class="btn btn-primary" :disabled="!canSave">Сохранить</button>
              <button type="button" @click="back" class="btn btn-secondary ms-2">Отмена/назад</button>
            </div>
          </div>
        </div>

        <OptionPriceList v-if="isEdit" :option-id="id" />
        <div v-else class="alert alert-info mt-3">
          Волны цен доступны после сохранения опции.
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import OptionPriceList from '@/components/Option/OptionPriceList.vue';

export default {
  name: 'OptionItem',
  components: { OptionPriceList },
  props: {
    id: { type: String, default: null },
  },
  data() {
    return {
      newName: null,
      newFestivalId: null,
      newActive: null,
      bindings: {},
    };
  },
  computed: {
    ...mapGetters('appOption', ['getItem', 'getMessage']),
    ...mapGetters('appFestivalTickets', ['getFestivalList']),
    ...mapGetters('appTicketType', { getTicketTypeList: 'getList' }),
    isEdit() {
      return this.id !== null && this.id !== undefined && this.id !== '';
    },
    canSave() {
      return !!this.name && !!this.festivalId;
    },
    availableTicketTypes() {
      const list = this.getTicketTypeList || [];
      if (!this.festivalId) return list;
      return list.filter((t) => !t.festivalList || t.festivalList.some((f) => f.id === this.festivalId));
    },
    name: {
      get() { return this.newName ?? this.getItem.name ?? ''; },
      set(v) { this.newName = v; },
    },
    festivalId: {
      get() { return this.newFestivalId ?? this.getItem.festival_id ?? ''; },
      set(v) { this.newFestivalId = v; },
    },
    active: {
      get() {
        if (this.newActive !== null) return this.newActive;
        if (this.getItem.active === undefined) return true;
        return !!this.getItem.active;
      },
      set(v) { this.newActive = v; },
    },
  },
  watch: {
    'getItem.id'() {
      this.rebuildBindings();
    },
  },
  methods: {
    ...mapActions('appOption', ['create', 'edit']),
    ...mapActions('appFestivalTickets', ['getListFestival']),
    ...mapActions('appTicketType', { loadTicketTypes: 'loadList' }),
    rebuildBindings() {
      const dict = {};
      const fromServer = this.getItem.bindings || [];
      fromServer.forEach((b) => {
        dict[b.ticket_type_id] = b.description ?? '';
      });
      this.bindings = dict;
    },
    isBound(ticketTypeId) {
      return Object.prototype.hasOwnProperty.call(this.bindings, ticketTypeId);
    },
    getDescription(ticketTypeId) {
      return this.bindings[ticketTypeId] ?? '';
    },
    toggleBinding(ticketTypeId, checked) {
      const next = { ...this.bindings };
      if (checked) {
        next[ticketTypeId] = next[ticketTypeId] ?? '';
      } else {
        delete next[ticketTypeId];
      }
      this.bindings = next;
    },
    setDescription(ticketTypeId, value) {
      this.bindings = { ...this.bindings, [ticketTypeId]: value };
    },
    async save() {
      const bindingsArray = Object.entries(this.bindings).map(([ticket_type_id, description]) => ({
        ticket_type_id,
        description: description || null,
      }));
      const data = {
        name: this.name,
        festival_id: this.festivalId,
        active: this.active,
        bindings: bindingsArray,
      };
      if (this.isEdit) {
        await this.edit({ id: this.id, data });
      } else {
        await this.create({ data });
      }
      this.back();
    },
    back() {
      this.$router.push({ name: 'OptionListView' });
    },
  },
  async created() {
    this.getListFestival();
    this.loadTicketTypes({ filter: {}, orderBy: {} });
    this.rebuildBindings();
  },
};
</script>

<style scoped>
</style>
