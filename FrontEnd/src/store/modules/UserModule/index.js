import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

export default {
    namespaced: true,
    state: {
        userToken: localStorage.getItem('user.token') || null,
        userTimeLifeForToken: localStorage.getItem('user.token.lifetime') || null,
        userInfo: {
            'id':      localStorage.getItem('user.id') || null,
            'email':   localStorage.getItem('user.email') || null,
            'admin':   localStorage.getItem('user.isAdmin') === 'true' || localStorage.getItem('user.role') === 'admin',
            'manager': localStorage.getItem('user.isManager') === 'true' || localStorage.getItem('user.role') === 'manager',
            'seller':  localStorage.getItem('user.isSeller') === 'true' || localStorage.getItem('user.role') === 'seller',
            'pusher':  localStorage.getItem('user.isPusher') === 'true' || localStorage.getItem('user.role') === 'pusher',
            'curator': localStorage.getItem('user.isCurator') === 'true' || localStorage.getItem('user.role') === 'curator' || localStorage.getItem('user.role') === 'curator_pusher',
        },
        userData: {
            'city': null,
            'phone': null,
            'name': null,
        },
        dataError: [],
    },
    getters,
    actions,
    mutations
};
