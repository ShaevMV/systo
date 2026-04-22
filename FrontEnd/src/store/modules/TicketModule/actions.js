import axios from 'axios';

const API_TICKET = '/api/v1/ticket'

/**
 * Авторизоваться
 *
 * @param context
 * @param payload
 */
export const toPushNumber = (context, payload) => {
    let promise = axios.get(API_TICKET + '/live/' + payload.number);
    promise.then(async function (response) {
        context.commit('setLiveNumber', response.data.number);

    }).catch(function (error) {
        context.commit('setError', error.response.data.errors);
    });
};

export const clearError = (context) => {
    context.commit('setError', []);
};
