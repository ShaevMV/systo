// ─────────────────────────────────────────────────────────────────────────────
//  Поиск билета без QR (Ф5, PR-5) — отделён от Vue для тестируемости.
//
//  Онлайн: GET /api/search (тот же SearchService, что Blade /search — богатый поиск
//  по ФИО/телефону/телеге/госномеру/№ заказа). Офлайн: по локальному снимку (B5 —
//  только имя/номер). Результат нормализуем в единую строку для списка.
// ─────────────────────────────────────────────────────────────────────────────
import { http } from '@/api/http';
import { humanType } from '@/lib/qr';
import { searchSnapshot } from '@/db/snapshot';
import { getKey } from '@/services/pin';

/**
 * @typedef {{type: string, kilter: (number|null), name: string, typeTicket: string,
 *   color: (string|null), dateChange: (string|null), online: boolean, key: string}} SearchRow
 */

/**
 * @param {string} q
 * @param {{online: boolean}} ctx
 * @returns {Promise<SearchRow[]>}
 */
export async function searchTickets(q, { online }) {
    const term = String(q || '').trim();
    if (term === '') {
        return [];
    }
    return online ? searchOnline(term) : searchOffline(term);
}

async function searchOnline(q) {
    let data;
    try {
        ({ data } = await http.get('/api/search', { params: { q } }));
    } catch {
        return [];
    }
    if (!data || data.success !== true) {
        return [];
    }
    const groups = data.groups || {};
    const seen = new Set();
    const rows = [];
    for (const [groupKey, items] of Object.entries(groups)) {
        for (const it of items || []) {
            const row = normalize(it, groupKey, true);
            // Один билет может прийти и в типовой группе, и в ticket_search — дедуп по ключу.
            if (row.kilter !== null && seen.has(row.key)) {
                continue;
            }
            if (row.kilter !== null) {
                seen.add(row.key);
            }
            rows.push(row);
        }
    }
    return rows;
}

async function searchOffline(q) {
    const rows = await searchSnapshot(q, getKey());
    return rows.map((r) => normalize(r, r.type, false));
}

function normalize(it, groupKey, online) {
    const type = it.type || groupKey;
    const kilter = it.kilter ?? null;
    return {
        type,
        kilter,
        name: it.name || it.fio || '—',
        typeTicket: it.type_ticket || it.typeTicket || humanType(type),
        color: it.color || null,
        dateChange: it.date_change || null,
        online,
        key: `${type}:${kilter}`
    };
}
