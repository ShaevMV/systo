export const setError = (state, payload) => {
    state.dataError = payload;
};

/**
 * Записать токен
 *
 * @param state
 * @param payload
 */
export const setToken = (state, payload) => {
    state.userToken = payload.type + ' ' + payload.token;
    state.userTimeLifeForToken = payload.lifetime;

    localStorage.setItem('user.token', payload.type + ' ' + payload.token);
    localStorage.setItem('user.token.lifetime', payload.lifetime);
};

export const removeToken = (state) => {
    state.userToken = null;
    state.userTimeLifeForToken = null;
    state.userInfo = { id: null, email: null, admin: false, manager: false, seller: false, pusher: false, curator: false };

    // Удаляем только ключи пользователя, не трогая остальной localStorage
    ['user.token', 'user.token.lifetime', 'user.email', 'user.id',
     'user.isAdmin', 'user.isManager', 'user.isSeller', 'user.isPusher', 'user.isCurator', 'user.role']
        .forEach(key => localStorage.removeItem(key));
};

/**
 * Записать данные пользователя
 *
 * @param state
 * @param payload
 */
export const setUserInfo = (state, payload) => {
    const role = payload.role || '';

    // Обновляем state напрямую — геттеры isAdmin/isSeller/etc. работают без перезагрузки страницы
    state.userInfo = {
        id:      payload.id,
        email:   payload.email,
        admin:   payload.is_admin || role === 'admin',
        manager: role === 'manager',
        seller:  role === 'seller',
        pusher:  role === 'pusher',
        curator: role === 'curator',
    };

    localStorage.setItem('user.email',     payload.email);
    localStorage.setItem('user.id',        payload.id);
    localStorage.setItem('user.isAdmin',   String(state.userInfo.admin));
    localStorage.setItem('user.isManager', String(state.userInfo.manager));
    localStorage.setItem('user.isSeller',  String(state.userInfo.seller));
    localStorage.setItem('user.isPusher',  String(state.userInfo.pusher));
    localStorage.setItem('user.isCurator', String(state.userInfo.curator));
    localStorage.setItem('user.role',      role);
};

export const setUserData = (state, payload) => {
    state.userData = payload;
};
