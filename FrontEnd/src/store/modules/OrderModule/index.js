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
            price: 0.00,
            guests: [],
            totalPrice: 0,
            discount: null,
            typeOfPaymentName: null,
            humanStatus: null,
            dateBuy: null,
            email: null,
            lastComment: null,
            listCorrectNextStatus: [],
        },
    },
    getters,
    actions,
    mutations
};
