import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

export default {
    namespaced: true,
    state: {
        list: [],
        item: {},
        filter: {},
        orderBy: {},
        isLoading: false,
        dataError: {},
        message: null,
    },
    getters,
    actions,
    mutations
};
