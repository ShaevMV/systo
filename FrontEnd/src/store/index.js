import {createStore} from 'vuex'
import appFestivalTickets from './modules/FestivalTicketsModule/index';
import appUserModule from './modules/UserModule/index';

export default createStore({
    state: {},
    getters: {},
    mutations: {},
    actions: {},
    modules: {
        'appFestivalTickets': appFestivalTickets,
        'appUser': appUserModule,
    }
})
