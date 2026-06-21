// ─────────────────────────────────────────────────────────────────────────────
//  Текущий сотрудник (роль + права) для PWA (Шаг 3): GET /api/whoami.
//
//  Нужен для гейтинга пунктов меню (Права/Смены/Регистрация только нужным ролям) и
//  признака полной карточки. БЕЗОПАСНОСТЬ ПДн — на бэкенде (он уже вырезает поля без
//  права ticket.pii); это лишь UI-логика.
// ─────────────────────────────────────────────────────────────────────────────
import { ref } from 'vue';
import { http } from '@/api/http';

// Реактивный синглтон текущего пользователя — общий для App.vue и экранов.
const current = ref(null);
let loading = null;

/**
 * Загрузить (один раз) и закэшировать текущего сотрудника.
 * @returns {Promise<object|null>}
 */
export async function loadCurrentUser() {
    if (current.value) {
        return current.value;
    }
    if (!loading) {
        loading = http
            .get('/api/whoami')
            .then(({ data }) => {
                current.value = data && data.success ? data : null;
                return current.value;
            })
            .catch(() => null)
            .finally(() => {
                loading = null;
            });
    }
    return loading;
}

/** Реактивная ссылка на текущего пользователя (null пока не загружен / офлайн). */
export function useCurrentUser() {
    return current;
}

/** Есть ли у текущего пользователя право (по списку из whoami). */
export function hasPermission(action) {
    const u = current.value;
    return !!u && Array.isArray(u.permissions) && u.permissions.includes(action);
}

export function isAdmin() {
    return !!current.value && current.value.is_admin === true;
}
