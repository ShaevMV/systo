import axios from 'axios';

const API = '/api/v1/templateBinding';

/** Список привязок. */
export const loadList = (context) => {
    context.commit('setIsLoading', true);
    return axios
        .post(API + '/getList')
        .then((r) => {
            context.commit('setList', r.data.list ?? []);
            return r.data.list ?? [];
        })
        .finally(() => context.commit('setIsLoading', false));
};

/** Справочники для формы: фестивали, типы билетов, шаблоны email/pdf. */
export const loadRefs = (context) => {
    return Promise.all([
        axios.get('/api/v1/festival/getFestivalList').then((r) => r.data ?? []),
        axios.post('/api/v1/ticketType/getList', { filter: {} }).then((r) => r.data.list ?? r.data ?? []),
        axios.post('/api/v1/template/getList', { filter: { kind: 'email' } }).then((r) => r.data.list ?? []),
        axios.post('/api/v1/template/getList', { filter: { kind: 'pdf' } }).then((r) => r.data.list ?? [])
    ]).then(([festivals, ticketTypes, emailTemplates, pdfTemplates]) => {
        context.commit('setRefs', { festivals, ticketTypes, emailTemplates, pdfTemplates });
    });
};

export const create = (context, payload) => axios.post(API + '/create', { data: payload.data }).then((r) => r.data);

export const edit = (context, payload) => axios.post(API + '/edit/' + payload.id, { data: payload.data }).then((r) => r.data);

export const remove = (context, payload) => axios.delete(API + '/delete/' + payload.id).then((r) => r.data);
