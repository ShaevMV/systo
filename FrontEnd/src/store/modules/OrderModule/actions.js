import axios from 'axios';

const API_ORDER = '/api/v1/order'
const API_INVITE = '/api/v1/invite'
/**
 * Отправить данные на создание билета
 *
 * @param context
 * @param payload
 */
export const goToCreateOrderTicket = (context, payload) => {
    let promise = axios.post(API_ORDER + '/create', payload);
    promise.then(function (response) {
        console.log(response.data.success);
        payload.callback(response.data.success, response.data.message, response.data.link ?? null);
    }).catch(function (error) {
        console.error(error);
        context.commit('setError', error.response.data.errors);
    });
}


export const goToCreateFrendlyOrderTicket = (context, payload) => {
    let promise = axios.post(API_ORDER + '/createFriendly', payload);
    promise.then(function (response) {
        console.log(response.data.success);
        payload.callback(response.data.success, response.data.message);
    }).catch(function (error) {
        console.error(error);
        context.commit('setError', error.response.data.errors);
    });
}


export const getOrderListForFrendly = (context, payload) => {
    console.log(payload);
    let promise = axios.post(API_ORDER + '/getListForFriendly', payload);
    promise.then(function (response) {
        context.commit('setOrderUserList', response.data.list);
        context.commit('setTotalNumber', response.data.totalNumber);
        context.commit('setLoaging', false);
    }).catch(function (error) {
        console.error(error);
        context.commit('setError', error.response.data.errors);
    });
}


/**
 * Получить список заказов пользователя
 *
 * @param context
 */
export const getOrderListForUser = (context) => {
    let promise = axios.get(API_ORDER + '/getUserList');
    promise.then(function (response) {
        context.commit('setOrderUserList', response.data.list);
    }).catch(function (error) {
        console.error(error);
        context.commit('setError', error.response.data.errors);
    });
}

/**
 * Получить список заказов по фильтру
 *
 * @param context
 * @param payload
 */
export const getOrderListForAdmin = (context, payload) => {
    // Если фильтр не передан, используем фильтр из state
    const filter = payload || context.state.filter || {};
    console.log(filter);
    let promise = axios.post(API_ORDER + '/getList', filter);
    promise.then(function (response) {
        context.commit('setOrderUserList', response.data.list);
        context.commit('setTotalNumber', response.data.totalNumber);
        context.commit('setLoaging', false);
    }).catch(function (error) {
        console.error(error);
        context.commit('setError', error.response?.data?.errors || error.message);
    });
}

/**
 * Загрузить заказ
 *
 * @param context
 * @param payload
 */
export const loadOrderItem = (context, payload) => {
    let promise = axios.get(API_ORDER + '/getItem/' + payload.id);
    promise.then(function (response) {
        context.commit('setOrderItem', response.data.order);
    }).catch(function (error) {
        console.error(error);
        context.commit('setError', error.response.data.errors);
    });
};

/**
 * Сменить статус у заказа
 *
 * @param context
 * @param payload
 */
export const sendToChangeStatus = (context, payload) => {
    context.commit('setLoaging', true);
    let promise = axios.post(API_ORDER + '/toChangeStatus/' + payload.id, {
        'status': payload.status,
        'comment': payload.comment,
        'liveList': payload?.liveList,
    });
    promise.then(function (response) {
        context.commit('chanceStatus', {
            'id': payload.id,
            'humanStatus': response.data.status.humanStatus,
            'status': response.data.status.name,
            'listCorrectNextStatus': response.data.status.listCorrectNextStatus,
        })
        // Обновляем данные заказа если они есть в ответе
        if (response.data.order) {
            context.commit('setOrderItem', response.data.order);
        }
        // Перезагружаем список заказов с текущим фильтром
        // if (context.state.filter?.festivalId) {
        //    context.dispatch('getOrderListForAdmin', context.state.filter);
        // }
        // Закрываем модалку ПОСЛЕ всех операций
        if(payload.callback !== undefined) {
            payload.callback()
        }
    }).catch(function (error) {
        console.error(error);
        context.commit('setError', error.response?.data?.errors || error.message);
        // Даже при ошибтке вызываем callback для закрытия модалки
        if(payload.callback !== undefined) {
            payload.callback()
        }
    }).finally(function () {
        context.commit('setLoaging', false);
    });
}

/**
 * Изменить цену заказа (только admin)
 *
 * @param context
 * @param payload
 */
export const sendChangePrice = (context, payload) => {
    let promise = axios.post(API_ORDER + '/changePrice/' + payload.id, {
        'price': payload.price,
    });
    promise.then(function (response) {

        context.commit('chancePrice', {
            'id': payload.id,
            'price': response.data.price,
        })
    }).catch(function (error) {
        console.error(error);
        if(payload.callbackError !== undefined) {
            payload.callbackError(error.response.data.errors?.price?.join())
        }
    });
}

export const getUrlForPdf = (context, payload) => {
    return new Promise((resolve, reject) => {
    let promise = axios.get(API_ORDER + '/getTicketPdf/' + payload);
    return promise.then(function (response) {
        //console.log('in promise ', response);
        response.data.listUrl.forEach(function (item) {
             resolve(item);
        })
        //console.log(response)
    }).catch(function (error) {
        //console.error(error);
        context.commit('setError', error.response.data.errors);
        reject(error);
    });
    });
};


// получение персональной ссылки
export const pullInviteLink = (context, payload) => {
    return new Promise((resolve, reject) => {
        let promise = axios.get(API_INVITE + '/getInviteLink');
        return promise.then(function (response) {
            console.log(response.data);
            payload.callback(response.data);
        }).catch(function (error) {
            context.commit('setError', error);
            reject(error);
        });
    });
};

export const clearError = (context) => {
    context.commit('setError', []);
};

/**
 * Установить фильтр
 *
 * @param context
 * @param payload
 */
export const setFilter = (context, payload) => {
    context.commit('setFilter', payload);
};

/**
 * Установить загрузку
 *
 * @param context
 */
export const loading = (context) => {
    context.commit('setLoaging', true);
};