import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

export default {
    namespaced: true,
    state: {
        typesOfPayment: [],
        selectTypeOfPayment: null,
        ticketType: [],
        selectTicketType: {
            id: null,
            groupLimit: null,
        },
        promoCode: {
            discount: null,
            name: null,
            success: false,
        },
        dataError: [],
    },
    getters,
    actions,
    mutations
};
