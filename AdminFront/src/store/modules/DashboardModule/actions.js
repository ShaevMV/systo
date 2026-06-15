import axios from 'axios';

const API = '/api/v1/qrOrder';

/**
 * Загрузить сводные метрики qr-заказов для дашборда (read-only).
 * payload.filter: { festival_id?, date_from?, date_to? } — любое поле опционально.
 */
export const loadStats = (context, payload = {}) => {
    const filter = payload.filter ?? context.getters.getFilter;

    if (payload.filter !== undefined) {
        context.commit('setFilter', filter);
    }

    context.commit('setIsLoading', true);

    return new Promise((resolve, reject) => {
        axios
            .post(API + '/getStats', { filter })
            .then((response) => {
                context.commit('setStats', response.data.stats ?? {});
                resolve(response.data);
            })
            .catch((error) => {
                context.commit('setError', error.response?.data?.errors ?? []);
                reject(error);
            })
            .finally(() => {
                context.commit('setIsLoading', false);
            });
    });
};

export const clearError = (context) => {
    context.commit('setError', []);
};
