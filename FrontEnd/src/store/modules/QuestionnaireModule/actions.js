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
            'email': payload.email
        });
        return promise.then(function (response) {
            context.commit('setMessage', response.data.message)
            payload.callback();
        }).catch(function (error) {
            if (error.response && error.response.data && error.response.data.errors) {
                context.commit('setError', error.response.data.errors);
            } else {
                context.commit('setError', {});
            }
            reject(error);
        });
    });
};

export const approve = (context, payload) => {
    return new Promise((resolve, reject) => {
        axios.post(API + '/approve/' + payload.id)
            .then(response => {
                // Извлекаем сообщение из ответа (если есть)
                const message = response.data?.message || 'Анкета подтверждена';
                // Вызываем мутацию (предполагается, что она обновляет статус)
                context.commit('approve', payload);
                // Вызываем колбэк, если он передан
                if (payload.callback) {
                    payload.callback(message);
                }
                resolve(response);
            })
            .catch(error => {
                // Подготовка сообщения об ошибке
                let errorMessage = 'Ошибка при подтверждении';
                let errorData = null;

                if (error.response) {
                    // Сервер вернул ответ с ошибкой
                    errorData = error.response.data?.errors;
                    errorMessage = error.response.data?.message || errorMessage;
                } else if (error.request) {
                    // Запрос был отправлен, но нет ответа
                    errorMessage = 'Нет ответа от сервера';
                } else {
                    // Ошибка на этапе настройки запроса
                    errorMessage = error.message;
                }

                if (errorData) {
                    context.commit('setError', errorData);
                } else {
                    context.commit('setError', { generic: errorMessage });
                }

                if (payload.callback) {
                    payload.callback(errorMessage);
                }
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
            if (error.response && error.response.data && error.response.data.errors) {
                context.commit('setError', error.response.data.errors);
            } else {
                context.commit('setError', {});
            }
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
            context.commit('setMessage', response.data.message);
            context.commit('setError', {});
            payload.callback();
            resolve(response);
        }).catch(function (error) {
            if (error.response && error.response.data && error.response.data.errors) {
                context.commit('setError', error.response.data.errors);
            } else {
                context.commit('setError', {});
            }
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
            if (error.response && error.response.data && error.response.data.errors) {
                context.commit('setError', error.response.data.errors);
            } else {
                context.commit('setError', {});
            }
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
            context.commit('setQuestionnaireItem', response.data.questionnaire);
            context.commit('setError', []);
            if (payload.callback !== undefined) {
                payload.callback(response.data.questionnaire);
            }
            resolve(response.data.questionnaire);
        }).catch(function (error) {
            console.error('getQuestionnaire error:', error);
            if (error.response && error.response.data && error.response.data.errors) {
                context.commit('setError', error.response.data.errors);
            }
            reject(error);
        });
    });
};

/**
 * Загрузить фото участника для бейджа
 *
 * @param context
 * @param payload - { file, orderId, ticketId, callback(photoUrl) }
 * @returns {Promise<unknown>}
 */
export const uploadPhoto = (context, payload) => {
    return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('photo', payload.file);

        axios.post(API + '/uploadPhoto/' + payload.orderId + '/' + payload.ticketId, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        }).then(response => {
            if (payload.callback) {
                payload.callback(response.data.photo_url);
            }
            resolve(response.data.photo_url);
        }).catch(error => {
            if (error.response && error.response.data && error.response.data.errors) {
                context.commit('setError', error.response.data.errors);
            } else {
                context.commit('setError', { photo: error.response?.data?.message || 'Ошибка загрузки фото' });
            }
            reject(error);
        });
    });
};

/**
 * Загрузить анкету по orderId и ticketId для предзаполнения
 *
 * @param context
 * @param payload
 * @returns {Promise<unknown>}
 */
export const loadQuestionnaireByOrderTicket = (context, payload) => {
    return new Promise((resolve, reject) => {
        let promise = axios.get(API + '/getByOrderTicket/' + payload.orderId + '/' + payload.ticketId);
        return promise.then(function (response) {
            context.commit('setQuestionnaireItem', response.data.questionnaire);
            context.commit('setError', []);
            resolve(response.data.questionnaire);
        }).catch(function (error) {
            console.error('loadQuestionnaireByOrderTicket error:', error);
            if (error.response && error.response.data && error.response.data.errors) {
                context.commit('setError', error.response.data.errors);
            }
            reject(error);
        });
    });
};