// ─────────────────────────────────────────────────────────────────────────────
//  Управление сменами (Шаг 6) — обёртка над /api/shifts. Только онлайн.
//  Изоляция (бэкенд): начальник видит/закрывает только свою смену; admin — все.
// ─────────────────────────────────────────────────────────────────────────────
import { http } from '@/api/http';

/** { shifts:[{id,chief_id,chief_name,members_count,start,counts}], is_admin } */
export async function loadShifts() {
    const { data } = await http.get('/api/shifts');
    return data;
}

/** { users:[{id,name,email,role,role_label}] } — для выбора состава. */
export async function loadShiftUsers() {
    const { data } = await http.get('/api/shifts/users');
    return data;
}

/** Создать смену: { members:[id], chief_id? } (для admin chief_id обязателен; начальник — авто). */
export async function createShift(payload) {
    const { data } = await http.post('/api/shifts', payload);
    return data;
}

export async function closeShift(id) {
    const { data } = await http.post(`/api/shifts/${id}/close`);
    return data;
}
