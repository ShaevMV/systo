<script setup>
import { ref, computed, onMounted } from 'vue';
import { useStore } from 'vuex';

import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import Timeline from 'primevue/timeline';
import ProgressSpinner from 'primevue/progressspinner';
import ConfirmPopup from 'primevue/confirmpopup';
import { useToast } from 'primevue/usetoast';

import FilterBar from '@/components/FilterBar.vue';
import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.vue';

// Фестивали — мастер каталога на org (AF-7). Бэкенд: /api/v1/festival/* (admin на write).
// CRUD + журнал изменений. Vuex-модуль appFestival (НЕ путать с appFestivalTickets).
const store = useStore();
const toast = useToast();

const list = computed(() => {
    const v = store.getters['appFestival/getList'];
    return Array.isArray(v) ? v : [];
});
const loading = computed(() => store.getters['appFestival/isLoading']);
const fieldError = (field) => store.getters['appFestival/getError'](field);

// Фильтр (whitelist бэкенда: name / year / active).
const blankFilter = () => ({ name: '', year: null, active: '' });
const filter = ref(blankFilter());
const ACTIVE_OPTIONS = [
    { label: 'Все', value: '' },
    { label: 'Активные', value: '1' },
    { label: 'Неактивные', value: '0' }
];

/** Чистим фильтр от пустых значений — бэкенд-фильтр это whitelist по непустым ключам. */
function buildFilter() {
    const f = {};
    if (filter.value.name?.trim()) f.name = filter.value.name.trim();
    if (filter.value.year != null && filter.value.year !== '') f.year = filter.value.year;
    if (filter.value.active !== '') f.active = filter.value.active;
    return f;
}

function applyFilter() {
    store.dispatch('appFestival/loadList', { filter: buildFilter(), orderBy: { name: 'asc' } });
}
function resetFilter() {
    filter.value = blankFilter();
    store.dispatch('appFestival/loadList', { orderBy: { name: 'asc' } });
}

// ───────── Форма создания / редактирования ─────────
const dialogVisible = ref(false);
const saving = ref(false);
const formError = ref('');
const blank = () => ({ id: null, name: '', year: new Date().getFullYear(), active: true });
const form = ref(blank());
const isEdit = computed(() => !!form.value.id);

function openCreate() {
    form.value = blank();
    formError.value = '';
    store.commit('appFestival/clearError');
    dialogVisible.value = true;
}

function openEdit(row) {
    form.value = {
        id: row.id,
        name: row.name ?? '',
        year: row.year ?? new Date().getFullYear(),
        active: row.active === undefined ? true : !!row.active
    };
    formError.value = '';
    store.commit('appFestival/clearError');
    dialogVisible.value = true;
}

async function submit() {
    // Клиентская проверка по контракту бэкенда (name required, year 2000..2100).
    if (!form.value.name?.trim()) {
        formError.value = 'Название обязательно';
        return;
    }
    if (form.value.year == null || form.value.year < 2000 || form.value.year > 2100) {
        formError.value = 'Год должен быть в диапазоне 2000–2100';
        return;
    }
    formError.value = '';
    saving.value = true;
    const data = { name: form.value.name.trim(), year: form.value.year, active: form.value.active };
    try {
        const action = isEdit.value ? 'edit' : 'create';
        const res = await store.dispatch('appFestival/' + action, { id: form.value.id, data });
        if (res?.success === false) {
            formError.value = res.message || 'Ошибка сохранения';
            return;
        }
        dialogVisible.value = false;
        toast.add({ severity: 'success', summary: isEdit.value ? 'Фестиваль отредактирован' : 'Фестиваль создан', life: 2500 });
        applyFilter();
    } catch {
        // 422 → ошибки полей лежат в сторе (getError), общий хинт показываем тут.
        formError.value = 'Проверьте правильность заполнения полей';
    } finally {
        saving.value = false;
    }
}

async function onDelete(row) {
    try {
        const res = await store.dispatch('appFestival/remove', { id: row.id });
        if (res?.success === false) {
            toast.add({ severity: 'error', summary: res.message || 'Не удалось удалить', life: 3500 });
            return;
        }
        toast.add({ severity: 'success', summary: 'Фестиваль удалён', life: 2500 });
        applyFilter();
    } catch {
        toast.add({ severity: 'error', summary: 'Не удалось удалить фестиваль', life: 3500 });
    }
}

// ───────── История изменений ─────────
const historyVisible = ref(false);
const historyTitle = ref('');
const history = computed(() => {
    const v = store.getters['appFestival/getHistory'];
    return Array.isArray(v) ? v : [];
});
const historyLoading = computed(() => store.getters['appFestival/isHistoryLoading']);

const HISTORY_LABELS = {
    festival_created: 'Фестиваль создан',
    festival_edited: 'Фестиваль изменён',
    festival_deleted: 'Фестиваль удалён'
};
const historyLabel = (name) => HISTORY_LABELS[name] || name;

function openHistory(row) {
    historyTitle.value = `${row.name}${row.year ? ' ' + row.year : ''}`;
    historyVisible.value = true;
    store.dispatch('appFestival/loadHistory', { id: row.id });
}

function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

/** Кто инициатор события (имя/почта актора, если есть). */
function actorText(ev) {
    if (ev.actor_name && ev.actor_email) return `${ev.actor_name} (${ev.actor_email})`;
    return ev.actor_name || ev.actor_email || ev.actor_type || '—';
}

/** Что изменилось — для festival_edited payload.changed: [...], иначе показываем поля payload. */
function changeText(ev) {
    const p = ev.payload;
    if (!p) return '';
    if (Array.isArray(p.changed)) return p.changed.length ? 'Изменены поля: ' + p.changed.join(', ') : '';
    const parts = [];
    if (p.name !== undefined) parts.push(`название: ${p.name}`);
    if (p.year !== undefined) parts.push(`год: ${p.year}`);
    if (p.active !== undefined) parts.push(`активность: ${p.active ? 'да' : 'нет'}`);
    return parts.join(', ');
}

onMounted(() => {
    store.dispatch('appFestival/loadList', { orderBy: { name: 'asc' } });
});
</script>

<template>
    <div class="fest-view">
        <div class="fest-header">
            <div>
                <h1>Фестивали</h1>
                <p class="fest-subtitle">Мастер-каталог фестивалей на org. К фестивалю привязаны типы билетов, цены, локации, промокоды и заказы. Удаление — мягкое (данные сохраняются).</p>
            </div>
            <Button label="Создать" icon="pi pi-plus" @click="openCreate" />
        </div>

        <FilterBar @apply="applyFilter" @reset="resetFilter">
            <div class="fb-field">
                <label>Название</label>
                <InputText v-model="filter.name" placeholder="Название фестиваля" @keyup.enter="applyFilter" />
            </div>
            <div class="fb-field">
                <label>Год</label>
                <InputNumber v-model="filter.year" :min="2000" :max="2100" :use-grouping="false" placeholder="Любой" show-clear @keydown.enter="applyFilter" />
            </div>
            <div class="fb-field">
                <label>Активность</label>
                <Select v-model="filter.active" :options="ACTIVE_OPTIONS" option-label="label" option-value="value" />
            </div>
        </FilterBar>

        <Card>
            <template #content>
                <DataTable :value="list" :loading="loading" data-key="id" responsive-layout="scroll" paginator :rows="20" :rows-per-page-options="[20, 50, 100]">
                    <Column header="Название" field="name" sortable />
                    <Column header="Год" field="year" sortable :style="{ width: '120px' }" />
                    <Column header="Активен" :style="{ width: '120px' }">
                        <template #body="{ data }"><Tag :value="data.active ? 'да' : 'нет'" :severity="data.active ? 'success' : 'secondary'" /></template>
                    </Column>
                    <Column header="" :style="{ width: '150px' }">
                        <template #body="{ data }">
                            <Button icon="pi pi-history" text rounded aria-label="История изменений" title="История изменений" @click="openHistory(data)" />
                            <Button icon="pi pi-pencil" text rounded aria-label="Изменить" @click="openEdit(data)" />
                            <ConfirmDeleteButton :message="`Удалить фестиваль «${data.name}»? (мягкое удаление)`" @confirm="onDelete(data)" />
                        </template>
                    </Column>
                    <template #empty><div class="fest-empty">Фестивалей нет</div></template>
                </DataTable>
            </template>
        </Card>

        <!-- Форма создания / редактирования -->
        <Dialog v-model:visible="dialogVisible" :header="isEdit ? 'Фестиваль' : 'Новый фестиваль'" modal :style="{ width: '480px' }">
            <div class="fest-form">
                <div class="fest-field">
                    <label>Название <span class="fest-req">*</span></label>
                    <InputText v-model="form.name" placeholder="Например: Солнечное Сысто" :invalid="!!fieldError('name')" />
                    <small v-if="fieldError('name')" class="fest-error">{{ fieldError('name') }}</small>
                </div>
                <div class="fest-field">
                    <label>Год <span class="fest-req">*</span></label>
                    <InputNumber v-model="form.year" :min="2000" :max="2100" :use-grouping="false" placeholder="2026" :invalid="!!fieldError('year')" />
                    <small v-if="fieldError('year')" class="fest-error">{{ fieldError('year') }}</small>
                    <small v-else class="fest-hint">Допустимый диапазон 2000–2100</small>
                </div>
                <div class="fest-check">
                    <Checkbox v-model="form.active" :binary="true" input-id="fest-active" />
                    <label for="fest-active">Активен</label>
                </div>
                <small v-if="formError" class="fest-error">{{ formError }}</small>
            </div>
            <template #footer>
                <Button label="Отмена" text @click="dialogVisible = false" />
                <Button label="Сохранить" icon="pi pi-check" :loading="saving" @click="submit" />
            </template>
        </Dialog>

        <!-- История изменений -->
        <Dialog v-model:visible="historyVisible" modal :header="'История: ' + historyTitle" :style="{ width: '46rem' }" :breakpoints="{ '960px': '90vw', '640px': '100vw' }">
            <div v-if="historyLoading" class="fest-hist-loading">
                <ProgressSpinner style="width: 40px; height: 40px" stroke-width="4" />
            </div>
            <div v-else-if="!history.length" class="fest-empty">История пуста</div>
            <Timeline v-else :value="history" class="fest-timeline">
                <template #content="{ item: ev }">
                    <div class="fest-hist-event">
                        <strong>{{ historyLabel(ev.event_name) }}</strong>
                    </div>
                    <small class="fest-hist-meta">{{ formatDate(ev.occurred_at) }} · {{ actorText(ev) }}</small>
                    <div v-if="changeText(ev)" class="fest-hist-change">{{ changeText(ev) }}</div>
                </template>
            </Timeline>
        </Dialog>

        <ConfirmPopup />
    </div>
</template>

<style scoped>
.fest-view {
    padding: 1.5rem;
    max-width: 1280px;
    margin: 0 auto;
}
.fest-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.fest-header h1 {
    margin: 0;
    font-size: 1.6rem;
}
.fest-subtitle {
    margin: 0.25rem 0 0;
    color: var(--p-text-muted-color, #6b7280);
    max-width: 680px;
}
.fest-empty {
    text-align: center;
    padding: 1.5rem;
    color: var(--p-text-muted-color, #9ca3af);
}
.fest-form {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    padding-top: 0.5rem;
}
.fest-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.fest-field label {
    font-size: 0.8rem;
    font-weight: 600;
}
.fest-req {
    color: var(--p-red-500, #ef4444);
}
.fest-hint {
    color: var(--p-text-muted-color, #9ca3af);
    font-size: 0.75rem;
}
.fest-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.fest-error {
    color: var(--p-red-500, #ef4444);
    font-size: 0.78rem;
}
.fest-hist-loading {
    display: flex;
    justify-content: center;
    padding: 1.5rem;
}
.fest-hist-event {
    display: flex;
    gap: 0.5rem;
    align-items: baseline;
    flex-wrap: wrap;
}
.fest-hist-meta {
    color: var(--p-text-muted-color, #9ca3af);
}
.fest-hist-change {
    margin-top: 0.25rem;
    color: var(--p-text-color, #374151);
}
</style>
