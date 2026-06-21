// ─────────────────────────────────────────────────────────────────────────────
//  Подсветка совпадений в результатах поиска (Ф5).
//
//  Подсветку делаем НА КЛИЕНТЕ (не доверяем <b> от бэкенда ShowSearchWordService):
//  сначала экранируем весь HTML из данных билета (защита от XSS — данные приходят из
//  org), затем оборачиваем найденную подстроку в <b>. Так безопасно для v-html и
//  работает одинаково онлайн и офлайн (снимок подсветки не несёт).
// ─────────────────────────────────────────────────────────────────────────────

/** Экранировать HTML-спецсимволы (чтобы данные не исполнялись как разметка). */
function escapeHtml(s) {
    return String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/** Экранировать спецсимволы регулярного выражения в строке запроса. */
function escapeRegExp(s) {
    return String(s).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Вернуть HTML строки `text` с подсвеченным (через <b>) вхождением `query`.
 * Безопасно для v-html: сами данные экранированы, добавляются только наши <b>.
 *
 * @param {string|null|undefined} text
 * @param {string|null|undefined} query
 * @returns {string}
 */
export function highlightMatch(text, query) {
    const safe = escapeHtml(text);
    const q = String(query ?? '').trim();
    if (q === '') {
        return safe;
    }
    // Запрос тоже экранируем — ищем по экранированной строке, чтобы границы совпали.
    const needle = escapeRegExp(escapeHtml(q));

    return safe.replace(new RegExp('(' + needle + ')', 'gi'), '<b>$1</b>');
}
