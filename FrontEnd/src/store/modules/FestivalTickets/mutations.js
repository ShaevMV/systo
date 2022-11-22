/**
 * Запись данные о видах оплаты
 *
 * @param state
 * @param payload
 */
export const setTypesOfPayment = (state, payload) => {
    state.typesOfPayment = payload;
};

/**
 * Запись данные о типах билета
 *
 * @param state
 * @param payload
 */
export const setTicketType = (state, payload) => {
    state.ticketType = payload;
};
