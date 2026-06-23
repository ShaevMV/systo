export const setList = (state, payload) => {
    state.list = payload;
};

export const setItem = (state, payload) => {
    state.item = payload;
};

export const setHistory = (state, payload) => {
    state.history = payload;
};

export const setIsLoading = (state, payload) => {
    state.isLoading = payload;
};

export const setHistoryLoading = (state, payload) => {
    state.historyLoading = payload;
};

export const setError = (state, payload) => {
    state.dataError = payload ?? {};
};

export const clearError = (state) => {
    state.dataError = {};
};
