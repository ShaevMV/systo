import {createStore} from 'vuex'
import appFestivalTickets from './modules/FestivalTicketsModule/index';
import appUserModule from './modules/UserModule/index';
import appOrderModule from './modules/OrderModule/index';
import appPromoCodeModule from './modules/PromoCodeModule/index';
import appQuestionnaire from './modules/QuestionnaireModule/index';
import appTicket from './modules/TicketModule/index';
import appTicketType from './modules/TicketTypeModule/index';
import appAccount from './modules/AccountModule/index';
import appTypesOfPayment from './modules/TypesOfPaymentModule/index';
import appQuestionnaireType from './modules/QuestionnaireTypeModule/index';
import appLocation from './modules/LocationModule/index';
import appTicketTypePrice from './modules/TicketTypePriceModule/index';

export default createStore({
    state: {
        showMenu: false,
    },
    getters: {
        isShowMenu: state => {
            return state.showMenu;
        },
    },
    mutations: {
        SHOW_MENU: state => {
            state.showMenu = true;
        },
        HIDE_MENU: state => {
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
        'appPromoCode': appPromoCodeModule,
        'appQuestionnaire': appQuestionnaire,
        'appTicket': appTicket,
        'appTicketType': appTicketType,
        'appAccount': appAccount,
        'appTypesOfPayment': appTypesOfPayment,
        'appQuestionnaireType': appQuestionnaireType,
        'appLocation': appLocation,
        'appTicketTypePrice': appTicketTypePrice,
    }
})
