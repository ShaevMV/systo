<template>
  <div class="title-block text-center">
    <h1 class="card-title">Заказы кураторов</h1>
  </div>
  <div class="row">
    <div class="col-lg-12 mx-auto" id="filter-results">
      <div class="card">
        <div class="card-body">
          <table class="table table-hover">
            <thead>
            <tr>
              <th scope="col" class="mobile">№ заказа</th>
              <th scope="col" class="mobile"></th>
              <th scope="col">Куратор</th>
              <th scope="col">Email куратора</th>
              <th scope="col">Проект</th>
              <th scope="col">Участники</th>
              <th scope="col">Тип билета</th>
              <th scope="col" class="mobile">Статус</th>
              <th scope="col" class="mobile">Кол-во</th>
              <th scope="col" class="mobile"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(itemOrder, index) in getOrderList"
                v-bind:key="index">
              <th scope="row" class="mobile">{{ itemOrder.kilter }}</th>
              <td class="mobile">
                <div class="btn-group"
                     v-show="Object.keys(itemOrder.listCorrectNextStatus).length > 0">
                  <button type="button"
                          class="btn btn-danger dropdown-toggle"
                          data-toggle="dropdown"
                          :style="{ 'background-color': statusColor(itemOrder.status) }"
                          aria-haspopup="true"
                          aria-expanded="false">...</button>
                  <div class="dropdown-menu">
                    <span class="dropdown-item btn-link"
                          role="button"
                          v-for="(statusItem, key) in itemOrder.listCorrectNextStatus"
                          v-bind:key="key"
                          @click="changeStatus(key, itemOrder)">{{ statusItem }}</span>
                  </div>
                </div>
              </td>
              <td>{{ itemOrder.pusher_name }}</td>
              <td>{{ itemOrder.pusher_email }}</td>
              <td>{{ itemOrder.project || '—' }}</td>
              <td :title="guestNames(itemOrder.guests, true)" class="mobile">
                {{ guestNames(itemOrder.guests, false) }}
              </td>
              <td>{{ itemOrder.name }}</td>
              <td class="mobile" :style="{ color: statusColor(itemOrder.status) }">
                {{ itemOrder.humanStatus }}
              </td>
              <td class="mobile">{{ itemOrder.count }}</td>
              <td class="mobile">
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

    <!-- Модалка комментария для difficulties_arose_curator -->
    <div class="modal fade" id="curatorCommentModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Комментарий к трудностям</h5>
            <button type="button" class="close" data-dismiss="modal" id="closeCuratorModal">
              <span>×</span>
            </button>
          </div>
          <div class="modal-body">
            <textarea class="form-control" v-model="comment"></textarea>
            <small class="form-text text-muted">{{ getError('comment') }}</small>
          </div>
          <div class="modal-footer">
            <button type="button" @click="sendWithComment" class="btn btn-secondary">
              Сменить статус
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {mapActions, mapGetters} from 'vuex';

export default {
  name: "OrderListCurator",
  data() {
    return {
      comment: null,
      selectId: null,
      selectStatus: null,
    };
  },
  computed: {
    ...mapGetters('appOrder', ['getOrderList', 'getError']),
  },
  methods: {
    ...mapActions('appOrder', ['sendToChangeStatus']),
    statusColor(status) {
      const colors = {
        new_for_list: '#333333',
        pending_curator: '#1a6cc9',
        paid: '#1e871c',
        cancel: '#86201c',
        difficulties_arose_curator: '#d0ba27',
        difficulties_arose: '#d0ba27',
      };
      return colors[status] ?? '#333333';
    },
    guestNames(guests, all) {
      const max = all ? guests.length : 3;
      return guests.slice(0, max).map(g => g.value).join(', ');
    },
    changeStatus(status, itemOrder) {
      this.selectId = itemOrder.id;
      this.selectStatus = status;

      if (status === 'difficulties_arose_curator') {
        const modalEl = document.getElementById('curatorCommentModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
      } else {
        this.sendToChangeStatus({
          id: itemOrder.id,
          status,
          comment: null,
        });
      }
    },
    sendWithComment() {
      this.sendToChangeStatus({
        id: this.selectId,
        status: this.selectStatus,
        comment: this.comment,
        callback: () => {
          document.getElementById('closeCuratorModal')?.click();
          this.selectId = null;
          this.selectStatus = null;
          this.comment = null;
        },
      });
    },
  },
};
</script>
