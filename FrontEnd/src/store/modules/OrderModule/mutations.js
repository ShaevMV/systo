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

export const chanceStatus = (state, payload) => {
    state.orderList.find(item => item.id === payload.id).humanStatus = payload.humanStatus;
    state.orderList.find(item => item.id === payload.id).status = payload.status;
    state.orderList.find(item => item.id === payload.id).listCorrectNextStatus = payload.listCorrectNextStatus;
    
    // Обновляем цену, если она была изменена
    if (payload.price !== undefined && payload.price !== null) {
        state.orderList.find(item => item.id === payload.id).price = payload.price;
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