import axios from 'axios';
import { notify, errorText } from '@/lib/notify';

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
            return Promise.reject(error);
        }

        // Централизованный показ ошибок (Фаза A). Единый сток — чтобы НИ ОДНА ошибка API
        // не осталась незамеченной. Пропускаем:
        //  - skipAutoNotify (вызывающий показывает сам: светофор скана, фоновый синк, поиск);
        //  - офлайн-сеть (это штатный сценарий КПП — есть индикатор в шапке, тост = шум на каждый запрос).
        const skip = error?.config?.meta?.skipAutoNotify;
        const offline = !error.response && !navigator.onLine;
        if (!skip && !offline) {
            if (!error.response) {
                notify('error', 'Нет связи с сервером', 'Проверьте интернет и повторите');
            } else if (status >= 500) {
                notify('error', 'Ошибка сервера', 'Попробуйте ещё раз');
            } else {
                notify('error', errorText(error)); // 422/403/прочие 4xx — человекочитаемо
            }
        }

        return Promise.reject(error);
    }
);
