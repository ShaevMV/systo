<script setup>
// Экран «Поиск без QR» (Ф5, PR-5): когда у гостя нет работающего QR.
//   поле → онлайн /api/search (богатый поиск) / офлайн по снимку (имя/номер) →
//   список → «Пропустить» по строке. Впуск через тот же doEnter, что и сканер.
import { ref } from 'vue';
import { searchTickets } from '@/services/search';
import { doEnter } from '@/services/scan';

const q = ref('');
const rows = ref([]);
const loading = ref(false);
const searched = ref(false);
const offline = ref(false);
// Статусы впуска по ключу строки: 'ok' | 'queued' | сообщение об ошибке.
const entered = ref({});

const online = () => navigator.onLine;

async function run() {
    const term = q.value.trim();
    if (!term) return;
    loading.value = true;
    offline.value = !online();
    try {
        rows.value = await searchTickets(term, { online: online() });
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
                    <div class="sr-name">{{ row.name }}</div>
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
.sr-pass {
    min-height: 44px; padding: 0 1rem; border: 0; border-radius: 10px;
    background: #1e9e54; color: #fff; font-weight: 700; font-size: 1rem;
}
.sr-ok { color: #1e9e54; font-weight: 700; white-space: nowrap; }
.sr-err { color: #c0392b; font-size: 0.85rem; max-width: 9rem; }
</style>
