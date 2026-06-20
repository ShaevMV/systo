// ─────────────────────────────────────────────────────────────────────────────
//  Логика скана и впуска (Ф5, PR-4) — отделена от Vue для тестируемости.
//
//  Светофор-вердикт (реш. встречи #16=A):
//    green  — впуск подтверждён ОНЛАЙН сервером.
//    yellow — офлайн: билет есть в снимке, впуск возможен, но данные из снимка (B5).
//    red    — стоп: уже прошёл / не найден / не опознан / нужен онлайн.
//
//  Онлайн авторитетен: /api/scan возвращает карточку, /api/enter серверно валидирует
//  двойной впуск. Офлайн: сверка по снимку + оптимистичная отметка в очередь намерений.
// ─────────────────────────────────────────────────────────────────────────────
import { http } from '@/api/http';
import { parseQrReference, humanType } from '@/lib/qr';
import { getByUuid, getByKilter, nameOf } from '@/db/snapshot';
import { isBlacklisted } from '@/db/blacklist';
import { enqueue, hasEnterIntent } from '@/db/queue';
import { getKey } from '@/services/pin';

function ticketKey(type, kilter) {
    return `${type}:${kilter}`;
}

function errorMessage(e, fallback) {
    const d = e?.response?.data;
    if (typeof d === 'string' && d.trim() !== '') {
        return d;
    }
    if (d && typeof d.message === 'string') {
        return d.message;
    }
    return fallback;
}

/**
 * Вердикт по сырому тексту QR (или ручному вводу).
 * @param {string} rawText
 * @param {{online: boolean}} ctx
 */
export async function resolveScan(rawText, { online }) {
    const ref = parseQrReference(rawText);

    // B6 (приоритет): отозванный билет — красный, независимо от онлайн/офлайн.
    if (ref && (await isBlacklisted(ref.kind === 'uuid' ? ref.id : null, ref.kind === 'number' ? ref.id : null))) {
        return revoked();
    }

    if (online) {
        return resolveOnline(rawText);
    }
    return resolveOffline(ref);
}

async function resolveOnline(rawText) {
    try {
        const { data } = await http.post('/api/scan', { search: rawText });

        // Локальный blacklist может опережать снимок — мгновенный красный по авторитетным uuid/kilter.
        if (await isBlacklisted(data.uuid || null, data.kilter ?? null)) {
            return { ...revoked(), online: true, ticket: cardFromOnline(data) };
        }

        const entered = !!data.date_change;
        return {
            online: true,
            color: entered ? 'red' : 'green',
            title: entered ? 'Уже прошёл' : 'Пропустить',
            reason: entered ? `Впущен: ${data.date_change}` : null,
            ticket: cardFromOnline(data),
            enterRef: entered
                ? null
                : { type: data.type, id: data.kilter, uuid: data.uuid || null, key: ticketKey(data.type, data.kilter) }
        };
    } catch (e) {
        return {
            online: true,
            color: 'red',
            title: 'Не пропускать',
            reason: errorMessage(e, 'Билет не опознан'),
            ticket: null,
            enterRef: null
        };
    }
}

async function resolveOffline(ref) {
    if (!ref) {
        return verdict('red', 'Не опознан', 'QR не распознан. Введите № вручную.');
    }
    // Живой билет приходит зашифрованным — офлайн расшифровать нельзя.
    if (ref.kind === 'live') {
        return verdict('yellow', 'Нужен онлайн', 'Живой билет проверяется только онлайн.');
    }

    const row = ref.kind === 'uuid' ? await getByUuid(ref.id) : await getByKilter(ref.id);
    if (!row) {
        return verdict('red', 'Не найден', 'Нет в офлайн-снимке. Проверьте онлайн (снимок мог устареть).');
    }

    // Имя в снимке может быть зашифровано (PR-6) — расшифровываем для карточки.
    const card = cardFromOffline({ ...row, name: await nameOf(row, getKey()) });

    const key = ticketKey(row.type, row.kilter);
    if (await hasEnterIntent(key)) {
        return {
            online: false,
            color: 'red',
            title: 'Уже прошёл',
            reason: 'Впущен с этого устройства.',
            ticket: card,
            enterRef: null
        };
    }

    return {
        online: false,
        color: 'yellow', // офлайн = жёлтый: впуск возможен, но данные из снимка (реш. #16)
        title: 'Пропустить',
        reason: 'Данные из офлайн-снимка.',
        ticket: card,
        enterRef: { type: row.type, id: row.kilter, uuid: row.uuid || null, key }
    };
}

/**
 * Впуск гостя. Онлайн → /api/enter (серверная защита от двойного впуска).
 * Офлайн → оптимистично кладём намерение в очередь (досыл при сети, мёрж в PR-8).
 */
export async function doEnter(enterRef, { online }) {
    if (!enterRef) {
        return { ok: false, message: 'Нет билета для впуска' };
    }
    if (online) {
        try {
            await http.post('/api/enter', { type: enterRef.type, id: enterRef.id });
            return { ok: true, queued: false };
        } catch (e) {
            return { ok: false, message: errorMessage(e, 'Не удалось впустить') };
        }
    }
    // client_op_id фиксируем при постановке — стабильная идемпотентность дренажа (PR-8).
    const clientOpId =
        typeof crypto !== 'undefined' && crypto.randomUUID ? crypto.randomUUID() : 'op-' + Date.now() + '-' + enterRef.key;
    await enqueue({
        type: 'enter',
        payload: {
            type: enterRef.type,
            id: enterRef.id,
            ticket_uuid: enterRef.uuid || null,
            ticket_key: enterRef.key,
            client_op_id: clientOpId
        }
    });
    return { ok: true, queued: true };
}

// ── Карточки впуска ───────────────────────────────────────────────────────────

function cardFromOnline(d) {
    return {
        online: true,
        name: d.name || '—',
        typeTicket: d.type_ticket || humanType(d.type),
        color: d.color || null,
        status: d.status_human || null,
        dateChange: d.date_change || null,
        // Онлайн — полные данные доступны (карточка показывает их).
        phone: d.phone || null,
        email: d.email || null,
        comment: d.comment || null
    };
}

function cardFromOffline(row) {
    return {
        online: false,
        name: row.name || '—',
        typeTicket: row.type_ticket || humanType(row.type),
        color: row.color || null,
        status: null,
        dateChange: null,
        // B5: телефон/email/коммент офлайн НЕ кэшируются — плейсхолдер «доступно онлайн».
        phone: null,
        email: null,
        comment: null
    };
}

function verdict(color, title, reason) {
    return { online: false, color, title, reason, ticket: null, enterRef: null };
}

/** Отозванный билет (B6) — красный, впуск запрещён. */
function revoked() {
    return { online: false, color: 'red', title: 'Отозван', reason: 'Билет отозван (возврат/отмена).', ticket: null, enterRef: null };
}
