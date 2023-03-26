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


export const clearError = (context) => {
    context.commit('setError', []);
};
