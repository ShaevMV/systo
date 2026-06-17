import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

// Модуль «Доставка писем» (Ф2) — admin-only. Список статусов писем (server-side пагинация),
// деталь с таймлайном (domain_history) и повторная отправка. Отправку выполняет бэкенд
// (MailDispatcher/SendEmailJob); здесь только просмотр пути письма и повтор.
export default {
    namespaced: true,
    state: {
        list: [],
        item: {},
        history: [],
        filter: {},
        orderBy: { created_at: 'desc' },
        pagination: { page: 1, perPage: 20, total: 0 },
        isLoading: false,
        dataError: []
    },
    getters,
    actions,
    mutations
};
