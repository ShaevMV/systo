import { ref } from 'vue';
import axios from 'axios';

/**
 * Доменный слой над заказами org (`/api/v1/order/*`).
 *
 * Покрывает три варианта списка заказов (перенос со старого фронта):
 *   - оргвзносы (все)  POST /getList            (role seller,admin)
 *   - дружеские        POST /getListForFriendly (role pusher,admin,pusher_curator)
 *   - заказы-списки    POST /getListsList       (role admin,manager)
 * + смена статуса (машина статусов), история и деталь заказа.
 *
 * Сервер возвращает список как `{ list, totalNumber }`. Допустимые переходы
 * статуса берём ИЗ САМОГО заказа (`item.listCorrectNextStatus` = { statusKey: humanLabel }) —
 * матрицу НЕ хардкодим, бэкенд считает её на стороне `Status::getListNextStatus()`.
 *
 * axios уже несёт JWT-интерсепторы (см. main.js).
 *
 * @param {string} listEndpoint путь списка: '/api/v1/order/getList' | '/getListForFriendly' | '/getListsList'
 */
export function useOrders(listEndpoint) {
    const API = '/api/v1/order';

    const list = ref([]);
    const totalNumber = ref({});
    const loading = ref(false);
    const saving = ref(false);
    const error = ref(null);

    // Деталь заказа (Dialog) и история (Timeline).
    const item = ref({});
    const history = ref([]);
    const loadingItem = ref(false);
    const loadingHistory = ref(false);

    const asArray = (v) => (Array.isArray(v) ? v : []);

    /** Достаёт читаемое сообщение из ответа Laravel (message / первая ошибка валидации). */
    const messageFromError = (e, fallback) => {
        const data = e?.response?.data;
        if (data?.message) return data.message;
        if (data?.errors) {
            const first = Object.values(data.errors).flat()[0];
            return first ?? fallback;
        }
        return fallback;
    };

    /**
     * Загрузить список заказов по фильтру.
     * Тело запроса — плоский объект фильтра (как в старом FilterOrder).
     * @param {object} filter
     */
    async function loadList(filter = {}) {
        loading.value = true;
        error.value = null;
        try {
            const r = await axios.post(`${API}${listEndpoint}`, filter);
            list.value = asArray(r.data?.list);
            totalNumber.value = r.data?.totalNumber ?? {};
            return list.value;
        } catch (e) {
            error.value = messageFromError(e, 'Не удалось загрузить список заказов');
            list.value = [];
            totalNumber.value = {};
            return [];
        } finally {
            loading.value = false;
        }
    }

    /** Загрузить деталь заказа (для Dialog). */
    async function loadItem(id) {
        loadingItem.value = true;
        item.value = {};
        try {
            const r = await axios.get(`${API}/getItem/${id}`);
            item.value = r.data?.order ?? {};
            return item.value;
        } catch (e) {
            error.value = messageFromError(e, 'Не удалось загрузить заказ');
            return {};
        } finally {
            loadingItem.value = false;
        }
    }

    /** Загрузить историю заказа (Timeline). admin-only эндпоинт. */
    async function loadHistory(id) {
        loadingHistory.value = true;
        history.value = [];
        try {
            const r = await axios.get(`${API}/getHistory/${id}`);
            history.value = asArray(r.data?.history);
            return history.value;
        } catch (e) {
            // История доступна только admin — для seller/pusher/manager молча пустим.
            history.value = [];
            return [];
        } finally {
            loadingHistory.value = false;
        }
    }

    /**
     * Сменить статус заказа.
     *
     * Бэкенд (`POST /toChangeStatus/{id}`):
     *  - `comment` обязателен при DIFFICULTIES_AROSE / DIFFICULTIES_AROSE_LIST;
     *  - `liveList` обязателен при LIVE_TICKET_ISSUED — это ОБЪЕКТ, ключ = guest.id,
     *    значение = номер живого билета (бэкенд делает `new Uuid($key)` по ключу!).
     *
     * @param {object} p { id, status, comment?, liveList? }
     * @returns {Promise<{ ok: boolean, status?: object, order?: object, error?: object|string }>}
     */
    async function changeStatus(p) {
        saving.value = true;
        error.value = null;
        try {
            const r = await axios.post(`${API}/toChangeStatus/${p.id}`, {
                status: p.status,
                comment: p.comment ?? null,
                liveList: p.liveList ?? undefined
            });
            if (r.data?.success === false) {
                error.value = r.data.errors ?? 'Не удалось сменить статус';
                return { ok: false, error: error.value };
            }
            return { ok: true, status: r.data?.status, order: r.data?.order };
        } catch (e) {
            // 422 — ошибки валидации (errors: { comment: [...], liveList: [...] }).
            error.value = e?.response?.data?.errors ?? messageFromError(e, 'Не удалось сменить статус');
            return { ok: false, error: error.value };
        } finally {
            saving.value = false;
        }
    }

    return {
        list,
        totalNumber,
        loading,
        saving,
        error,
        item,
        history,
        loadingItem,
        loadingHistory,
        loadList,
        loadItem,
        loadHistory,
        changeStatus
    };
}

/**
 * Цвет/severity статуса заказа (обычные/live + list-статусы).
 * Возвращает severity для PrimeVue Tag.
 */
export function orderStatusSeverity(status) {
    switch (status) {
        case 'paid':
        case 'paid_for_live':
        case 'approve_list':
        case 'live_ticket_issued':
            return 'success';
        case 'new':
        case 'new_for_live':
        case 'new_list':
            return 'info';
        case 'cancel':
        case 'cancel_for_live':
        case 'cancel_list':
            return 'danger';
        case 'difficulties_arose':
        case 'difficulties_arose_list':
            return 'warn';
        default:
            return 'secondary';
    }
}

/** Человекочитаемое имя события истории заказа. */
export function orderHistoryEventLabel(name) {
    return (
        {
            'order.status.changed': 'Смена статуса',
            order_status_changed: 'Смена статуса',
            order_created: 'Заказ создан',
            order_ticket_changed: 'Данные заказа изменены',
            order_ticket_removed: 'Билет удалён'
        }[name] || name
    );
}
