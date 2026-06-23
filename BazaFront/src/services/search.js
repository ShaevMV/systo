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
 *   color: (string|null), dateChange: (string|null), online: boolean, key: string,
 *   phone: (string|null), email: (string|null), comment: (string|null), telegram: (string|null),
 *   carNumber: (string|null), childName: (string|null), parentPhone: (string|null),
 *   externalOrderNo: (string|null), city: (string|null)}} SearchRow
 */

/** Снять HTML-подсветку ShowSearchWordService (<b>…</b>) — чтобы теги/XSS не текли в карточку. */
function stripTags(s) {
    return typeof s === 'string' ? s.replace(/<\/?[^>]+>/g, '') : s;
}

/**
 * @param {string} q
 * @param {{online: boolean}} ctx
 * @returns {Promise<{rows: SearchRow[], festivalScope: (string|null)}>}
 *   festivalScope — имя фестиваля смены, если поиск ограничен им (TD-48, изоляция ON).
 */
export async function searchTickets(q, { online }) {
    const term = String(q || '').trim();
    if (term === '') {
        return { rows: [], festivalScope: null };
    }
    return online ? searchOnline(term) : searchOffline(term);
}

async function searchOnline(q) {
    let data;
    try {
        ({ data } = await http.get('/api/search', { params: { q } }));
    } catch {
        return { rows: [], festivalScope: null };
    }
    if (!data || data.success !== true) {
        return { rows: [], festivalScope: null };
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
    return { rows, festivalScope: data.festival_scope || null };
}

async function searchOffline(q) {
    const found = await searchSnapshot(q, getKey());
    return { rows: found.map((r) => normalize(r, r.type, false)), festivalScope: null };
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
        key: `${type}:${kilter}`,
        // ПДн: бэкенд (TicketPiiFilter) присылает эти поля ТОЛЬКО ролям с правом ticket.pii —
        // остальным они уже вырезаны, поэтому здесь будут null и в шаблоне скроются v-if.
        // Раньше normalize() их молча терял → для админа «не было разницы» (фикс #5).
        phone: stripTags(it.phone) || null,
        email: stripTags(it.email) || null,
        comment: stripTags(it.comment) || null,
        telegram: stripTags(it.telegram) || null,
        carNumber: stripTags(it.car_number) || null,
        childName: stripTags(it.child_name) || null,
        parentPhone: stripTags(it.parent_phone) || null,
        externalOrderNo: stripTags(it.external_order_no) || null,
        city: stripTags(it.city) || null
    };
}
