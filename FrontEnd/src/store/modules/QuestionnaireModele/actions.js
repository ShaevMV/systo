import axios from 'axios';

const API = '/api/v1/questionnaire';

/**
 * Повторно выслать ссылку на анкету
 *
 * @param context
 * @param payload
 * @returns {Promise<unknown>}
 */
export const sendNotitificationUser = (context, payload) => {
    return new Promise((resolve, reject) => {
        let promise = axios.post(API + '/notification/' + payload.id, {
            'questionnaire': payload.questionnaire
        });
        return promise.then(function (response) {
            context.commit('setMessage', response.data.message)
            payload.callback();
        }).catch(function (error) {
            context.commit('setError', error.response.data.errors);
            reject(error);
        });
    });
};

/**
 * Загрузить список всех анкет по фильтру
 *
 * @param context
 * @param payload
 */
export const loadQuestionnaire = (context, payload) => {
    let promise = axios.post(API + '/load', payload.filter);
    promise.then(function (response) {
        context.commit('setQuestionnaireList', response.data.questionnaireList);
    })
};

/**
 * отправить данные для анкеты по заказу
 *
 * @param context
 * @param payload
 * @returns {Promise<unknown>}
 */
export const sendNewUserQuestionnaire = (context, payload) => {
    return new Promise((resolve, reject) => {
        let promise = axios.post(API + '/sendNewUser/', {
            'questionnaire': payload.questionnaire
        });
        return promise.then(function (response) {
            context.commit('setMessage', response.data.message)
            payload.callback();
        }).catch(function (error) {
            context.commit('setError', error.response.data.errors);
            reject(error);
        });
    });
};

/**
 * отправить данные для анкеты по заказу
 *
 * @param context
 * @param payload
 * @returns {Promise<unknown>}
 */
export const sendQuestionnaire = (context, payload) => {
    return new Promise((resolve, reject) => {
        let promise = axios.post(API + '/send/' + payload.orderId + '/' + payload.ticketId, {
            'questionnaire': payload.questionnaire
        });
        return promise.then(function (response) {
            context.commit('setMessage', response.data.message)
            payload.callback();
        }).catch(function (error) {
            context.commit('setError', error.response.data.errors);
            reject(error);
        });
    });
};

/**
 * Отредактировать анкету
 *
 * @param context
 * @param payload
 * @returns {Promise<unknown>}
 */
export const editQuestionnaire = (context, payload) => {
    return new Promise((resolve, reject) => {
        let promise = axios.post(API + '/edit/' + payload.id, {
            'questionnaire': payload.questionnaire
        });
        return promise.then(function (response) {
            context.commit('setMessage', response.data.message)
            payload.callback();
        }).catch(function (error) {
            context.commit('setError', error.response.data.errors);
            reject(error);
        });
    });
};

/**
 * Получить определённую анкету
 *
 * @param context
 * @param payload
 * @returns {Promise<unknown>}
 */
export const getQuestionnaire = (context, payload) => {
    return new Promise((resolve, reject) => {
        let promise = axios.get(API + '/get/' + payload.id);
        return promise.then(function (response) {
            context.commit('setQuestionnaireItem', response.data.questionnaire)
            if(response.callback !== undefined) {
                response.callback(response.data.questionnaire);
            }
        }).catch(function (error) {
            console.error(error);
            //context.commit('setError', error.response.data.errors);
            reject(error);
        });
    });
};