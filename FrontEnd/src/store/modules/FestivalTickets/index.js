import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

export default {
    namespaced: true,
    state: {
        typesOfPayment: [],
        ticketType: [],
    },
    getters,
    actions,
    mutations
};
