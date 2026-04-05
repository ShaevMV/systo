export const setConfigs = (state, configs) => {
    state.configs = configs;
};

export const setLoading = (state, isLoading) => {
    state.isLoading = isLoading;
};

export const setError = (state, error) => {
    state.error = error;
};

export const addConfig = (state, config) => {
    state.configs.push(config);
};

export const updateConfig = (state, updatedConfig) => {
    const index = state.configs.findIndex(c => c.id === updatedConfig.id);
    if (index !== -1) {
        state.configs.splice(index, 1, updatedConfig);
    }
};

export const deleteConfig = (state, configId) => {
    state.configs = state.configs.filter(c => c.id !== configId);
};
