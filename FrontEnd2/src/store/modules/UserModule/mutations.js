export const setError = (state, payload) => {
    state.dataError = payload;
};
/**
 * Записать токен
 *
 * @param state
 * @param payload
 */
export const setToken = async (state, payload) => {
    state.userToken = payload.type + ' ' + payload.token;
    state.userTimeLifeForToken = payload.lifetime;

    await localStorage.setItem('user.token', payload.type + ' ' + payload.token); // сохранение токена пользователя на стороне клиента
    await localStorage.setItem('user.token.lifetime', payload.lifetime);
};

export const removeToken = async (state) => {
    state.userToken = null;
    state.userTimeLifeForToken = null;

    await localStorage.removeItem('user.token');
    await localStorage.removeItem('user.token.lifetime');
    await localStorage.removeItem('user.email');
    await localStorage.removeItem('user.id');
};

/**
 * Записать данные пользователя
 *
 * @param state
 * @param payload
 */
export const setUserInfo = async (state, payload) => {
    state.userInfo = payload;
    await localStorage.setItem('user.email', payload.email);
    await localStorage.setItem('user.id', payload.id);
};
