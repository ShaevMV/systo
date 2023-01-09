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
    console.log(payload);
    state.orderList.find(item => item.id === payload.id).humanStatus = payload.humanStatus;
    state.orderList.find(item => item.id === payload.id).status = payload.status;
    state.orderList.find(item => item.id === payload.id).listCorrectNextStatus = payload.listCorrectNextStatus;
};
