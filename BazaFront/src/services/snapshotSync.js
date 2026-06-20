// ─────────────────────────────────────────────────────────────────────────────
//  Синхронизация офлайн-снимка билетов с облаком (Ф5, PR-4).
//
//  Топология облако-мастер: телефон тянет снимок из боевого сервера по любому
//  интернету (сотовая/Wi-Fi). Пагинируем порциями (after_id), дельту берём по
//  server_time предыдущего успешного синка (реш. встречи #13=A).
// ─────────────────────────────────────────────────────────────────────────────
import { http } from '@/api/http';
import { putSnapshotBatch } from '@/db/snapshot';
import { getMeta, setMeta } from '@/db/index';

const KEY_SINCE = 'snapshot_since'; // server_time последнего успешного синка (граница дельты)
const PAGE_LIMIT = 500;
const MAX_PAGES = 200; // предохранитель от бесконечного цикла (до 100k билетов за синк)

let running = false;

/**
 * Синхронизировать снимок: первый раз — полный, далее — дельта.
 * Идемпотентно и безопасно к параллельному вызову (повторный вызов — no-op).
 * @param {{festivalId?: string|null}} [opts]
 * @returns {Promise<{added: number, ok: boolean}>}
 */
export async function syncSnapshot({ festivalId = null } = {}) {
    if (running) {
        return { added: 0, ok: false };
    }
    running = true;
    try {
        const since = await getMeta(KEY_SINCE, null); // null → полный снимок
        let afterId = 0;
        let watermark = null;
        let added = 0;

        for (let page = 0; page < MAX_PAGES; page++) {
            const params = { after_id: afterId, limit: PAGE_LIMIT };
            if (festivalId) {
                params.festival_id = festivalId;
            }
            if (since) {
                params.since = since;
            }

            const { data } = await http.get('/api/snapshot', { params });
            if (!data || data.success !== true) {
                break;
            }

            // Водяную метку берём с ПЕРВОЙ страницы (момент начала прохода) — чтобы
            // следующая дельта не пропустила строки, изменённые во время пагинации.
            if (watermark === null) {
                watermark = data.server_time || null;
            }

            added += await putSnapshotBatch(data.items || []);
            afterId = Number.isFinite(data.next_after_id) ? data.next_after_id : afterId;

            if (!data.has_more) {
                break;
            }
        }

        if (watermark) {
            await setMeta(KEY_SINCE, watermark);
        }
        return { added, ok: true };
    } catch {
        // Офлайн/сеть упала — не страшно: работаем по уже скачанному снимку.
        return { added: 0, ok: false };
    } finally {
        running = false;
    }
}
