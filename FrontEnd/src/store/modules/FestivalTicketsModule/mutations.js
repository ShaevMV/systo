export const setError = (state, payload) => {
    state.dataError = payload;
};

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
 * Запись данные о cиске фестивалей
 *
 * @param state
 * @param payload
 */
export const setFestivalList = (state, payload) => {
    state.festivalList = payload;
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


/**
 * Записать данные о выбранном типе билета
 *
 * @param state
 * @param payload
 */
export const setSelectTicketType = (state, payload) => {
    state.selectTicketType = payload;
};

/**
 * Запись данные о выбранном видае оплаты
 *
 * @param state
 * @param payload
 */
export const setSelectTypesOfPayment = (state, payload) => {
    state.selectTypeOfPayment = payload;
};

/**
 * Записать данные о промо коде
 *
 * @param state
 * @param payload
 */
export const setValuePromoCode = (state, payload) => {
    console.log(payload);
    state.promoCode = payload;
};
