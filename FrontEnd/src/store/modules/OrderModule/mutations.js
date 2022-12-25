export const setError = (state, payload) => {
    state.dataError = payload;
};


export const setOrderUserList = (state, payload) => {
    state.orderList = payload;
};

export const setOrderItem = (state, payload) => {
    state.orderItem = payload;
};

export const addCommentByOrderItem = (state, payload) => {
    state.orderItem.comment.push(payload);
};

