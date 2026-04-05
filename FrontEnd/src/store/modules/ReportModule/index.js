import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

export default {
    namespaced: true,
    state: {
        configs: [],
        isLoading: false,
        error: null,
    },
    getters,
    actions,
    mutations
};
