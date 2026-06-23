// ─────────────────────────────────────────────────────────────────────────────
//  Фестивали КПП (TD-48) — обёртка над /api/festivals. Только онлайн.
//  Реестр — реплика каталога org; здесь читаем активные для КПП (для выбора смены).
// ─────────────────────────────────────────────────────────────────────────────
import { http } from '@/api/http';

/** Активные для КПП фестивали — для выбора при открытии смены.
 *  @returns {Promise<Array<{id:string,name:string,year:?number,active:boolean,active_for_kpp:boolean}>>} */
export async function loadKppFestivals() {
    const { data } = await http.get('/api/festivals');
    return data.festivals || [];
}
