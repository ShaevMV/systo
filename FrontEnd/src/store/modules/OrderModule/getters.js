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
 * проверим прошла ли загрузка
 *
 * @param state
 * @returns {Array}
 */
export const getIsLoading = state => {
    return state.isLoading;
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

/**
 * Вывести данные об общем колве заказов
 *
 * @param state
 * @returns {{totalAmount: number, totalCountToPaid: number, totalCount: number}}
 */
export const getTotalNumber = state => {
    return state.totalNumber;
};

/**
 * Вывести данные о сообщение после заполнения анкеты
 */
export const getMessageForQuestionnaire = state => {
    return state.questionnaireItem.message;
};

/**
 * Получить текущий фильтр
 *
 * @param state
 * @returns {Object}
 */
export const getFilter = state => {
    return state.filter;
};

export const getOrderHistory = state => {
    return state.orderHistory;
};