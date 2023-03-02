import axios from 'axios';

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

/**
 * Проверить промо код
 *
 * @param context
 * @param payload
 */
export const checkPromoCode = (context, payload) => {
    let promise = axios.post('/api/v1/festival/findPromoCode/' + payload.promoCode, {
        typeOrder: payload.typeOrder
    });

    promise.then(function (response) {
        if (payload.callback !== undefined) {
            payload.callback(response.data.massage);
        }

        context.commit('setValuePromoCode', response.data);
    })
};

/**
 * Очистить данные о промо коде
 *
 * @param context
 */
export const clearPromoCode = (context) => {
    context.commit('setValuePromoCode', {
        success: false,
        discount: null,
        name: null,
    });
};



export const getListPriceFor =  (context) => {
    let promise = axios.get('/api/v1/festival/getListPrice');
    promise.then(function (response) {
        context.commit('setTicketType', response.data.ticketType);
    })
};
