// ─────────────────────────────────────────────────────────────────────────────
//  Append-only очередь НАМЕРЕНИЙ впуска/скана, накопленных офлайн (Ф5).
//
//  Никогда не редактируем чужие записи — только добавляем свои и помечаем
//  отправленными. Это основа мульти-устройственной синхронизации (PR-8):
//  узел/облако мёржит журналы намерений, не теряя ни одного впуска.
//
//  Открыватель БД и meta-хелперы — в ./index.js (общие со снимком, PR-4).
// ─────────────────────────────────────────────────────────────────────────────
import { db, notifyChange, STORE_QUEUE } from '@/db/index';

// Реэкспорт meta-хелперов для обратной совместимости (PR-2 импортировал из queue).
export { getMeta, setMeta, deviceId } from '@/db/index';
import { deviceId } from '@/db/index';

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

/**
 * Есть ли уже намерение впуска по этому билету (с ЭТОГО устройства).
 * Офлайн-защита от двойного впуска одним телефоном (полная меж-устройственная — PR-8).
 * @param {string} ticketKey  стабильный ключ билета (`type:id`)
 * @returns {Promise<boolean>}
 */
export async function hasEnterIntent(ticketKey) {
    if (!ticketKey) {
        return false;
    }
    const database = await db();
    const all = await database.getAll(STORE_QUEUE);
    return all.some((rec) => rec.type === 'enter' && rec.payload?.ticket_key === ticketKey);
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
