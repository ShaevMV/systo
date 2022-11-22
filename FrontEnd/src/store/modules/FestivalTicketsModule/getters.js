/**
 * Вывести список способов оплаты
 *
 * @param state
 * @returns {Array}
 */
export const getTypesOfPayment = state => {
    return state.typesOfPayment;
};


/**
 * Вывести список типов билетов
 *
 * @param state
 * @returns {Array}
 */
export const getTicketType = state => {
    return state.ticketType;
};

/**
 * Выбранный тип билета
 *
 * @param state
 * @returns {null}
 */
export const getSelectTicketType = state => {
    return state.selectTicketType;
};

/**
 * Id Выбранноготипа билета
 *
 * @param state
 * @returns {null|*}
 */
export const getSelectTicketTypeId = state => {
    if(state.selectTicketType !== null) {
        return state.selectTicketType.id;
    }
    return null;
};

/**
 * Вывести лимит по групавому билету
 *
 * @param state
 * @returns {null|*}
 */
export const getSelectTicketTypeLimit = state => {
    if(state.selectTicketType !== null) {
        return state.selectTicketType.groupLimit;
    }
    return null;
};

/**
 * Вывести скидку по промокоду
 *
 * @param state
 * @returns {null|*}
 */
export const getDiscountByPromoCode = state => {
    if(state.promoCode !== null) {
        return state.promoCode.discount;
    }
    return null;
};
