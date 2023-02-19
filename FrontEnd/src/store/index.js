import {createStore} from 'vuex'
import appFestivalTickets from './modules/FestivalTicketsModule/index';
import appUserModule from './modules/UserModule/index';
import appOrderModule from './modules/OrderModule/index';

export default createStore({
    state: {
        showMenu: false,
    },
    getters: {
        isShowMenu: state =>{
            return state.showMenu;
        },
    },
    mutations: {
        SHOW_MENU: state =>{
            state.showMenu = true;
        },
        HIDE_MENU: state =>{
            state.showMenu = false;
        },
        TOGGLE_MENU: state => {
            state.showMenu = !state.showMenu;
        },
    },
    actions: {},
    modules: {
        'appFestivalTickets': appFestivalTickets,
        'appUser': appUserModule,
        'appOrder': appOrderModule,
    }
})
