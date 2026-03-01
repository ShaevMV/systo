import axios from 'axios';

const API = '/api/v1/ticketType';


export const loadList = (context, payload) => {
    return new Promise((resolve, reject) => {
        let promise = axios.post(API + '/getList', payload);
        return promise.then(function (response) {
            context.commit('setList', response.data.list)
        }).catch(function (error) {
            context.commit('setError', error);
            reject(error);
        });
    });
};

export const loadTemplate = (context) => {
    return new Promise((resolve, reject) => {
        let promise = axios.get(API + '/getBlade');
        return promise.then(function (response) {
            context.commit('setTemplateList', response.data.list)
        }).catch(function (error) {
            context.commit('setError', error);
            reject(error);
        });
    });
};


export const loadItem = (context, payload) => {
    return new Promise((resolve, reject) => {
        let promise = axios.get(API + '/getItem/' + payload.id);
        return promise.then(function (response) {
            context.commit('setItem', response.data.item)
        }).catch(function (error) {
            context.commit('setError', error.response.data.errors);
            reject(error);
        });
    });
};

export const create = (context, payload) => {
    return new Promise((resolve, reject) => {
        let promise = axios.post(API + '/create', {
            'data': payload.data
        });
        return promise.then(function (response) {
            context.commit('setMessage', response.data.message)
        }).catch(function (error) {
            context.commit('setError', error.response.data.errors);
            reject(error);
        });
    });
};

export const edit = (context, payload) => {
    return new Promise((resolve, reject) => {
        let promise = axios.post(API + '/edit/' + payload.id, {
            'data': payload.data
        });
        return promise.then(function (response) {
            context.commit('setMessage', response.data.message)
            context.commit('setItem', response.data.item)
        }).catch(function (error) {
            context.commit('setError', error.response.data.errors);
            reject(error);
        });
    });
};

export const remove = (context, payload) => {
    return new Promise((resolve, reject) => {
        let promise = axios.delete(API + '/delete/' + payload.id, {
            'data': payload.data
        });
        return promise.then(function () {
            context.commit('removeInList', payload);
        }).catch(function (error) {
            context.commit('setError', error.response.data.errors);
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
    let orderByCurrent = context.getters.getOrderBy

    if (Object.keys(orderByCurrent).length == 0 || Object.keys(orderByCurrent)[0] !== payload) {
        context.commit('setOrderBy', {[payload]: 'desc'});
    } else {
        let type = (orderByCurrent[payload] == 'desc' ? 'asc': 'desc');
        context.commit('setOrderBy', {[payload]: type});
    }

};