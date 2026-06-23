import axios from 'axios';

// CRUD фестивалей (мастер каталога на org). Бэкенд: /api/v1/festival/* (AF-7).
// Не путать с FestivalTicketsModule — тот грузит типы билетов/оплаты ПО фестивалю.
const API = '/api/v1/festival';

/**
 * Список фестивалей с фильтром + сортировкой.
 * POST /getList { filter:{ name?, year?, active? }, orderBy:{ name } } → { success, list:[{id,name,year,active}] }
 * Не путать со старым /getFestivalList (формат витрины { festivalDto:[…] }).
 */
export const loadList = (context, payload = {}) => {
    context.commit('setIsLoading', true);
    context.commit('clearError');
    const body = { filter: {}, orderBy: {}, ...payload };
    return axios
        .post(API + '/getList', body)
        .then((r) => {
            context.commit('setList', r.data?.list ?? []);
            return r.data?.list ?? [];
        })
        .catch(() => {
            context.commit('setList', []);
            return [];
        })
        .finally(() => context.commit('setIsLoading', false));
};

/**
 * Один фестиваль.
 * GET /getItem/{id} → { success, item } | { success:false, message }
 */
export const loadItem = (context, payload) => {
    return axios.get(API + '/getItem/' + payload.id).then((r) => {
        if (r.data?.success !== false) {
            context.commit('setItem', r.data?.item ?? {});
        }
        return r.data;
    });
};

/**
 * Создать фестиваль.
 * POST /create { data:{ name, year, active } } → { success, item, message } | 422 { errors }
 */
export const create = (context, payload) => {
    context.commit('clearError');
    return axios
        .post(API + '/create', { data: payload.data })
        .then((r) => r.data)
        .catch((e) => {
            context.commit('setError', e.response?.data?.errors ?? {});
            throw e;
        });
};

/**
 * Отредактировать фестиваль.
 * POST /edit/{id} { data:{ name, year, active } } → { success, item, message } | 422 { errors }
 */
export const edit = (context, payload) => {
    context.commit('clearError');
    return axios
        .post(API + '/edit/' + payload.id, { data: payload.data })
        .then((r) => r.data)
        .catch((e) => {
            context.commit('setError', e.response?.data?.errors ?? {});
            throw e;
        });
};

/**
 * Soft-delete фестиваля.
 * DELETE /delete/{id} → { success }
 */
export const remove = (context, payload) => axios.delete(API + '/delete/' + payload.id).then((r) => r.data);

/**
 * Журнал изменений фестиваля.
 * GET /getHistory/{id} → { success, history:[{ event_name, payload, actor_name, actor_email, actor_type, occurred_at }] }
 */
export const loadHistory = (context, payload) => {
    context.commit('setHistoryLoading', true);
    context.commit('setHistory', []);
    return axios
        .get(API + '/getHistory/' + payload.id)
        .then((r) => {
            context.commit('setHistory', r.data?.history ?? []);
            return r.data?.history ?? [];
        })
        .catch(() => {
            context.commit('setHistory', []);
            return [];
        })
        .finally(() => context.commit('setHistoryLoading', false));
};
