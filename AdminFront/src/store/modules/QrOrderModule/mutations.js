export const setList = (state, payload) => {
    state.list = payload;
};

export const setItem = (state, payload) => {
    state.item = payload;
};

export const setHistory = (state, payload) => {
    state.history = payload;
};

export const setTickets = (state, payload) => {
    state.tickets = payload;
};

export const setEmails = (state, payload) => {
    state.emails = payload;
};

export const setFilter = (state, payload) => {
    state.filter = payload;
};

export const setOrderBy = (state, payload) => {
    state.orderBy = payload;
};

export const setPagination = (state, payload) => {
    state.pagination = { ...state.pagination, ...payload };
};

export const setTotal = (state, payload) => {
    state.pagination = { ...state.pagination, total: payload };
};

export const setIsLoading = (state, payload) => {
    state.isLoading = payload;
};

export const setError = (state, payload) => {
    state.dataError = payload;
};
