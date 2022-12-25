import axios from 'axios';

axios.defaults.baseURL = process.env.VUE_APP_BACKEND_ENDPOINT
axios.defaults.withCredentials = true

/**
 * Авторизоваться
 *
 * @param context
 * @param payload
 */
export const toLogin = (context, payload) => {
    let promise = axios.post('/api/login', payload);
    promise.then(async function (response) {
        if (response.data.status === 'success') {
            context.commit('setToken', response.data.authorisation);
            context.commit('setUserInfo', response.data.user);
            payload.callback();
        }
    }).catch(function (error) {
        if (error.response.data.errors === undefined) {
            context.commit('setError', {
                'main': error.response.data.message
            });
        } else {
            context.commit('setError', error.response.data.errors);
        }
    });
};

/**
 * Пересобрать токен
 *
 * @param context
 */
export const tokenRefresh = (context) => {
    let promise = axios.post('/api/refresh');
    promise.then(async function (response) {
        if (response.data.status === 'success') {
            context.commit('setToken', response.data.authorisation);
            context.commit('setUserInfo', response.data.user);
        }
    }).catch(function (error) {
        if(error.response.status === 401) {
            context.dispatch('logOut').then(r => console.log(r));
        }
        console.error(error);
    })
};

export const isCorrectRole = (context, payload) => {
    console.log(payload);
    return axios.post('/api/isCorrectRole', payload);
};



export const logOut = (context) => {
    let promise = axios.post('/api/logout');
    promise.then(async function () {
        context.commit('removeToken');
        location.reload();
    }).catch(function (error) {
        console.error(error);
        context.commit('removeToken');
    });
};
