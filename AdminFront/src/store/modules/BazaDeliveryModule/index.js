import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

// Модуль «Доставка в baza» (AF-4) — admin-only. Список статусов доставки билетов в Baza
// (server-side пагинация), деталь с таймлайном (domain_history) и ручной повтор застрявшей
// доставки. Запись в Baza выполняет бэкенд (BazaDeliveryDispatcher/DeliverTicketToBazaJob);
// здесь только просмотр пути доставки, повтор и статистика для дашборда.
export default {
    namespaced: true,
    state: {
        list: [],
        item: {},
        history: [],
        filter: {},
        orderBy: { created_at: 'desc' },
        pagination: { page: 1, perPage: 20, total: 0 },
        stats: { queued: 0, sending: 0, delivered: 0, failed: 0, stuck: 0 },
        isLoading: false,
        dataError: []
    },
    getters,
    actions,
    mutations
};
