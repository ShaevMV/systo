import { ref } from 'vue';
import axios from 'axios';

/**
 * Переиспользуемый CRUD-слой над ресурсом-справочником `/api/v1/<resource>`.
 *
 * Контракт бэкенда — единый для admin-CRUD справочников (location / typesOfPayment /
 * ticketType / option / questionnaireType / promoCode …):
 *   getList  POST   {resource}/getList   { filter?, orderBy? } → { success, list: [...] }
 *   create   POST   {resource}/create    { data }             → { success, item, message }
 *   edit     POST   {resource}/edit/{id} { data }             → { success, item, message }
 *   delete   DELETE {resource}/delete/{id}                    → { success }
 *
 * Заменяет однотипный Vuex-модуль на каждый справочник: доменные модули остаются
 * на Vuex (см. spec `.claude/specs/admin-frontend-vite-sakai.md` §2.5), а плоские
 * справочники работают через этот composable. axios уже несёт JWT-интерсепторы
 * (см. `main.js`).
 *
 * @param {string} resource базовый путь, напр. '/api/v1/location'
 */
export function useCrud(resource) {
    const list = ref([]);
    const loading = ref(false);
    const saving = ref(false);
    const error = ref(null);

    const asArray = (v) => (Array.isArray(v) ? v : []);

    /** Достаёт читаемое сообщение из ответа Laravel (message / первая ошибка валидации). */
    const messageFromError = (e, fallback) => {
        const data = e?.response?.data;
        if (data?.message) return data.message;
        if (data?.errors) return Object.values(data.errors).flat()[0] ?? fallback;
        return fallback;
    };

    async function loadList(payload = {}) {
        loading.value = true;
        error.value = null;
        try {
            // Часть контроллеров читает filter/orderBy без `?? []` и падает 500 без
            // этих ключей (typesOfPayment, account…). Всегда гарантируем их наличие.
            const body = { filter: {}, orderBy: {}, ...payload };
            const r = await axios.post(`${resource}/getList`, body);
            list.value = asArray(r.data?.list);
            return list.value;
        } catch (e) {
            error.value = messageFromError(e, 'Не удалось загрузить список');
            list.value = [];
            return [];
        } finally {
            loading.value = false;
        }
    }

    /**
     * Создать (id отсутствует) или отредактировать (id передан).
     * @returns {Promise<{ ok: boolean, data?: object, error?: string }>}
     */
    async function save(data, id = null) {
        saving.value = true;
        error.value = null;
        try {
            const url = id ? `${resource}/edit/${id}` : `${resource}/create`;
            const r = await axios.post(url, { data });
            // Бэкенд может вернуть 200 + success:false (доменная ошибка).
            if (r.data?.success === false) {
                error.value = r.data.message || 'Ошибка сохранения';
                return { ok: false, error: error.value };
            }
            return { ok: true, data: r.data };
        } catch (e) {
            error.value = messageFromError(e, 'Ошибка сохранения');
            return { ok: false, error: error.value };
        } finally {
            saving.value = false;
        }
    }

    async function remove(id) {
        error.value = null;
        try {
            await axios.delete(`${resource}/delete/${id}`);
            return true;
        } catch (e) {
            error.value = messageFromError(e, 'Не удалось удалить');
            return false;
        }
    }

    return { list, loading, saving, error, loadList, save, remove };
}
