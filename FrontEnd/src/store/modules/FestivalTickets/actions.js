import axios from 'axios';


/**
 * Загрузить список экпертов
 *
 * @param context
 */
export const loadDataForOrderingTickets = (context) => {
    let promise = axios.get('/api/v1/festival/orderingTickets');
    promise.then(function (response) {
        context.commit('setTypesOfPayment', response.data.typesOfPayment);
        context.commit('getTicketType', response.data.ticketType);
    })
};
