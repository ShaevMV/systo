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
    if (payload !== null && payload.length > 2) {
        let promise = axios.get('/api/v1/festival/findPromoCode/' + payload);
        promise.then(function (response) {
            let result = typeof response.data === 'object' ? response.data : {
                'name': payload,
                'discount': null,
            };
            console.log(result);
            context.commit('setValuePromoCode', result);
        })
    }
};

/**
 * Очистить данные о промо коде
 *
 * @param context
 */
export const clearPromoCode = (context) => {
    context.commit('setValuePromoCode', {
        discount: null,
        name: null,
    });
};
