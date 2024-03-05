import {createApp} from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import axios from 'axios';
import 'v-calendar/dist/style.css';
import VCalendar from 'v-calendar';

window.store = store;

axios.defaults.baseURL = 'https://api.solarsysto.ru/'
axios.defaults.withCredentials = true
/*
if (localStorage['secret'] !== 'XyzWar') {
    window.location.href = 'https://ya.ru/';
}*/


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

createApp(App)
    .use(store)
    .use(router)
    .use(VCalendar)
    .mount('#app')
