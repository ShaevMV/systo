export const setError = (state, payload) => {
    state.dataError = payload;
};


export const setOrderUserList = (state, payload) => {
    state.orderList = payload;
};

export const setTotalNumber = (state, payload) => {
    state.totalNumber = payload;
};

export const setOrderItem = (state, payload) => {
    state.orderItem = payload;
};

export const addCommentByOrderItem = (state, payload) => {
    state.orderItem.comment.push(payload);
};

export const setFilter = (state, payload) => {
    state.filter = { ...state.filter, ...payload };
};

export const chanceStatus = (state, payload) => {
    const orderItem = state.orderList.find(item => item.id === payload.id);
    if (!orderItem) {
        console.warn(`Заказ с ID ${payload.id} не найден в списке`);
        return;
    }

    orderItem.humanStatus = payload.humanStatus;
    orderItem.status = payload.status;
    orderItem.listCorrectNextStatus = payload.listCorrectNextStatus;

    // Обновляем цену, если она была изменена
    if (payload.price !== undefined && payload.price !== null) {
        orderItem.price = payload.price;
    }
};

export const setMessage = (state, payload) => {
    state.questionnaireItem.message = payload;
};

/**
 * Установить Loaging
 *
 * @param state
 * @param payload
 */
export const setLoaging = (state, payload) => {
    state.isLoading = payload;
};