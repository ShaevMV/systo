export const getError = state => type => {
    if (state.dataError !== undefined && state.dataError[type] !== undefined) {
        if (typeof state.dataError[type] === "object") {
            return state.dataError[type][0];
        }
        return state.dataError[type];
    }
    return '';
};

export const getList = state => {
    return state.list;
};

export const getItem = state => {
    return state.item;
};

export const getFileter = state => {
    return state.filter;
};

export const getOrderBy = state => {
    return state.orderBy;
};

export const getMessage = state => {
    return state.message;
};
