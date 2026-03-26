import axios from 'axios';

const API_REPORTS = '/api/v1/reports';

export const loadConfigs = (context) => {
    context.commit('setLoading', true);
    return axios.get(API_REPORTS + '/configs')
        .then(function (response) {
            context.commit('setConfigs', response.data.configs);
            context.commit('setLoading', false);
        })
        .catch(function (error) {
            console.error(error);
            context.commit('setError', error.response?.data?.errors || error.message);
            context.commit('setLoading', false);
        });
};

export const saveConfig = (context, payload) => {
    context.commit('setLoading', true);
    return axios.post(API_REPORTS + '/configs', payload)
        .then(function (response) {
            if (response.data.config) {
                context.commit('addConfig', response.data.config);
            }
            context.commit('setLoading', false);
        })
        .catch(function (error) {
            console.error(error);
            context.commit('setError', error.response?.data?.errors || error.message);
            context.commit('setLoading', false);
        });
};

export const updateConfig = (context, payload) => {
    context.commit('setLoading', true);
    return axios.put(API_REPORTS + '/configs/' + payload.id, payload)
        .then(function (response) {
            if (response.data.config) {
                context.commit('updateConfig', response.data.config);
            }
            context.commit('setLoading', false);
        })
        .catch(function (error) {
            console.error(error);
            context.commit('setError', error.response?.data?.errors || error.message);
            context.commit('setLoading', false);
        });
};

export const exportToGoogle = (context, payload) => {
    context.commit('setLoading', true);
    return axios.post(API_REPORTS + '/export', payload)
        .then(function (response) {
            context.commit('setLoading', false);
            return response.data;
        })
        .catch(function (error) {
            console.error(error);
            context.commit('setError', error.response?.data?.errors || error.message);
            context.commit('setLoading', false);
            throw error;
        });
};

export const deleteConfig = (context, configId) => {
    context.commit('setLoading', true);
    return axios.delete(API_REPORTS + '/configs/' + configId)
        .then(function () {
            context.commit('deleteConfig', configId);
            context.commit('setLoading', false);
        })
        .catch(function (error) {
            console.error(error);
            context.commit('setError', error.response?.data?.errors || error.message);
            context.commit('setLoading', false);
        });
};
