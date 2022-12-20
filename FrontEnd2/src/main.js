/**
 =========================================================
 * Vue Soft UI Dashboard - v3.0.0
 =========================================================

 * Product Page: https://creative-tim.com/product/vue-soft-ui-dashboard
 * Copyright 2022 Creative Tim (https://www.creative-tim.com)

 Coded by www.creative-tim.com

 =========================================================

 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 */

import {createApp} from "vue";
import App from "./App.vue";
import store from "./store";
import router from "./router";
import "./assets/css/nucleo-icons.css";
import "./assets/css/nucleo-svg.css";
import SoftUIDashboard from "./soft-ui-dashboard";
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
        if (config.url !== '/api/refresh') {
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

const appInstance = createApp(App);
appInstance.use(store);
appInstance.use(router);
appInstance.use(SoftUIDashboard);
appInstance.mount("#app");
