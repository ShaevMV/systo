import axios from 'axios';

const API = '/api/v1/option';

export const loadList = (context, payload) => {
    return new Promise((resolve, reject) => {
        axios.post(API + '/getList', payload)
            .then(function (response) {
                context.commit('setList', response.data.list);
                resolve(response.data.list);
            })
            .catch(function (error) {
                context.commit('setError', error.response?.data?.errors ?? []);
                reject(error);
            });
    });
};

export const loadItem = (context, payload) => {
    return new Promise((resolve, reject) => {
        axios.get(API + '/getItem/' + payload.id)
            .then(function (response) {
                context.commit('setItem', response.data.item);
                resolve(response.data.item);
            })
            .catch(function (error) {
                context.commit('setError', error.response?.data?.errors ?? []);
                reject(error);
            });
    });
};

/**
 * Read-модель для формы покупки билета:
 * вернёт активные опции с уже подмешанной актуальной ценой
 * и description (зависит от типа билета).
 */
export const loadActiveForTicketType = (context, payload) => {
    return new Promise((resolve, reject) => {
        axios.get(API + '/getActiveForTicketType/' + payload.ticketTypeId)
            .then(function (response) {
                context.commit('setActiveForTicketType', response.data.list);
                resolve(response.data.list);
            })
            .catch(function (error) {
                context.commit('setError', error.response?.data?.errors ?? []);
                reject(error);
            });
    });
};

export const create = (context, payload) => {
    return new Promise((resolve, reject) => {
        axios.post(API + '/create', { data: payload.data })
            .then(function (response) {
                context.commit('setMessage', response.data.message);
                context.commit('setItem', response.data.item);
                resolve(response.data);
            })
            .catch(function (error) {
                context.commit('setError', error.response?.data?.errors ?? []);
                reject(error);
            });
    });
};

export const edit = (context, payload) => {
    return new Promise((resolve, reject) => {
        axios.post(API + '/edit/' + payload.id, { data: payload.data })
            .then(function (response) {
                context.commit('setMessage', response.data.message);
                context.commit('setItem', response.data.item);
                resolve(response.data);
            })
            .catch(function (error) {
                context.commit('setError', error.response?.data?.errors ?? []);
                reject(error);
            });
    });
};

export const remove = (context, payload) => {
    return new Promise((resolve, reject) => {
        axios.delete(API + '/delete/' + payload.id)
            .then(function () {
                context.commit('removeInList', payload);
                resolve();
            })
            .catch(function (error) {
                context.commit('setError', error.response?.data?.errors ?? []);
                reject(error);
            });
    });
};

export const clearError = (context) => {
    context.commit('setError', []);
};

export const setFilter = (context, payload) => {
    context.commit('setFilter', payload);
};

export const setOrderBy = (context, payload) => {
    let orderByCurrent = context.getters.getOrderBy;

    if (Object.keys(orderByCurrent).length === 0 || Object.keys(orderByCurrent)[0] !== payload) {
        context.commit('setOrderBy', { [payload]: 'desc' });
    } else {
        let type = (orderByCurrent[payload] === 'desc' ? 'asc' : 'desc');
        context.commit('setOrderBy', { [payload]: type });
    }
};
