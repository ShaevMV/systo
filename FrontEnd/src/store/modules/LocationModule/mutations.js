export const setList = (state, payload) => {
    state.list = payload;
};

export const setItem = (state, payload) => {
    state.item = payload;
};

export const setFilter = (state, payload) => {
    state.filter = payload;
};

export const setOrderBy = (state, payload) => {
    state.orderBy = payload;
};

export const setIsLoading = (state, payload) => {
    state.isLoading = payload;
};

export const setError = (state, payload) => {
    state.dataError = payload;
};

export const setMessage = (state, payload) => {
    state.message = payload;
};

export const removeInList = (state, payload) => {
    state.list = state.list.filter((item) => item.id !== payload.id);
};

export const setTemplateList = (state, payload) => {
    state.templateList = {
        email: payload?.email ?? [],
        pdf: payload?.pdf ?? [],
    };
};
