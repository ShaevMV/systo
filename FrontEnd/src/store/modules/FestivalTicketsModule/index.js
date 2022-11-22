import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

export default {
    namespaced: true,
    state: {
        typesOfPayment: [],
        selectTypeOfPayment: null,
        ticketType: [],
        selectTicketType: null,
        promoCode: null,
    },
    getters,
    actions,
    mutations
};
