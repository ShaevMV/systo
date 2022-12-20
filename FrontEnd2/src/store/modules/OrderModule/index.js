import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

export default {
    namespaced: true,
    state: {
        dataError: [],
        orderList: [],
        orderItem: {
            name: null,
            count: 0,
            guests: [],
            totalPrice: 0,
            discount: null,
            typeOfPayment: null,
            humanStatus: null,
            dateBuy: null,
            comment: [],
        },
    },
    getters,
    actions,
    mutations
};
