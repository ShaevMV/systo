import axios from 'axios';

const API_FESTIVAL = '/api/v1/festival'
const API_PROMOCODE = '/api/v1/promoCode'

/**
 * Загрузить список способов оплаты и типов билета
 *
 * @param context
 * @param payload
 */
export const loadDataForOrderingTickets = (context, payload) => {
    let promise = axios.get(API_FESTIVAL + '/load',
        {params: {
            festival_id: payload.festival_id,
            is_admin:    payload.is_admin,
        }});
    promise.then(function (response) {
        context.commit('setTypesOfPayment', response.data.typesOfPayment);
        context.commit('setSelectTypesOfPayment', response.data.typesOfPayment[0]);
        context.commit('setTicketType', response.data.ticketType);
        context.commit('setSelectTicketType', response.data.ticketType[0]);
    })
};

export const loadTypesOfPayment = (context, payload) => {
    let promise = axios.get(API_FESTIVAL + '/loadByTicketType/' + payload.ticket_type_id);

    promise.then(function (response) {
        context.commit('setTypesOfPayment', response.data.typesOfPaymentDto);
        context.commit('setSelectTypesOfPayment', response.data.typesOfPaymentDto[0]);
    })
};


export const getListTypesOfPayment = (context, payload) => {
    let promise = axios.get(API_FESTIVAL + '/load',
        {params: {
                festival_id: payload.festival_id,
                is_admin: true,
            }});
    promise.then(function (response) {
        context.commit('setTypesOfPayment', response.data.typesOfPayment);
    })
};


export const getListPriceFor = (context, payload) => {
    let promise = axios.get(API_FESTIVAL + '/getListPrice',
        {params: {festival_id: payload.festival_id}});
    promise.then(function (response) {
        context.commit('setTicketType', response.data.ticketType);
    })
};



export const getListFestival = (context) => {
    let promise = axios.get(API_FESTIVAL + '/getFestivalList');
    promise.then(function (response) {
        context.commit('setFestivalList', response.data.festivalDto);
    })
};


export const getListTicketTypes = (context) => {
    let promise = axios.get(API_FESTIVAL + '/getTicketTypeList');
    promise.then(function (response) {
        context.commit('setTicketType', response.data.ticketType);
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
 * Записать выбранный тип оплаты
 *
 * @param context
 * @param payload
 */
export const setSelectTypesOfPayment = (context, payload) => {
    let select = context.state.typesOfPayment.find(type => type.id === payload);
    context.commit('setSelectTypesOfPayment', select);
};
/**
 * Проверить промо код
 *
 * @param context
 * @param payload
 */
export const checkPromoCode = (context, payload) => {
    let promise = axios.post(API_PROMOCODE +  '/find/' + payload.promoCode, {
        typeOrder: payload.typeOrder
    });

    promise.then(function (response) {
        if (payload.callback !== undefined) {
            payload.callback(response.data.message);
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