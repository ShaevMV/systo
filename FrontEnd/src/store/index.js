import {createStore} from 'vuex'
import appFestivalTickets from './modules/FestivalTicketsModule/index';
import appUserModule from './modules/UserModule/index';
import appOrderModule from './modules/OrderModule/index';

export default createStore({
    state: {},
    getters: {},
    mutations: {},
    actions: {},
    modules: {
        'appFestivalTickets': appFestivalTickets,
        'appUser': appUserModule,
        'appOrder': appOrderModule,
    }
})
