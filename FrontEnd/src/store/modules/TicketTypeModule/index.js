import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

export default {
    namespaced: true,
    state: {
        list: [],
        item: {
            id: null,
            name: null,
            price: null,
            groupLimit: null,
            sort: null,
            active: null,
            is_live_ticket: null,
        },
        isLoading: false,
        dataError: [],
        message: null,
    },
    getters,
    actions,
    mutations
};
