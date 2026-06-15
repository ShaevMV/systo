import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

// Редактор шаблонов писем/PDF (AF-3). CRUD + черновик/публикация + версии + превью.
// Бэкенд: /api/v1/template/* (admin-only).
export default {
    namespaced: true,
    state: {
        list: [],
        item: {},
        versions: [],
        history: [],
        variables: [],
        filter: {},
        isLoading: false,
        dataError: []
    },
    getters,
    actions,
    mutations
};
