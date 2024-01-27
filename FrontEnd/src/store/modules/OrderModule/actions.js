import axios from 'axios';

/**
 * Отправить данные на создание билета
 *
 * @param context
 * @param payload
 */
export const goToCreateOrderTicket = (context, payload) => {
    let promise = axios.post('/api/v1/festival/ticketsOrder/create', payload);
    promise.then(function (response) {
        console.log(response.data.success);
        payload.callback(response.data.success, response.data.massage);
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
    let promise = axios.get('/api/v1/festival/ticketsOrder/getUserList');
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
    let promise = axios.post('/api/v1/festival/ticketsOrder/getList', payload);
    promise.then(function (response) {
        context.commit('setOrderUserList', response.data.list);
        context.commit('setTotalNumber', response.data.totalNumber);
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
        let promise = axios.get('/api/v1/festival/ticketsOrder/getItem/' + payload);
        promise.then(function (response) {
            context.commit('setOrderItem', response.data);
        }).catch(function (error) {
            console.error(error);
            context.commit('setError', error.response.data.errors);
        });
    }
};

/**
 * Отправить комментарий к заказу
 *
 *
 * @param context
 * @param payload
 */
export const sendCommentByOrder = (context, payload) => {
    let promise = axios.post('/api/v1/festival/ticketsOrder/sendComment/', payload);
    promise.then(function (response) {
        context.commit('addCommentByOrderItem', {
            'comment': payload.message,
            'is_checkin': true,
            'user_id': localStorage.getItem('user.id'),
            'created_at': response.data.created_at
        });
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
export const sendToChanceStatus = (context, payload) => {
    let promise = axios.post('/api/v1/festival/ticketsOrder/toChanceStatus/' + payload.id, {
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
        console.log(response);
    }).catch(function (error) {
        console.error(error);
        context.commit('setError', error.response.data.errors);
    });
}

export const getUrlForPdf = (context, payload) => {
    return new Promise((resolve, reject) => {
    let promise = axios.get('/api/v1/festival/ticketsOrder/getTicketPdf/' + payload);
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

export const clearError = (context) => {
    context.commit('setError', []);
};
