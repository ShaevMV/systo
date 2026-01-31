import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

export default {
    namespaced: true,
    state: {
        typesOfPayment: [],
        festivalList: [],
        selectTypeOfPayment: null,
        ticketType: [],
        selectTicketType: {
            id: null,
            groupLimit: null,
            description: null,
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
