// ─────────────────────────────────────────────────────────────────────────────
//  Дренаж офлайн-очереди намерений впуска в облачный журнал (Ф5, PR-8).
//
//  При появлении связи телефон сливает накопленные офлайн впуски на сервер
//  (POST /api/entry-events). Сервер дедуплицирует по client_op_id и решает
//  «первый впуск побеждает». После успешного слива намерения помечаются sent.
// ─────────────────────────────────────────────────────────────────────────────
import { http } from '@/api/http';
import { pending, markSent } from '@/db/queue';

let running = false;

/**
 * @returns {Promise<{drained: number, ok: boolean}>}
 */
export async function drainQueue() {
    if (running || !navigator.onLine) {
        return { drained: 0, ok: false };
    }
    running = true;
    try {
        const items = await pending();
        const enters = items.filter((r) => r.type === 'enter');
        if (enters.length === 0) {
            return { drained: 0, ok: true };
        }

        const events = enters.map((r) => ({
            client_op_id: r.payload?.client_op_id || `q${r.id}-${r.device_id || ''}`,
            type: r.payload?.type,
            kilter: r.payload?.id,
            ticket_uuid: r.payload?.ticket_uuid || null,
            device_id: r.device_id || null,
            entered_at: r.created_at || null
        }));

        const { data } = await http.post('/api/entry-events', { events }, { meta: { skipAutoNotify: true } });
        if (!data || data.success !== true) {
            return { drained: 0, ok: false };
        }

        // Сервер обработал пачку (entered/duplicate/revoked/already/error — все консьюмлены) → помечаем sent.
        for (const r of enters) {
            await markSent(r.id);
        }
        return { drained: enters.length, ok: true };
    } catch {
        // Сеть упала/сессия — оставляем намерения pending, дренаж повторится позже.
        return { drained: 0, ok: false };
    } finally {
        running = false;
    }
}
