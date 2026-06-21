<script setup>
// Экран «Поиск без QR» (Ф5, PR-5): когда у гостя нет работающего QR.
//   поле → онлайн /api/search (богатый поиск) / офлайн по снимку (имя/номер) →
//   список → «Пропустить» по строке. Впуск через тот же doEnter, что и сканер.
import { ref } from 'vue';
import { searchTickets } from '@/services/search';
import { doEnter } from '@/services/scan';
import { highlightMatch } from '@/lib/highlight';

const q = ref('');
const rows = ref([]);
const loading = ref(false);
const searched = ref(false);
const offline = ref(false);
// Запрос, по которому показан текущий список — для подсветки совпадений в результатах.
const lastQuery = ref('');
// Статусы впуска по ключу строки: 'ok' | 'queued' | сообщение об ошибке.
const entered = ref({});

const online = () => navigator.onLine;

// Подсветка найденного: где в строке встретился запрос — выделяем жирным (XSS-безопасно).
const hl = (value) => highlightMatch(value, lastQuery.value);

async function run() {
    const term = q.value.trim();
    if (!term) return;
    loading.value = true;
    offline.value = !online();
    try {
        rows.value = await searchTickets(term, { online: online() });
        lastQuery.value = term;
        searched.value = true;
        entered.value = {};
    } finally {
        loading.value = false;
    }
}

async function enter(row) {
    if (!row.kilter || entered.value[row.key]) return;
    const r = await doEnter(
        { type: row.type, id: row.kilter, key: row.key },
        { online: online() }
    );
    entered.value = {
        ...entered.value,
        [row.key]: r.ok ? (r.queued ? 'queued' : 'ok') : r.message || 'Ошибка'
    };
    if (navigator.vibrate) {
        try {
            navigator.vibrate(r.ok ? 60 : [120, 60, 120]);
        } catch {
            /* нет вибро */
        }
    }
}
</script>

<template>
    <section class="search">
        <form class="search-bar" @submit.prevent="run">
            <input
                v-model="q"
                class="search-input"
                placeholder="ФИО, телефон, № заказа, номер билета"
                autofocus
            />
            <button type="submit" class="search-btn" :disabled="loading">
                <i class="pi" :class="loading ? 'pi-spinner pi-spin' : 'pi-search'"></i>
            </button>
        </form>

        <p v-if="offline" class="search-offline">
            <i class="pi pi-ban"></i> Офлайн: поиск по снимку (имя / номер билета)
        </p>

        <div v-if="searched && rows.length === 0 && !loading" class="search-empty">
            Ничего не найдено
        </div>

        <ul class="search-list">
            <li v-for="row in rows" :key="row.key" class="search-row">
                <div class="sr-main">
                    <div class="sr-name" v-html="hl(row.name)"></div>
                    <div class="sr-meta">
                        <span
                            v-if="row.color"
                            class="sr-color"
                            :style="{ background: row.color }"
                            :title="'Браслет: ' + row.color"
                        ></span>
                        <span class="sr-type">{{ row.typeTicket }}</span>
                        <span v-if="row.kilter" class="sr-kilter">№ {{ row.kilter }}</span>
                    </div>
                    <div v-if="row.dateChange" class="sr-entered">Уже впущен: {{ row.dateChange }}</div>
                    <!-- Полные данные билета: видны только ролям с правом ticket.pii (бэкенд их и присылает только им). -->
                    <div
                        v-if="row.phone || row.email || row.telegram || row.city || row.carNumber || row.childName || row.parentPhone || row.externalOrderNo || row.comment"
                        class="sr-pii"
                    >
                        <span v-if="row.phone" class="sr-pii-item"><i class="pi pi-phone"></i> <span v-html="hl(row.phone)"></span></span>
                        <span v-if="row.email" class="sr-pii-item"><i class="pi pi-envelope"></i> <span v-html="hl(row.email)"></span></span>
                        <span v-if="row.telegram" class="sr-pii-item"><i class="pi pi-send"></i> <span v-html="hl(row.telegram)"></span></span>
                        <span v-if="row.city" class="sr-pii-item"><i class="pi pi-map-marker"></i> <span v-html="hl(row.city)"></span></span>
                        <span v-if="row.carNumber" class="sr-pii-item">Авто: <span v-html="hl(row.carNumber)"></span></span>
                        <span v-if="row.childName" class="sr-pii-item">Ребёнок: <span v-html="hl(row.childName)"></span></span>
                        <span v-if="row.parentPhone" class="sr-pii-item">Родитель: <span v-html="hl(row.parentPhone)"></span></span>
                        <span v-if="row.externalOrderNo" class="sr-pii-item">№ заказа: <span v-html="hl(row.externalOrderNo)"></span></span>
                        <span v-if="row.comment" class="sr-pii-item sr-pii-comment" v-html="hl(row.comment)"></span>
                    </div>
                </div>
                <div class="sr-action">
                    <span v-if="entered[row.key] === 'ok'" class="sr-ok"><i class="pi pi-check"></i> впущен</span>
                    <span v-else-if="entered[row.key] === 'queued'" class="sr-ok"><i class="pi pi-clock"></i> офлайн</span>
                    <span v-else-if="entered[row.key]" class="sr-err">{{ entered[row.key] }}</span>
                    <button v-else-if="row.kilter" class="sr-pass" @click="enter(row)">Пропустить</button>
                </div>
            </li>
        </ul>
    </section>
</template>

<style scoped>
.search { display: flex; flex-direction: column; gap: 0.75rem; height: 100%; }
.search-bar { display: flex; gap: 0.5rem; }
.search-input {
    flex: 1; min-height: 48px; padding: 0 0.8rem;
    border: 1px solid #cbd2da; border-radius: 10px; font-size: 1.05rem;
}
.search-btn { min-width: 56px; border: 0; background: #ff7900; color: #fff; border-radius: 10px; font-size: 1.1rem; }
.search-offline { margin: 0; color: #b8860b; font-size: 0.85rem; }
.search-empty { color: #8a93a0; text-align: center; padding: 2rem 0; }

.search-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.5rem; }
.search-row {
    display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;
    background: #fff; border: 1px solid #e3e6ea; border-radius: 12px; padding: 0.7rem 0.9rem;
}
.sr-name { font-weight: 700; font-size: 1.1rem; }
.sr-meta { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.2rem; color: #6b7280; font-size: 0.9rem; }
.sr-color { width: 14px; height: 14px; border-radius: 50%; border: 1px solid #cbd2da; display: inline-block; }
.sr-kilter { font-variant-numeric: tabular-nums; }
.sr-entered { color: #c0392b; font-size: 0.8rem; margin-top: 0.2rem; }
.sr-pii { display: flex; flex-wrap: wrap; gap: 0.3rem 0.9rem; margin-top: 0.35rem; }
.sr-pii-item { color: #374151; font-size: 0.85rem; white-space: nowrap; }
.sr-pii-item i { color: #6b7280; margin-right: 0.15rem; }
.sr-pii-comment { white-space: normal; color: #6b7280; font-style: italic; flex-basis: 100%; }
.sr-pass {
    min-height: 44px; padding: 0 1rem; border: 0; border-radius: 10px;
    background: #1e9e54; color: #fff; font-weight: 700; font-size: 1rem;
}
.sr-ok { color: #1e9e54; font-weight: 700; white-space: nowrap; }
.sr-err { color: #c0392b; font-size: 0.85rem; max-width: 9rem; }
</style>
