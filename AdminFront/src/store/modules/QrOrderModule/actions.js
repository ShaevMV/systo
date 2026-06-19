import axios from 'axios';

const API = '/api/v1/qrOrder';

/**
 * Загрузить страницу списка qr-заказов (server-side: фильтры + пагинация + сортировка).
 * payload: { filter, orderBy, page, perPage } — любое поле опционально.
 */
export const loadList = (context, payload = {}) => {
    const page = payload.page ?? context.state.pagination.page;
    const perPage = payload.perPage ?? context.state.pagination.perPage;

    const filter = payload.filter ?? context.getters.getFilter;
    const orderBy = payload.orderBy ?? context.getters.getOrderBy;

    // Держим выбранные фильтр/сортировку в state, чтобы пагинация/ре-рендер их не теряли.
    if (payload.filter !== undefined) {
        context.commit('setFilter', filter);
    }
    if (payload.orderBy !== undefined) {
        context.commit('setOrderBy', orderBy);
    }

    const body = { filter, orderBy, page, perPage };

    context.commit('setIsLoading', true);

    return new Promise((resolve, reject) => {
        axios
            .post(API + '/getList', body)
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

/**
 * Загрузить деталь заказа (с payload: гости, цены, локация и т.д.).
 */
export const loadItem = (context, payload) => {
    return new Promise((resolve, reject) => {
        axios
            .get(API + '/getItem/' + payload.id)
            .then((response) => {
                context.commit('setItem', response.data.item ?? {});
                resolve(response.data.item);
            })
            .catch((error) => {
                context.commit('setError', error.response?.data?.errors ?? []);
                reject(error);
            });
    });
};

/**
 * Загрузить историю заказа (created / status_changed / issued, actor = qr).
 */
export const loadHistory = (context, payload) => {
    return new Promise((resolve, reject) => {
        axios
            .get(API + '/getHistory/' + payload.id)
            .then((response) => {
                context.commit('setHistory', response.data.history ?? []);
                resolve(response.data.history);
            })
            .catch((error) => {
                context.commit('setError', error.response?.data?.errors ?? []);
                reject(error);
            });
    });
};

/**
 * Весь путь заказа (Ф5): заказ + история(шаги) + билеты(PDF) + письма(статусы) одним запросом.
 */
export const loadPipeline = (context, payload) => {
    return new Promise((resolve, reject) => {
        axios
            .get(API + '/getPipeline/' + payload.id)
            .then((response) => {
                context.commit('setItem', response.data.order ?? {});
                context.commit('setHistory', response.data.history ?? []);
                context.commit('setTickets', response.data.tickets ?? []);
                context.commit('setEmails', response.data.emails ?? []);
                context.commit('setBaza', response.data.baza ?? []);
                resolve(response.data);
            })
            .catch((error) => {
                context.commit('setError', error.response?.data?.errors ?? []);
                reject(error);
            });
    });
};

/** Ссылки на PDF билетов заказа (для скачивания). Возвращает массив url. */
export const downloadTickets = (context, payload) => {
    return axios.get(API + '/getTicketPdf/' + payload.id).then((r) => r.data.listUrl ?? []);
};

export const setFilter = (context, payload) => {
    context.commit('setFilter', payload);
};

/**
 * Переключить сортировку по полю (asc ↔ desc), как в LocationModule.
 */
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
