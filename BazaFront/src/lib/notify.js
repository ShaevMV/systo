// ─────────────────────────────────────────────────────────────────────────────
//  Единая точка уведомлений КПП (Фаза A системы ошибок).
//
//  Диспатчит window-событие 'app-notify' → слушатель в App.vue кладёт в PrimeVue Toast
//  (+ звук/вибро). Через ОКНО, а не useToast напрямую, — чтобы вызывать и ВНЕ Vue-setup
//  (например из axios-перехватчика http.js).
//
//  Уровни: success | info | warn | error (+ critical → рендерится как error).
//  Принцип: пользователь видит ВЕРДИКТ, а не диагноз — никаких HTTP-кодов/трейсов.
// ─────────────────────────────────────────────────────────────────────────────

export function notify(severity, summary, detail) {
    window.dispatchEvent(
        new CustomEvent('app-notify', { detail: { severity, summary, detail: detail || null } })
    );
}

export const notifySuccess = (summary, detail) => notify('success', summary, detail);
export const notifyInfo = (summary, detail) => notify('info', summary, detail);
export const notifyWarn = (summary, detail) => notify('warn', summary, detail);
export const notifyError = (summary, detail) => notify('error', summary, detail);

/**
 * Человекочитаемый текст из axios-ошибки — БЕЗ HTTP-кодов/трейсов наружу.
 *  - 422 → склейка сообщений валидации Laravel,
 *  - 403 → «Нет доступа»,
 *  - строcovый/`.message` ответ → как есть,
 *  - иначе → fallback.
 */
export function errorText(e, fallback = 'Не удалось выполнить') {
    const r = e?.response;
    if (!r) return fallback; // сеть/таймаут — решает перехватчик (см. http.js)
    if (r.status === 422 && r.data && r.data.errors) {
        const msgs = Object.values(r.data.errors).flat();
        if (msgs.length) return msgs.join('; ');
    }
    if (r.status === 403) return 'Нет доступа';
    if (typeof r.data === 'string' && r.data.trim() !== '') return r.data;
    if (r.data && typeof r.data.message === 'string' && r.data.message.trim() !== '') return r.data.message;
    return fallback;
}

/** Показать ошибку из axios-исключения одним вызовом. */
export const notifyFromError = (e, fallback) => notify('error', errorText(e, fallback));
