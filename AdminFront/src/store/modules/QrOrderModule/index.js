import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

// Модуль qr-заказов админки — READ-ONLY. Заказы создаёт/меняет статус витрина qr (S2S),
// здесь только просмотр: список (server-side пагинация), деталь и история.
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
