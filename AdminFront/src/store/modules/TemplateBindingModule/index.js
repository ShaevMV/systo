import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

// Привязки шаблонов к (festival, order_type, ticket_type) → email/pdf шаблон + дефолт (AF-3, Часть B).
// Бэкенд: /api/v1/templateBinding/* (admin-only).
export default {
    namespaced: true,
    state: {
        list: [],
        festivals: [],
        ticketTypes: [],
        emailTemplates: [],
        pdfTemplates: [],
        isLoading: false
    },
    getters,
    actions,
    mutations
};
