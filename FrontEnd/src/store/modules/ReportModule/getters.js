export const getConfigs = state => {
    return state.configs;
};

export const getActiveConfigs = state => {
    return state.configs.filter(config => config.is_active);
};

export const getIsLoading = state => {
    return state.isLoading;
};

export const getError = state => {
    return state.error;
};

export const getConfigById = state => (id) => {
    return state.configs.find(config => config.id === id);
};
