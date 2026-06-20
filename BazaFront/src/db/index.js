// ─────────────────────────────────────────────────────────────────────────────
//  Общий доступ к IndexedDB входного приложения КПП (Ф5).
//
//  Один открыватель БД на все store'ы (queue/meta — PR-2, snapshot — PR-4):
//   - `queue`    — append-only очередь НАМЕРЕНИЙ впуска (основа мульти-устройства, PR-8).
//   - `meta`     — служебные ключи (device_id, курсоры синка снимка).
//   - `snapshot` — минимизированный офлайн-снимок билетов (B5): уникальный ключ uuid,
//                  индекс по kilter (номер) для сверки по номеру/парковке.
//
//  Версия БД поднята до 2 в PR-4 (добавлен store `snapshot`). Апгрейд идемпотентный.
// ─────────────────────────────────────────────────────────────────────────────
import { openDB } from 'idb';

const DB_NAME = 'baza-pwa';
const DB_VERSION = 2;

export const STORE_QUEUE = 'queue';
export const STORE_META = 'meta';
export const STORE_SNAPSHOT = 'snapshot';

let dbPromise = null;

/** Сигнал «очередь изменилась» — шапка обновляет бейдж неотправленных намерений. */
export function notifyChange() {
    if (typeof window !== 'undefined') {
        window.dispatchEvent(new Event('baza-queue-changed'));
    }
}

export function db() {
    if (!dbPromise) {
        dbPromise = openDB(DB_NAME, DB_VERSION, {
            upgrade(database) {
                if (!database.objectStoreNames.contains(STORE_QUEUE)) {
                    const queue = database.createObjectStore(STORE_QUEUE, {
                        keyPath: 'id',
                        autoIncrement: true
                    });
                    queue.createIndex('status', 'status');
                }
                if (!database.objectStoreNames.contains(STORE_META)) {
                    database.createObjectStore(STORE_META, { keyPath: 'key' });
                }
                if (!database.objectStoreNames.contains(STORE_SNAPSHOT)) {
                    const snap = database.createObjectStore(STORE_SNAPSHOT, { keyPath: 'uuid' });
                    // Сверка по номеру (kilter) — для парковки и поиска по номеру.
                    snap.createIndex('kilter', 'kilter');
                }
            }
        });
    }
    return dbPromise;
}

// ── Meta (общая для очереди и снимка) ─────────────────────────────────────────

export async function getMeta(key, fallback = null) {
    const database = await db();
    const row = await database.get(STORE_META, key);
    return row ? row.value : fallback;
}

export async function setMeta(key, value) {
    const database = await db();
    return database.put(STORE_META, { key, value });
}

/**
 * Идентификатор устройства (self-service регистрация, реш. C10): создаётся один раз
 * и хранится локально. Узел/облако различают устройства по нему при мёрже журналов (PR-8).
 */
export async function deviceId() {
    let id = await getMeta('device_id');
    if (!id) {
        id =
            typeof crypto !== 'undefined' && crypto.randomUUID
                ? crypto.randomUUID()
                : 'dev-' + Date.now() + '-' + Math.floor(Math.random() * 1e6);
        await setMeta('device_id', id);
    }
    return id;
}
