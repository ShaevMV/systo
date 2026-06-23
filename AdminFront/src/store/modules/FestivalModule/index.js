import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

// CRUD фестивалей + история изменений (AF-7). Бэкенд: /api/v1/festival/* (admin-only на write).
// Отдельный модуль от FestivalTicketsModule (тот — загрузка типов билетов/оплаты по фестивалю).
export default {
    namespaced: true,
    state: {
        list: [],
        item: {},
        history: [],
        isLoading: false,
        historyLoading: false,
        dataError: {}
    },
    getters,
    actions,
    mutations
};
