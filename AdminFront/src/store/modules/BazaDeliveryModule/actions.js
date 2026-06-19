import axios from 'axios';

const API = '/api/v1/bazaDelivery';

/**
 * Страница списка доставок в baza (server-side: фильтры + пагинация + сортировка).
 * payload: { filter, orderBy, page, perPage } — любое поле опционально.
 */
export const loadList = (context, payload = {}) => {
    const page = payload.page ?? context.state.pagination.page;
    const perPage = payload.perPage ?? context.state.pagination.perPage;

    const filter = payload.filter ?? context.getters.getFilter;
    const orderBy = payload.orderBy ?? context.getters.getOrderBy;

    if (payload.filter !== undefined) {
        context.commit('setFilter', filter);
    }
    if (payload.orderBy !== undefined) {
        context.commit('setOrderBy', orderBy);
    }

    context.commit('setIsLoading', true);

    return new Promise((resolve, reject) => {
        axios
            .post(API + '/getList', { filter, orderBy, page, perPage })
            .then((response) => {
                context.commit('setList', response.data.list ?? []);
                context.commit('setPagination', {
                    page,
                    perPage,
                    total: response.data.totalNumber?.totalCount ?? 0
                });
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

/** Деталь доставки + таймлайн (domain_history aggregate_type=baza_delivery). */
export const loadItem = (context, payload) => {
    return new Promise((resolve, reject) => {
        axios
            .get(API + '/getItem/' + payload.id)
            .then((response) => {
                context.commit('setItem', response.data.item ?? {});
                context.commit('setHistory', response.data.history ?? []);
                resolve(response.data.item);
            })
            .catch((error) => {
                context.commit('setError', error.response?.data?.errors ?? []);
                reject(error);
            });
    });
};

/** Повторная доставка в baza (возвращает в очередь + ставит DeliverTicketToBazaJob). */
export const resend = (context, payload) => {
    return axios.post(API + '/resend/' + payload.id).then((r) => r.data);
};

/** Счётчики доставок по статусам (для дашборд-виджета «застрявшие билеты»). */
export const loadStats = (context, payload = {}) => {
    const body = {};
    if (payload.festival_id) body.festival_id = payload.festival_id;

    return axios.post(API + '/getStats', body).then((r) => {
        context.commit('setStats', r.data.stats ?? {});
        return r.data.stats;
    });
};

export const setFilter = (context, payload) => {
    context.commit('setFilter', payload);
};

export const setOrderBy = (context, payload) => {
    const orderByCurrent = context.getters.getOrderBy;

    if (Object.keys(orderByCurrent).length === 0 || Object.keys(orderByCurrent)[0] !== payload) {
        context.commit('setOrderBy', { [payload]: 'desc' });
    } else {
        const type = orderByCurrent[payload] === 'desc' ? 'asc' : 'desc';
        context.commit('setOrderBy', { [payload]: type });
    }
};

export const clearError = (context) => {
    context.commit('setError', []);
};
