export const getError = state => type => {
    if(state.dataError !== undefined && state.dataError[type] !== undefined){
        if(typeof state.dataError[type] === "object"){
            return state.dataError[type][0];
        }
        return state.dataError[type];
    }
    return '';
};

/**
 * Вывести список заказв пользователя
 *
 * @param state
 * @returns {[]}
 */
export const getOrderList = state => {
    return state.orderList;
};

/**
 * Вывести выбранный заказ
 *
 * @param state
 * @returns {null| Object}
 */
export const getOrderItem = state => {
    return state.orderItem;
};

/**
 * Вывести комментарии
 *
 * @param state
 * @returns {[]}
 */
export const getComment = state => {
    return state.orderItem.comment;
};

