// ─────────────────────────────────────────────────────────────────────────────
//  Синхронизация офлайн-снимка билетов с облаком (Ф5, PR-4).
//
//  Топология облако-мастер: телефон тянет снимок из боевого сервера по любому
//  интернету (сотовая/Wi-Fi). Пагинируем порциями (after_id), дельту берём по
//  server_time предыдущего успешного синка (реш. встречи #13=A).
// ─────────────────────────────────────────────────────────────────────────────
import { http } from '@/api/http';
import { putSnapshotBatch, clearSnapshot } from '@/db/snapshot';
import { getMeta, setMeta } from '@/db/index';
import { getKey } from '@/services/pin';

const KEY_SINCE = 'snapshot_since'; // server_time последнего успешного синка (граница дельты)
const KEY_FESTIVAL_ID = 'snapshot_festival_id'; // фестиваль текущего снимка (TD-48)
const KEY_FESTIVAL_NAME = 'snapshot_festival_name'; // имя фестиваля снимка — для офлайн-индикатора
const PAGE_LIMIT = 500;
const MAX_PAGES = 200; // предохранитель от бесконечного цикла (до 100k билетов за синк)

let running = false;

/** Имя фестиваля текущего офлайн-снимка (для индикатора «снимок фестиваля X»). */
export async function getSnapshotFestivalName() {
    return getMeta(KEY_FESTIVAL_NAME, null);
}

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
        let since = await getMeta(KEY_SINCE, null); // null → полный снимок
        const prevFestivalId = await getMeta(KEY_FESTIVAL_ID, null);
        let afterId = 0;
        let watermark = null;
        let added = 0;
        let snapFestivalId = null;
        let snapFestivalName = null;
        let festivalChecked = false;

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

            // TD-48: фестиваль снимка приходит от сервера (при изоляции — фестиваль смены).
            // Если он сменился — старый снимок ЧУЖОГО феста очищаем и тянем полный заново
            // (дельта по since пропустила бы основной массив нового фестиваля).
            if (!festivalChecked) {
                festivalChecked = true;
                snapFestivalId = data.festival_id || null;
                snapFestivalName = data.festival_name || null;
                if (prevFestivalId && snapFestivalId && prevFestivalId !== snapFestivalId) {
                    await clearSnapshot();
                    since = null;
                    afterId = 0;
                    watermark = null;
                    added = 0;
                    page = -1; // следующий проход цикла начнёт полный снимок нового фестиваля
                    continue;
                }
            }

            // Водяную метку берём с ПЕРВОЙ страницы (момент начала прохода) — чтобы
            // следующая дельта не пропустила строки, изменённые во время пагинации.
            if (watermark === null) {
                watermark = data.server_time || null;
            }

            added += await putSnapshotBatch(data.items || [], getKey());
            afterId = Number.isFinite(data.next_after_id) ? data.next_after_id : afterId;

            if (!data.has_more) {
                break;
            }
        }

        if (watermark) {
            await setMeta(KEY_SINCE, watermark);
        }
        if (snapFestivalId) {
            await setMeta(KEY_FESTIVAL_ID, snapFestivalId);
        }
        if (snapFestivalName) {
            await setMeta(KEY_FESTIVAL_NAME, snapFestivalName);
        }
        return { added, ok: true };
    } catch {
        // Офлайн/сеть упала — не страшно: работаем по уже скачанному снимку.
        return { added: 0, ok: false };
    } finally {
        running = false;
    }
}
