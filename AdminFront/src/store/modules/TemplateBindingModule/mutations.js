export const setList = (state, payload) => {
    state.list = payload;
};

export const setRefs = (state, payload) => {
    Object.assign(state, payload);
};

export const setIsLoading = (state, payload) => {
    state.isLoading = payload;
};
