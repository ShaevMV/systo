// ─────────────────────────────────────────────────────────────────────────────
//  IndexedDB офлайн-хранилище входного приложения КПП (Ф5).
//
//  Два store:
//   - `queue` — append-only очередь НАМЕРЕНИЙ впуска/скана, накопленных офлайн.
//     Никогда не редактируем чужие записи — только добавляем свои и помечаем
//     отправленными. Это основа мульти-устройственной синхронизации (PR-8):
//     узел КПП мёржит журналы намерений, не теряя ни одного впуска.
//   - `meta` — служебные ключи (device_id, отметки синка снимка и т.п.).
//
//  Снимок билетов для офлайн-сверки (store `snapshot`) добавит PR-3 — там же
//  поднимем версию БД. Здесь намеренно держим минимальную схему v1.
// ─────────────────────────────────────────────────────────────────────────────
import { openDB } from 'idb';

const DB_NAME = 'baza-pwa';
const DB_VERSION = 1;

const STORE_QUEUE = 'queue';
const STORE_META = 'meta';

let dbPromise = null;

// Сигнал «очередь изменилась» — шапка обновляет бейдж неотправленных намерений.
function notifyChange() {
    if (typeof window !== 'undefined') {
        window.dispatchEvent(new Event('baza-queue-changed'));
    }
}

function db() {
    if (!dbPromise) {
        dbPromise = openDB(DB_NAME, DB_VERSION, {
            upgrade(database) {
                if (!database.objectStoreNames.contains(STORE_QUEUE)) {
                    const queue = database.createObjectStore(STORE_QUEUE, {
                        keyPath: 'id',
                        autoIncrement: true
                    });
                    // По статусу выбираем неотправленные при восстановлении сети.
                    queue.createIndex('status', 'status');
                }
                if (!database.objectStoreNames.contains(STORE_META)) {
                    database.createObjectStore(STORE_META, { keyPath: 'key' });
                }
            }
        });
    }
    return dbPromise;
}

// ── Очередь намерений ────────────────────────────────────────────────────────

/**
 * Поставить намерение в очередь (впуск/скан, сделанные офлайн).
 * @param {{type: string, payload: object}} intent
 * @returns {Promise<number>} локальный id записи
 */
export async function enqueue(intent) {
    const database = await db();
    const record = {
        type: intent.type, // 'enter' | 'scan'
        payload: intent.payload ?? {},
        device_id: await deviceId(),
        // Время намерения по часам устройства — НЕ перетираем серверным временем,
        // оно нужно для разбора порядка впусков при мёрже журналов (PR-8).
        created_at: new Date().toISOString(),
        status: 'pending', // 'pending' | 'sent' | 'failed'
        attempts: 0
    };
    const id = await database.add(STORE_QUEUE, record);
    notifyChange();
    return id;
}

/** Все неотправленные намерения (для досыла при восстановлении сети). */
export async function pending() {
    const database = await db();
    return database.getAllFromIndex(STORE_QUEUE, 'status', 'pending');
}

/** Сколько намерений ждёт отправки (для бейджа в шапке). */
export async function pendingCount() {
    const database = await db();
    return database.countFromIndex(STORE_QUEUE, 'status', 'pending');
}

/** Пометить намерение отправленным. */
export async function markSent(id) {
    return patch(id, (rec) => {
        rec.status = 'sent';
    });
}

/** Пометить намерение неуспешным (с инкрементом попыток). */
export async function markFailed(id) {
    return patch(id, (rec) => {
        rec.status = 'failed';
        rec.attempts = (rec.attempts ?? 0) + 1;
    });
}

async function patch(id, mutate) {
    const database = await db();
    const tx = database.transaction(STORE_QUEUE, 'readwrite');
    const rec = await tx.store.get(id);
    if (rec) {
        mutate(rec);
        await tx.store.put(rec);
    }
    await tx.done;
    notifyChange();
    return !!rec;
}

// ── Meta ─────────────────────────────────────────────────────────────────────

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
 * Идентификатор устройства (self-service регистрация по решению PM): создаётся
 * один раз и хранится локально. Узел КПП различает устройства по нему при мёрже.
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
