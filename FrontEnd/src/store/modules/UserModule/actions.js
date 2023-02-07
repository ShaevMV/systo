import axios from 'axios';

axios.defaults.baseURL = process.env.VUE_APP_BACKEND_ENDPOINT
axios.defaults.withCredentials = true

/**
 * Авторизоваться
 *
 * @param context
 * @param payload
 */
export const toLogin = (context, payload) => {
    let promise = axios.post('/api/login', payload);
    promise.then(async function (response) {
        if (response.data.status === 'success') {
            context.commit('setToken', response.data.authorisation);
            context.commit('setUserInfo', response.data.user);
            payload.callback(response.data.user.admin);
        }
    }).catch(function (error) {
        if (error.response.data.errors === undefined) {
            context.commit('setError', {
                'main': error.response.data.message
            });
        } else {
            context.commit('setError', error.response.data.errors);
        }
    });
};

/**
 * Регистрация
 *
 * @param context
 * @param payload
 */
export const toRegistration = (context, payload) => {
    let promise = axios.post('/api/register', payload);
    promise.then(async function (response) {
        if (response.data.status === 'success') {
            context.commit('setToken', response.data.authorisation);
            context.commit('setUserInfo', response.data.user);
            payload.callback();
        }
    }).catch(function (error) {
        context.commit('setError', error.response.data.errors);
    });
};

/**
 * Восстановления пароля
 *
 * @param context
 * @param payload
 */
export const toForgotPassword = (context, payload) => {
    context.commit('setError', []);
    let promise = axios.post('/api/forgot-password', {
        email: payload.email
    });
    promise.then(async function (response) {
        payload.callback(response.data.message);
    }).catch(function (error) {
        payload.callback(error.response.data.errors.email);
    });
}

/**
 * Сменить данные пользователя
 *
 * @param context
 * @param payload
 */
export const editProfile = (context, payload) => {
    let promise = axios.post('/api/editProfile', {
        city: payload.city,
        phone: payload.phone,
        name: payload.name,
    });
    promise.then(async function (response) {
        payload.callback(response.data.message);
    }).catch(function (error) {
        payload.callback(error.response.data.errors);
    });
}

/**
 * Получить данные о пользователе
 *
 * @param context
 */
export const loadUserData = (context) => {
    let promise = axios.get('/api/user');
    promise.then(async function (response) {
        context.commit('setUserData', response.data);
    }).catch(function (error) {
        console.error(error)
    });
}

/**
 * Пересобрать токен
 *
 * @param context
 */
export const tokenRefresh = (context) => {
    let promise = axios.post('/api/refresh');
    promise.then(async function (response) {
        if (response.data.status === 'success') {
            context.commit('setToken', response.data.authorisation);
            context.commit('setUserInfo', response.data.user);
        }
    }).catch(function (error) {
        if (error.response.status === 401) {
            context.dispatch('logOut').then(r => console.log(r));
        }
        console.error(error);
    })
};

/**
 * Сменить пароль
 *
 * @param context
 * @param payload
 */
export const changePassword = (context, payload) => {
    let promise = axios.post('/api/resetPassword', {
        token: payload.token,
        password: payload.password,
        password_confirmation: payload.password_confirmation,
    });
    promise.then(async function (response) {
        if (response.data.status === 'success') {
            context.commit('setToken', response.data.authorisation);
            context.commit('setUserInfo', response.data.user);
            payload.callback()
        }
    }).catch(function (error) {
        if (error.response.status === 401) {
            context.dispatch('logOut').then(r => console.log(r));
        }
        console.error(error);
    })
};

/**
 * Проверить доступ
 *
 * @param context
 * @param payload
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
export const isCorrectRole = (context, payload) => {
    return axios.post('/api/isCorrectRole', payload);
};


/**
 * Сменить пароль
 *
 * @param context
 * @param payload
 */
export const editPassword = (context, payload) => {
    let promise = axios.post('/api/editPassword', {
        password: payload.password,
        password_confirmation: payload.password_confirmation,
    });
    promise.then(async function (response) {
        payload.callback(response.data.message);
    }).catch(function (error) {
        console.error(error.response.data);
        payload.callback(error.response.data.message);
    });
};

/**
 * Разлогиниться
 *
 * @param context
 */
export const logOut = (context) => {
    let promise = axios.post('/api/logout');
    promise.then(async function () {
        context.commit('removeToken');
        location.reload();
    }).catch(function (error) {
        console.error(error);
        context.commit('removeToken');
    });
};
