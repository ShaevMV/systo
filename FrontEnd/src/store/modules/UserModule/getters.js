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

