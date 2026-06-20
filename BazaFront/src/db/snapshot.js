// ─────────────────────────────────────────────────────────────────────────────
//  Офлайн-снимок билетов в IndexedDB (Ф5, PR-4). Источник — GET /api/snapshot (PR-3).
//
//  Минимизация ПДн (B5): храним только поля впуска (uuid/kilter/тип/цвет/имя). Сверка
//  по uuid (электронный QR) и по kilter (номер/парковка). Шифрование снимка — PR-6.
// ─────────────────────────────────────────────────────────────────────────────
import { db, STORE_SNAPSHOT } from '@/db/index';

/**
 * Записать порцию снимка (идемпотентно по uuid).
 * @param {Array<object>} items
 */
export async function putSnapshotBatch(items) {
    if (!Array.isArray(items) || items.length === 0) {
        return 0;
    }
    const database = await db();
    const tx = database.transaction(STORE_SNAPSHOT, 'readwrite');
    let n = 0;
    for (const it of items) {
        if (it && it.uuid) {
            tx.store.put(it);
            n++;
        }
    }
    await tx.done;
    return n;
}

/** Найти билет по uuid (электронный QR). */
export async function getByUuid(uuid) {
    if (!uuid) {
        return undefined;
    }
    const database = await db();
    return database.get(STORE_SNAPSHOT, uuid);
}

/** Найти билет по номеру (kilter) — парковка / ручной ввод номера. */
export async function getByKilter(kilter) {
    const n = Number(kilter);
    if (!Number.isFinite(n)) {
        return undefined;
    }
    const database = await db();
    return database.getFromIndex(STORE_SNAPSHOT, 'kilter', n);
}

/**
 * Офлайн-поиск по снимку (Ф5, PR-5). B5: в снимке только имя + номер, поэтому ищем
 * по ним (телефон/телега/госномер офлайн недоступны — только онлайн /api/search).
 * @param {string} q
 * @param {number} [limit=50]
 */
export async function searchSnapshot(q, limit = 50) {
    const term = String(q || '').trim().toLowerCase();
    if (term === '') {
        return [];
    }
    const num = Number(term);
    const database = await db();
    const all = await database.getAll(STORE_SNAPSHOT);
    const out = [];
    for (const r of all) {
        const byName = r.name && r.name.toLowerCase().includes(term);
        const byKilter = Number.isFinite(num) && r.kilter === num;
        if (byName || byKilter) {
            out.push(r);
            if (out.length >= limit) {
                break;
            }
        }
    }
    return out;
}

/** Сколько билетов в офлайн-снимке (для индикатора готовности офлайна). */
export async function snapshotCount() {
    const database = await db();
    return database.count(STORE_SNAPSHOT);
}

/** Очистить снимок (wipe при закрытии смены / смене фестиваля). */
export async function clearSnapshot() {
    const database = await db();
    return database.clear(STORE_SNAPSHOT);
}
