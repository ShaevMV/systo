export const setList = (state, payload) => {
    state.list = payload;
};

export const setItem = (state, payload) => {
    state.item = payload;
};

export const setVersions = (state, payload) => {
    state.versions = payload;
};

export const setHistory = (state, payload) => {
    state.history = payload;
};

export const setVariables = (state, payload) => {
    state.variables = payload;
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
