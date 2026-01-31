import axios from 'axios';

const API_ORDER = '/api/v1/order'
const API_FESTIVAL = '/api/v1/festival'
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
    console.log(payload);
    let promise = axios.post(API_ORDER + '/getList', payload);
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
 * Загрузить заказ
 *
 * @param context
 * @param payload
 */
export const loadOrderItem = (context, payload) => {
    if (payload !== null && payload.length > 0) {
        let promise = axios.get(API_ORDER + '/getItem/' + payload.id);
        promise.then(function (response) {
            context.commit('setOrderItem', response.data.order);
        }).catch(function (error) {
            console.error(error);
            context.commit('setError', error.response.data.errors);
        });
    }
};

/**
 * Сменить статус у заказа
 *
 * @param context
 * @param payload
 */
export const sendToChanceStatus = (context, payload) => {
    let promise = axios.post(API_ORDER + 'toChanceStatus/' + payload.id, {
        'status': payload.status,
        'comment': payload.comment
    });
    promise.then(function (response) {
        if(payload.callback !== undefined) {
            payload.callback()
        }
        context.commit('chanceStatus', {
            'id': payload.id,
            'humanStatus': response.data.status.humanStatus,
            'status': response.data.status.name,
            'listCorrectNextStatus': response.data.status.listCorrectNextStatus,
        })
    }).catch(function (error) {
        console.error(error);
        context.commit('setError', error.response.data.errors);
    });
}

export const getUrlForPdf = (context, payload) => {
    return new Promise((resolve, reject) => {
    let promise = axios.get(API_ORDER + 'ticketsOrder/getTicketPdf/' + payload);
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
 * Установить загрузку
 *
 * @param context
 */
export const loading = (context) => {
    context.commit('setLoaging', true);
};