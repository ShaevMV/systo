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
            is_percent: null,
            action: false,
            limit: null,
        },
    },
    getters,
    actions,
    mutations
};
