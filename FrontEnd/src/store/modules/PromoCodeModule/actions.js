import axios from 'axios';

export const loadListPromoCode = (context) => {
    let promise = axios.get('/api/v1/festival/getListPromoCode');
    promise.then(function (response) {
        console.log(response.data.success);
        context.commit('setListPromoCode', response.data.listPromoCode);
    }).catch(function (error) {
        console.error(error);
        context.commit('setError', error.response.data.errors);
    });
};


export const loadPromoCodeItem = (context, payload) => {
    if (payload !== null && payload.length > 0) {
        let promise = axios.get('/api/v1/festival/getItemPromoCode/' + payload)
        promise.then(function (response) {
            console.log(response.data.success);
            context.commit('setItemPromoCode', response.data);
        }).catch(function (error) {
            console.error(error);
            context.commit('setError', error.response.data.errors);
        });
    }
};

export const sendSavePromoCode = (context, payload) => {
    let promise = axios.post('/api/v1/festival/savePromoCode/' + payload.id, {
        'name' : payload.name,
        'discount': payload.discount,
        'is_percent': payload.is_percent,
        'active': payload.active,
        'limit': payload.limit
    })
    promise.then(function (response) {
        if(payload.callback !== undefined) {
            payload.callback(response.massage)
        }
    }).catch(function (error) {
        console.error(error);
        context.commit('setError', error.response.data.errors);
    });
};

export const clearError = (context) => {
    context.commit('setError', []);
};
