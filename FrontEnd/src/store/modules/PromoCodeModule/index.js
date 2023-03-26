import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

export default {
    namespaced: true,
    state: {
        dataError: [],
        promoCodeList: [],
        promoCodeItem: {
            id: null,
            name: null,
            discount: 0.00,
            isPercent: null,
            isSuccess: false,
            limit: {
                count: 0,
                limit: null,
            },
        },
    },
    getters,
    actions,
    mutations
};
