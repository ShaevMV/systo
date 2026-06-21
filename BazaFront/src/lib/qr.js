// ─────────────────────────────────────────────────────────────────────────────
//  Распознавание QR-ссылки билета (Ф5, PR-4) — порт DefineService::getTypeByReference.
//
//  По подстроке в ссылке определяем тип билета и достаём идентификатор.
//  Офлайн надёжно матчатся electron (по uuid) и parking (по номеру). Живой билет
//  приходит ЗАШИФРОВАННЫМ номером — офлайн расшифровать нельзя, только онлайн.
// ─────────────────────────────────────────────────────────────────────────────

const URL_STRIP = ['http://baza.spaceofjoy.ru', '/search?q='];

const ELECTRON_URL = '/newTickets/';
const LIVE_URL = 'https://org.spaceofjoy.ru/ticket/live/';

const PARKING = 'parking';
const PARKING_FREE = 'parking_free';
const PARKING_CC = 'parking_cross-countrycross-country';

/**
 * @typedef {{type: string, id: (string|number), kind: ('uuid'|'number'|'live')}} QrRef
 */

/**
 * Разобрать сырой текст QR. null — не опознан (нужен ручной ввод).
 * @param {string} origLink
 * @returns {QrRef|null}
 */
export function parseQrReference(origLink) {
    if (typeof origLink !== 'string' || origLink.trim() === '') {
        return null;
    }

    let link = origLink;
    for (const u of URL_STRIP) {
        link = link.split(u).join('');
    }
    const lc = link.toLowerCase();

    // Порядок важен: free/cross-country проверяются ДО общей parking (как в DefineService).
    if (lc.includes(PARKING_FREE)) {
        return { type: 'parking_free', id: onlyNumber(link, PARKING_FREE), kind: 'number' };
    }
    if (lc.includes(PARKING_CC)) {
        return { type: 'parking_cross-countrycross-country', id: onlyNumber(link, PARKING_CC), kind: 'number' };
    }
    if (lc.includes(PARKING)) {
        return { type: 'parking', id: onlyNumber(link, PARKING), kind: 'number' };
    }
    // Берём часть ПОСЛЕ маркера (надёжнее str_replace: корректно и для относительного
    // QR `/newTickets/<uuid>`, и для полного URL с хостом).
    if (link.includes(ELECTRON_URL)) {
        const i = link.lastIndexOf(ELECTRON_URL);
        return { type: 'electron', id: link.slice(i + ELECTRON_URL.length).trim(), kind: 'uuid' };
    }
    if (link.includes(LIVE_URL)) {
        const i = link.lastIndexOf(LIVE_URL);
        return { type: 'live', id: link.slice(i + LIVE_URL.length).trim(), kind: 'live' };
    }
    return null;
}

function onlyNumber(str, symbol) {
    let s = str;
    if (symbol) {
        const i = s.toLowerCase().lastIndexOf(symbol);
        if (i >= 0) {
            s = s.slice(i);
        }
    }
    return parseInt(s.replace(/[^0-9]/g, ''), 10) || 0;
}

const HUMAN_TYPE = {
    electron: 'Электронный',
    auto: 'Автомобиль',
    spisok: 'Список',
    live: 'Живой',
    drug: 'Френдли',
    parking: 'Парковка гостевая',
    parking_free: 'Парковка для своих'
};

/** Читаемое название типа билета. */
export function humanType(type) {
    return HUMAN_TYPE[type] || type || '—';
}
