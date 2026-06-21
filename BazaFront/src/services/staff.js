// ─────────────────────────────────────────────────────────────────────────────
//  Регистрация персонала (Шаг 5) — обёртка над /api/staff. Только онлайн (только админ).
// ─────────────────────────────────────────────────────────────────────────────
import { http } from '@/api/http';

/** { staff:[{id,name,email,is_admin,role,role_label}], roles:[{value,label}] } */
export async function loadStaff() {
    const { data } = await http.get('/api/staff');
    return data;
}

/** Создать/обновить сотрудника (идемпотентно по email). Бросает при 422 (валидация). */
export async function createStaff(payload) {
    const { data } = await http.post('/api/staff', payload);
    return data;
}
