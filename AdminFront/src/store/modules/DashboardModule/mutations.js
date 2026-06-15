export const setStats = (state, payload) => {
    state.stats = payload;
};

export const setFilter = (state, payload) => {
    state.filter = payload;
};

export const setIsLoading = (state, payload) => {
    state.isLoading = payload;
};

export const setError = (state, payload) => {
    state.dataError = payload;
};
