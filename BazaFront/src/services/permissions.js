// ─────────────────────────────────────────────────────────────────────────────
//  Матрица прав «роль × действие» (Шаг 4) — обёртка над /api/permissions/matrix.
//  Только онлайн (управление правами — не офлайн-сценарий КПП).
// ─────────────────────────────────────────────────────────────────────────────
import { http } from '@/api/http';

/** Текущая матрица: { roles:[{value,label}], actions:[{value,label}], matrix:{role:[action]}, admin_role }. */
export async function loadMatrix() {
    const { data } = await http.get('/api/permissions/matrix');
    return data;
}

/**
 * Сохранить матрицу. perm = { role: [action,...] } по всем редактируемым ролям
 * (форма = источник правды: неотмеченное снимается). administrator сервер игнорирует.
 */
export async function saveMatrix(perm) {
    const { data } = await http.post('/api/permissions/matrix', { perm });
    return data;
}
