import axios from 'axios';

// HTTP-клиент BazaFront. Auth — СЕССИОННЫЕ куки Baza (НЕ JWT, в отличие от AdminFront):
// withCredentials шлёт session-куку (домен vhod.*), withXSRFToken берёт XSRF-TOKEN cookie
// и кладёт в заголовок X-XSRF-TOKEN — Laravel VerifyCsrfToken (web-группа) этого ждёт.
// Решение архитектора/PM (2026-06-20): онлайн = куки+CSRF; офлайн = PIN (отдельный слой, PR-6).
const base = import.meta.env.VITE_API_URL || '/';

export const http = axios.create({
    baseURL: base.replace(/\/$/, ''),
    withCredentials: true,
    withXSRFToken: true,
    headers: { Accept: 'application/json' }
});

// 401/419 (сессия протухла/нет) → на страницу логина Baza (старый Blade /login).
// Офлайн-очередь впуска (PR-4+) переживёт это: дренаж дождётся ре-логина.
http.interceptors.response.use(
    (r) => r,
    (error) => {
        const status = error?.response?.status;
        if (status === 401 || status === 419) {
            const loginUrl = (base.replace(/\/$/, '')) + '/login';
            if (!window.location.pathname.endsWith('/login')) {
                window.location.assign(loginUrl);
            }
        }
        return Promise.reject(error);
    }
);
