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
            email: null,
            phone: null,
            vk: null,
            howManyTimes: null,
            musicStyles: null,
            questionForSysto: null,
            is_have_in_club: false,
            message: '',
            link: null,
        },
        isLoading: false,
        dataError: [],
        message: null,
    },
    getters,
    actions,
    mutations
};
