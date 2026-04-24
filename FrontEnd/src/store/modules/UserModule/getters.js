export const getError = state => type => {
    if(state.dataError !== undefined && state.dataError[type] !== undefined){
        if(typeof state.dataError[type] === "object"){
            return state.dataError[type][0];
        }
        return state.dataError[type];
    }
    return '';
};

/**
 * Проверить авторизацию
 *
 * @param state
 * @returns {boolean}
 */
export const isAuth = state => {
    return state.userToken !== null && state.userToken.length > 0;
};

/**
 * Проверить авторизацию
 *
 * @param state
 * @returns {boolean}
 */
export const isAdmin = state => {
    return state.userInfo.admin;
};

export const isSeller = state => {
    return state.userInfo.seller;
};

/**
 * Проверить авторизацию
 *
 * @param state
 * @returns {boolean}
 */
export const isManager = state => {
    return state.userInfo.manager;
};

/**
 * Проверить авторизацию
 *
 * @param state
 * @returns {boolean}
 */
export const isPusher = state => {
    return state.userInfo.pusher;
};

/**
 * Проверить роль куратора (curator или curator_pusher)
 *
 * @param state
 * @returns {boolean}
 */
export const isCurator = state => {
    return state.userInfo.curator;
};

/**
 * Вывести email
 *
 * @param state
 * @returns {string|null}
 */
export const getEmail = state => {
    return state.userInfo.email;
};

/**
 * Вывести id пользователя
 *
 * @param state
 * @returns {string|null}
 */
export const getIdUser = state => {
    return state.userInfo.id;
};


export const getUserData = state => type => {
    if(state.userData !== undefined && state.userData[type] !== undefined){
        if(typeof state.userData[type] === "object"){
            return state.userData[type];
        }
        return state.userData[type];
    }
    return null;
}

export const getUserInfo = state => {
    return state.userInfo;
}
