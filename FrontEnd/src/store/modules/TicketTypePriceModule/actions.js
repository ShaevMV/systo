import axios from 'axios';

const API = '/api/v1/ticketTypePrice';

export const loadList = (context, payload) => {
    const filter = (payload && payload.filter) || context.state.filter || {};
    return new Promise((resolve, reject) => {
        axios.post(API + '/getList', {filter, orderBy: context.state.orderBy || {}})
            .then(function (response) {
                context.commit('setList', response.data.list);
                resolve(response);
            })
            .catch(function (error) {
                context.commit('setError', error.response?.data?.errors);
                reject(error);
            });
    });
};

export const loadItem = (context, payload) => {
    return new Promise((resolve, reject) => {
        axios.get(API + '/getItem/' + payload.id)
            .then(function (response) {
                context.commit('setItem', response.data.item);
                resolve(response);
            })
            .catch(function (error) {
                context.commit('setError', error.response?.data?.errors);
                reject(error);
            });
    });
};

export const create = (context, payload) => {
    return new Promise((resolve, reject) => {
        axios.post(API + '/create', {data: payload.data})
            .then(function (response) {
                context.commit('setMessage', response.data.message);
                if (response.data.item) {
                    context.commit('upsertInList', response.data.item);
                }
                resolve(response);
            })
            .catch(function (error) {
                context.commit('setError', error.response?.data?.errors);
                reject(error);
            });
    });
};

export const edit = (context, payload) => {
    return new Promise((resolve, reject) => {
        axios.post(API + '/edit/' + payload.id, {data: payload.data})
            .then(function (response) {
                context.commit('setMessage', response.data.message);
                if (response.data.item) {
                    context.commit('upsertInList', response.data.item);
                }
                resolve(response);
            })
            .catch(function (error) {
                context.commit('setError', error.response?.data?.errors);
                reject(error);
            });
    });
};

export const remove = (context, payload) => {
    return new Promise((resolve, reject) => {
        axios.delete(API + '/delete/' + payload.id)
            .then(function (response) {
                context.commit('removeInList', payload);
                resolve(response);
            })
            .catch(function (error) {
                context.commit('setError', error.response?.data?.errors);
                reject(error);
            });
    });
};

export const clearError = (context) => {
    context.commit('setError', []);
};

export const clearMessage = (context) => {
    context.commit('setMessage', null);
};

export const setFilter = (context, payload) => {
    context.commit('setFilter', payload);
};
