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
        templateList: {},
        questionnaireTypeList: [],
        isLoading: false,
        dataError: [],
        message: null,
    },
    getters,
    actions,
    mutations
};
