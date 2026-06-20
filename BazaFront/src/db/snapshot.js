// ─────────────────────────────────────────────────────────────────────────────
//  Офлайн-снимок билетов в IndexedDB (Ф5, PR-4). Источник — GET /api/snapshot (PR-3).
//
//  Минимизация ПДн (B5): храним только поля впуска (uuid/kilter/тип/цвет/имя). Сверка
//  по uuid (электронный QR) и по kilter (номер/парковка). Шифрование снимка — PR-6.
// ─────────────────────────────────────────────────────────────────────────────
import { db, STORE_SNAPSHOT } from '@/db/index';
import { encryptString, decryptString } from '@/lib/crypto';

/**
 * Записать порцию снимка (идемпотентно по uuid). Если передан ключ (PIN разблокирован) —
 * имя гостя шифруется на диске (B5/PR-6: «кэш шифрован»); без ключа — plaintext (деградация).
 * @param {Array<object>} items
 * @param {CryptoKey|null} [key]
 */
export async function putSnapshotBatch(items, key = null) {
    if (!Array.isArray(items) || items.length === 0) {
        return 0;
    }

    // Шифрование имени — ВНЕ IDB-транзакции (await на не-IDB промис закрыл бы tx).
    const rows = [];
    for (const it of items) {
        if (!it || !it.uuid) {
            continue;
        }
        if (key && it.name) {
            const nameEnc = await encryptString(key, it.name);
            rows.push({ ...it, name: null, name_enc: nameEnc });
        } else {
            rows.push(it);
        }
    }

    const database = await db();
    const tx = database.transaction(STORE_SNAPSHOT, 'readwrite');
    for (const r of rows) {
        tx.store.put(r);
    }
    await tx.done;
    return rows.length;
}

/**
 * Расшифровать имя строки снимка (если зашифровано и есть ключ).
 * @param {object} row
 * @param {CryptoKey|null} key
 * @returns {Promise<string>}
 */
export async function nameOf(row, key) {
    if (row && row.name_enc && key) {
        try {
            return (await decryptString(key, row.name_enc)) || '—';
        } catch {
            return '—';
        }
    }
    return (row && row.name) || '—';
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
 * Если имя зашифровано (PR-6), расшифровываем для сопоставления (ключ обязателен).
 * @param {string} q
 * @param {CryptoKey|null} [key]
 * @param {number} [limit=50]
 */
export async function searchSnapshot(q, key = null, limit = 50) {
    const term = String(q || '').trim().toLowerCase();
    if (term === '') {
        return [];
    }
    const num = Number(term);
    const database = await db();
    const all = await database.getAll(STORE_SNAPSHOT);
    const out = [];
    for (const r of all) {
        const name = await nameOf(r, key);
        const byName = name && name !== '—' && name.toLowerCase().includes(term);
        const byKilter = Number.isFinite(num) && r.kilter === num;
        if (byName || byKilter) {
            out.push({ ...r, name });
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
