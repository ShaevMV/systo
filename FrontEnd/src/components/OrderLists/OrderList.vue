<template>
  <div class="title-block text-center">
    <h1 class="card-title">{{ titleText }}</h1>
  </div>
  <div class="row">
    <div class="col-lg-12 mx-auto" id="filter-results">
      <div class="card">
        <div class="card-body">
          <p>Заказы-списки. Куратор создаёт список гостей на локацию,
            администратор/менеджер одобряет — после этого получатель получает PDF-билеты, гостям приходят анкеты.</p>
          <table class="table table-hover">
            <thead>
            <tr>
              <th scope="col">№</th>
              <th scope="col"></th>
              <th scope="col">Email куратора</th>
              <th scope="col">ФИО куратора</th>
              <th scope="col">Email получателя</th>
              <th scope="col">Локация</th>
              <th scope="col">Гости</th>
              <th scope="col">Кол-во</th>
              <th scope="col">Статус</th>
              <th scope="col"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(itemOrder, index) in getOrderList" v-bind:key="index">
              <th scope="row" @click="goItem(itemOrder.id)" style="cursor: pointer">
                {{ itemOrder.kilter }}
              </th>
              <td>
                <div class="btn-group" v-show="canChangeStatus && Object.keys(itemOrder.listCorrectNextStatus || {}).length > 0">
                  <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown"
                          :style="{ 'background-color': activeColor(itemOrder.status) }">
                    ...
                  </button>
                  <div class="dropdown-menu">
                    <span class="dropdown-item btn-link" role="button"
                          v-for="(label, key) in itemOrder.listCorrectNextStatus" v-bind:key="key"
                          @click="chanceStatus(key, itemOrder)">{{ label }}</span>
                  </div>
                </div>
              </td>
              <td>{{ itemOrder.curator_email }}</td>
              <td>{{ itemOrder.curator_name }}</td>
              <td>{{ itemOrder.email }}</td>
              <td>{{ itemOrder.location_name }}</td>
              <td :title="getListGuestsTitle(itemOrder.guests)">
                {{ getListGuestsShort(itemOrder.guests) }}
              </td>
              <td>{{ itemOrder.count }}</td>
              <td :style="styleObject(itemOrder.status)">{{ itemOrder.humanStatus }}</td>
              <td>
                <router-link :to="{ name: 'orderItems', params: { id: itemOrder.id } }">
                  Открыть
                </router-link>
              </td>
            </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="modal fade" id="listsCommentModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Комментарий получателю</h5>
            <button type="button" class="close" data-dismiss="modal" id="closeListsModal">
              <span aria-hidden="true">Х</span>
            </button>
          </div>
          <div class="modal-body">
            <textarea class="form-control" v-model="comment"></textarea>
            <small class="form-text text-muted">{{ getError('comment') }}</small>
          </div>
          <div class="modal-footer">
            <button type="button" @click="sendDifficulties" class="btn btn-secondary">Сменить статус</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';

export default {
  name: 'OrderListsList',
  props: {
    title: { type: String, default: 'Заказы-списки' },
    canChangeStatus: { type: Boolean, default: true },
  },
  data() {
    return {
      comment: null,
      selectId: null,
      selectStatus: null,
    };
  },
  computed: {
    ...mapGetters('appOrder', ['getOrderList', 'getError']),
    titleText() {
      return this.title;
    },
  },
  methods: {
    ...mapActions('appOrder', ['sendToChangeStatus']),
    styleObject(status) {
      return { color: this.activeColor(status) };
    },
    activeColor(status) {
      switch (status) {
        case 'new_list': return '#333333';
        case 'approve_list': return '#1e871c';
        case 'cancel_list': return '#86201c';
        case 'difficulties_arose_list': return '#d0ba27';
        default: return '#888';
      }
    },
    getListGuestsShort(guests) {
      if (!guests) return '';
      return guests.slice(0, 3).map((g) => g.value).join(', ') + (guests.length > 3 ? '...' : '');
    },
    getListGuestsTitle(guests) {
      if (!guests) return '';
      return guests.map((g) => g.value).join(', ');
    },
    goItem(id) {
      this.$router.push({ name: 'orderItems', params: { id } });
    },
    chanceStatus(status, itemOrder) {
      this.selectId = itemOrder.id;
      this.selectStatus = status;
      if (status === 'difficulties_arose_list') {
        const modalEl = document.getElementById('listsCommentModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
      } else {
        this.sendToChangeStatus({
          id: itemOrder.id,
          status: status,
          comment: null,
        });
      }
    },
    sendDifficulties() {
      const self = this;
      this.sendToChangeStatus({
        id: this.selectId,
        status: this.selectStatus,
        comment: this.comment,
        callback() {
          document.getElementById('closeListsModal')?.click();
          self.selectId = null;
          self.selectStatus = null;
          self.comment = null;
        },
      });
    },
  },
};
</script>

<style scoped>
</style>
