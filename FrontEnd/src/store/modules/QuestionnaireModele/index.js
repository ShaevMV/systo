import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

export default {
    namespaced: true,
    state: {
        questionnaireList: [],
        questionnaireItem: {
            agy: null,
            telegram: null,
            phone: null,
            vk: null,
            howManyTimes: null,
            musicStyles: null,
            questionForSysto: null,
            message: '',
            link: null,
        },
        isLoading: false,
        dataError: [],
    },
    getters,
    actions,
    mutations
};
