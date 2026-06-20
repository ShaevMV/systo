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
