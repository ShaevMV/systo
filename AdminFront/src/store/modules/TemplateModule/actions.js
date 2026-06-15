import axios from 'axios';

const API = '/api/v1/template';

/** Список шаблонов (фильтр по kind). */
export const loadList = (context, payload = {}) => {
    const filter = payload.filter ?? context.getters.getFilter;
    if (payload.filter !== undefined) {
        context.commit('setFilter', filter);
    }

    context.commit('setIsLoading', true);

    return axios
        .post(API + '/getList', { filter })
        .then((response) => {
            context.commit('setList', response.data.list ?? []);
            return response.data.list ?? [];
        })
        .catch((error) => {
            context.commit('setError', error.response?.data?.errors ?? []);
            throw error;
        })
        .finally(() => context.commit('setIsLoading', false));
};

/** Один шаблон (с body/draft_body). */
export const loadItem = (context, payload) => {
    context.commit('setIsLoading', true);
    return axios
        .get(API + '/getItem/' + payload.id)
        .then((response) => {
            context.commit('setItem', response.data.item ?? {});
            return response.data.item ?? {};
        })
        .finally(() => context.commit('setIsLoading', false));
};

export const create = (context, payload) => {
    return axios.post(API + '/create', { data: payload.data }).then((r) => r.data);
};

export const edit = (context, payload) => {
    return axios.post(API + '/edit/' + payload.id, { data: payload.data }).then((r) => r.data);
};

/** Сохранить черновик (прод не трогается). */
export const saveDraft = (context, payload) => {
    return axios.post(API + '/saveDraft/' + payload.id, { draft_body: payload.draftBody }).then((r) => r.data);
};

/** Опубликовать body (+ снапшот версии). */
export const publish = (context, payload) => {
    return axios
        .post(API + '/publish/' + payload.id, { body: payload.body, comment: payload.comment ?? null })
        .then((r) => {
            if (r.data.item) context.commit('setItem', r.data.item);
            return r.data;
        });
};

export const activate = (context, payload) => {
    return axios.post(API + '/activate/' + payload.id, { active: payload.active }).then((r) => {
        if (r.data.item) context.commit('setItem', r.data.item);
        return r.data;
    });
};

export const loadVersions = (context, payload) => {
    return axios.get(API + '/versions/' + payload.id).then((r) => {
        context.commit('setVersions', r.data.versions ?? []);
        return r.data.versions ?? [];
    });
};

/** Журнал изменений шаблона (domain_history): кто/что/когда. */
export const loadHistory = (context, payload) => {
    return axios.get(API + '/history/' + payload.id).then((r) => {
        context.commit('setHistory', r.data.history ?? []);
        return r.data.history ?? [];
    });
};

export const rollback = (context, payload) => {
    return axios.post(API + '/rollback/' + payload.id + '/' + payload.versionId).then((r) => {
        if (r.data.item) context.commit('setItem', r.data.item);
        return r.data;
    });
};

export const loadVariables = (context, payload) => {
    return axios.get(API + '/variables/' + (payload.slug || payload.kind) + '?kind=' + payload.kind).then((r) => {
        context.commit('setVariables', r.data.variables ?? []);
        return r.data.variables ?? [];
    });
};

/**
 * Предпросмотр. email → возвращает HTML-строку; pdf → blob-URL (для iframe).
 * Ошибка рендера (422) пробрасывается — компонент покажет сообщение.
 */
export const preview = (context, payload) => {
    if (payload.kind === 'pdf') {
        return axios
            .post(API + '/preview', { kind: 'pdf', slug: payload.slug, body: payload.body }, { responseType: 'blob' })
            .then((r) => ({ type: 'pdf', url: URL.createObjectURL(r.data) }));
    }

    return axios
        .post(API + '/preview', { kind: 'email', slug: payload.slug, body: payload.body })
        .then((r) => ({ type: 'email', html: r.data.html }));
};

export const clearError = (context) => context.commit('setError', []);
