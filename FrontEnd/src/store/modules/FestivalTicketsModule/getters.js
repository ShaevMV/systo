export const getError = state => type => {
    if (state.dataError !== undefined && state.dataError[type] !== undefined) {
        if (typeof state.dataError[type] === "object") {
            return state.dataError[type][0];
        }
        return state.dataError[type];
    }
    return '';
};

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
    if (state.selectTicketType.id !== null) {
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
    if (state.selectTicketType.groupLimit !== null) {
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
    if (state.promoCode.discount !== null) {
        return state.promoCode.discount;
    }
    return null;
};


export const getPromoCodeName = state => {
    if (state.promoCode.name !== null) {
        return state.promoCode.name;
    }

    return null;
};

/**
 * Проверка на соответсвие условием группавого типа билета
 */
export const isAllowedGuest = (state, getters) => count => {
    return state.selectTicketType.groupLimit === null || getters.getSelectTicketTypeLimit >= count;
};


export const isAllowedGuestMin = (state, getters) => count => {
    return state.selectTicketType.groupLimit === null || getters.getSelectTicketTypeLimit === count;
};