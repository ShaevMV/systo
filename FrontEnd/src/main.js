import {createApp} from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import axios from 'axios';

window.store = store;

axios.defaults.baseURL = process.env.VUE_APP_BACKEND_ENDPOINT
axios.defaults.withCredentials = true


// добавить токент клиента
axios.interceptors.request.use(function (config) {
    let token = localStorage['user.token'];
    let lifetime = localStorage['user.token.lifetime'];
    if (token) {
        config.headers.Authorization = token;

        if (config.url !== '/api/refresh' && config.url !== '/api/logout'){
            console.log(config.url)
            if (Math.trunc(Date.now() / 1000) > +lifetime) {
                store.dispatch('appUser/tokenRefresh').catch(r => {
                    console.error(r)
                });
            }
        }
    }

    return config;
}, function (error) {
    console.log('unauthorized, logging out ...');
    return Promise.reject(error.response);
});

createApp(App).use(store).use(router).mount('#app')
