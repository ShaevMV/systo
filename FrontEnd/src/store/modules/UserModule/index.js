import * as getters from './getters';
import * as actions from './actions';
import * as mutations from './mutations';

export default {
    namespaced: true,
    state: {
        userToken: localStorage.getItem('user.token') || null,
        userTimeLifeForToken: localStorage.getItem('user.token.lifetime') || null,
        userInfo: {
            'id': localStorage.getItem('user.id') || null,
            'email': localStorage.getItem('user.email') || null,
            'admin': localStorage.getItem('user.isAdmin') == 'true' || localStorage.getItem('user.role') == 'admin' || false,
            'manager': localStorage.getItem('user.isManager') == 'true' || localStorage.getItem('user.role') == 'manager' || false,
            'seller': localStorage.getItem('user.role') == 'seller' || localStorage.getItem('user.role') == 'seller' || false,
            'pusher': localStorage.getItem('user.role') == 'pusher' || localStorage.getItem('user.role') == 'pusher' || false,
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
