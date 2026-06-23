// ─────────────────────────────────────────────────────────────────────────────
//  Синк чёрного списка отозванных билетов (Ф5, PR-6, B6).
//
//  Тянем ПРИОРИТЕТНЕЕ снимка (отозванный билет важнее показать заблокированным, чем
//  свежий снимок). Дельта по server_time, пагинация after_id — как снимок.
// ─────────────────────────────────────────────────────────────────────────────
import { http } from '@/api/http';
import { putBlacklistBatch } from '@/db/blacklist';
import { getMeta, setMeta } from '@/db/index';

const KEY_SINCE = 'blacklist_since';
const PAGE_LIMIT = 1000;
const MAX_PAGES = 200;

let running = false;

/**
 * @param {{festivalId?: string|null}} [opts]
 * @returns {Promise<{added: number, ok: boolean}>}
 */
export async function syncBlacklist({ festivalId = null } = {}) {
    if (running) {
        return { added: 0, ok: false };
    }
    running = true;
    try {
        const since = await getMeta(KEY_SINCE, null);
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

            const { data } = await http.get('/api/blacklist', { params, meta: { skipAutoNotify: true } });
            if (!data || data.success !== true) {
                break;
            }
            if (watermark === null) {
                watermark = data.server_time || null;
            }
            added += await putBlacklistBatch(data.items || []);
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
        return { added: 0, ok: false };
    } finally {
        running = false;
    }
}
