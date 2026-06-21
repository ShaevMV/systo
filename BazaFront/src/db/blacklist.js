// ─────────────────────────────────────────────────────────────────────────────
//  Чёрный список отозванных билетов в IndexedDB (Ф5, PR-6, B6).
//
//  Приоритетный канал «не пускать»: отозванный/возвращённый билет блокируется на КПП
//  ДАЖЕ офлайн. Без ПДн (только uuid/kilter). Ключ записи = uuid (или 'k:'+kilter),
//  индекс по kilter — чтобы найти и по uuid, и по номеру.
// ─────────────────────────────────────────────────────────────────────────────
import { db, STORE_BLACKLIST } from '@/db/index';

function keyOf(uuid, kilter) {
    if (uuid) {
        return uuid;
    }
    return Number.isFinite(Number(kilter)) ? 'k:' + Number(kilter) : null;
}

/**
 * Записать порцию отозванных.
 * @param {Array<{uuid?:string, kilter?:number}>} items
 */
export async function putBlacklistBatch(items) {
    if (!Array.isArray(items) || items.length === 0) {
        return 0;
    }
    const database = await db();
    const tx = database.transaction(STORE_BLACKLIST, 'readwrite');
    let n = 0;
    for (const it of items) {
        const uuid = it.uuid || null;
        const kilter = Number.isFinite(Number(it.kilter)) ? Number(it.kilter) : null;
        const key = keyOf(uuid, kilter);
        if (key) {
            tx.store.put({ key, uuid, kilter });
            n++;
        }
    }
    await tx.done;
    return n;
}

/**
 * Билет в чёрном списке? Проверяем и по uuid, и по номеру (любое совпадение → true).
 * @param {string|null} uuid
 * @param {number|string|null} kilter
 */
export async function isBlacklisted(uuid, kilter) {
    const database = await db();
    if (uuid) {
        const byUuid = await database.get(STORE_BLACKLIST, uuid);
        if (byUuid) {
            return true;
        }
    }
    const k = Number(kilter);
    if (Number.isFinite(k)) {
        const byKilter = await database.getFromIndex(STORE_BLACKLIST, 'kilter', k);
        if (byKilter) {
            return true;
        }
    }
    return false;
}

export async function blacklistCount() {
    const database = await db();
    return database.count(STORE_BLACKLIST);
}

export async function clearBlacklist() {
    const database = await db();
    return database.clear(STORE_BLACKLIST);
}
