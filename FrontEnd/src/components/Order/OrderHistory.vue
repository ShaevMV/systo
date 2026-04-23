<template>
  <div class="mt-4">
    <h5 class="mb-3">История изменений заказа</h5>
    <div v-if="history.length === 0" class="text-muted">История пуста</div>
    <table v-else class="table table-sm table-bordered">
      <thead class="thead-light">
      <tr>
        <th>Событие</th>
        <th>Было</th>
        <th>Стало</th>
        <th>Дата</th>
        <th>Кто совершил</th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="(item, index) in history" :key="index">
        <td>{{ eventLabel(item.event_name) }}</td>
        <td>{{ fromValue(item) }}</td>
        <td>{{ toValue(item) }}</td>
        <td>{{ formatDate(item.occurred_at) }}</td>
        <td>{{ actorLabel(item) }}</td>
      </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import {mapGetters} from "vuex";

const EVENT_LABELS = {
  'order_created':        'Заказ создан',
  'status_changed':       'Смена статуса',
  'ticket_data_changed':  'Изменение данных билета',
};

const STATUS_LABELS = {
  'new':                'Новый',
  'new_for_live':       'Новый (живой)',
  'paid':               'Оплачен',
  'paid_for_live':      'Оплачен (живой)',
  'cancel':             'Отменён',
  'cancel_for_live':    'Отменён (живой)',
  'difficulties_arose': 'Возникли трудности',
  'live_ticket_issued': 'Живой билет выдан',
};

const ACTOR_LABELS = {
  'system':  'Система',
  'artisan': 'Artisan',
};

export default {
  name: 'OrderHistory',

  props: {
    orderId: {
      type: String,
      required: true,
    },
  },

  computed: {
    ...mapGetters('appOrder', ['getOrderHistory']),
    history() {
      return this.getOrderHistory;
    },
  },

  methods: {
    eventLabel(eventName) {
      return EVENT_LABELS[eventName] ?? eventName;
    },

    fromValue(item) {
      const p = item.payload;
      if (item.event_name === 'status_changed') {
        return STATUS_LABELS[p.from] ?? p.from ?? '—';
      }
      if (item.event_name === 'ticket_data_changed' && Array.isArray(p.changes)) {
        return p.changes.map(c => c.oldName).join(', ');
      }
      if (item.event_name === 'order_created') {
        return '—';
      }
      return '—';
    },

    toValue(item) {
      const p = item.payload;
      if (item.event_name === 'status_changed') {
        return STATUS_LABELS[p.to] ?? p.to ?? '—';
      }
      if (item.event_name === 'ticket_data_changed' && Array.isArray(p.changes)) {
        return p.changes.map(c => c.newName).join(', ');
      }
      if (item.event_name === 'order_created') {
        return `Создан: ${p.kilter ?? ''}, ${p.price ?? ''} ₽`;
      }
      return '—';
    },

    formatDate(iso) {
      if (!iso) return '—';
      return new Date(iso).toLocaleString('ru-RU', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
      });
    },

    actorLabel(item) {
      if (item.actor_type in ACTOR_LABELS) {
        return ACTOR_LABELS[item.actor_type];
      }
      return item.actor_id ?? 'Пользователь';
    },
  },

  created() {
    this.$store.dispatch('appOrder/loadOrderHistory', {id: this.orderId});
  },
};
</script>
