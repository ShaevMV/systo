import axios from 'axios';

console.log(process.env.VUE_APP_BACKEND_ENDPOINT);

axios.defaults.baseURL = process.env.VUE_APP_BACKEND_ENDPOINT
axios.defaults.withCredentials = true

/**
 * Загрузить список способов оплаты и типов билета
 *
 * @param context
 */
export const loadDataForOrderingTickets = (context) => {
    let promise = axios.get('/api/v1/festival/orderingTickets');
    promise.then(function (response) {
        context.commit('setTypesOfPayment', response.data.typesOfPayment);
        context.commit('setTicketType', response.data.ticketType);
        context.commit('setSelectTicketType', response.data.ticketType[0]);
    })
};

/**
 * Записать выбранный тип билета
 *
 * @param context
 * @param payload
 */
export const setSelectTicketType = (context, payload) => {
    let select = context.state.ticketType.find(type => type.id === payload);
    context.commit('setSelectTicketType', select);
};

export const checkPromoCode = (context, payload) => {
    let promise = axios.get('/api/v1/festival/findPromoCode/' + payload);
    promise.then(function (response) {
        context.commit('setValuePromoCode', response.data);
    })
};


/**
 * Отправить данные на создание билета
 *
 * @param context
 * @param payload
 */
export const goToOrderTicket = (context, payload) => {
    let promise = axios.post('/api/v1/festival/ticketsOrder/create', payload);
    promise.then(function () {
        context.dispatch('loadExpertsList');
        payload.callback();
    }).catch(function (error) {
        context.commit('setError', error.response.data.errors, {root: true});
    });
}
