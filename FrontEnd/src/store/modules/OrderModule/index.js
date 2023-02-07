import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

export default {
    namespaced: true,
    state: {
        dataError: [],
        orderList: [],
        totalNumber: {
            totalAmount: 0,
            totalCount: 0,
            totalCountToPaid: 0,
        },
        orderItem: {
            name: null,
            count: 0,
            kilter: null,
            price: 0.00,
            guests: [],
            totalPrice: 0,
            discount: null,
            typeOfPaymentName: null,
            humanStatus: null,
            dateBuy: null,
            idBuy: null,
            email: null,
            lastComment: null,
            listCorrectNextStatus: [],
        },
    },
    getters,
    actions,
    mutations
};
