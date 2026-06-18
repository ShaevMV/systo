<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import ConfirmPopup from 'primevue/confirmpopup';
import { useToast } from 'primevue/usetoast';

import { useCrud } from '@/composables/useCrud';
import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.vue';
import FilterBar from '@/components/FilterBar.vue';

// Типы билетов — центральный справочник каталога (оргвзносы, живые билеты, парковка,
// групповые, детские). Бэкенд: /api/v1/ticketType/* — read публичный, write только admin.
// Контракт стандартный (getList/create/edit/delete) → работаем через useCrud.
//
// КВИРК БЭКЕНДА (TicketTypeDto/FestivalDto::fromState): описание, шаблон письма и PDF —
// это поля СВЯЗКИ «тип билета ↔ фестиваль» (pivot), поэтому в payload они идут с префиксом
// festival_*: festival_description / festival_email / festival_pdf / festival_id.
// `sort` — обязательный int в DTO ((int)$data['sort']) → всегда отправляем (по умолчанию 0).
// В этот заход — БАЗОВЫЙ CRUD. Волны цен (ticketTypePrice) и привязка опций — отдельные
// под-CRUD (как у опций), их тут НЕ редактируем.
const { list, loading, saving, error, loadList, save, remove } = useCrud('/api/v1/ticketType');
const toast = useToast();

// Фильтр (поля 1:1 со старым TicketTypeFilter: name / active / is_live_ticket / festival_id).
// КВИРК: TicketTypeGetListFilter::fromState сравнивает active/is_live_ticket/is_parking
// со строкой 'true' — поэтому значения опций именно 'true'/'false' (НЕ '1'/'0'), '' = «все».
const blankFilter = () => ({ name: '', festival_id: '', active: '', is_live_ticket: '' });
const filter = ref(blankFilter());
const ACTIVE_OPTIONS = [
    { label: 'Все', value: '' },
    { label: 'Активные', value: 'true' },
    { label: 'Неактивные', value: 'false' }
];
const LIVE_FILTER_OPTIONS = [
    { label: 'Все', value: '' },
    { label: 'Живые', value: 'true' },
    { label: 'Обычные', value: 'false' }
];

// Пустые строки не шлём в filter — иначе бэкенд примет '' за валидное значение фильтра.
function buildFilter() {
    const f = {};
    if (filter.value.name) f.name = filter.value.name;
    if (filter.value.festival_id) f.festival_id = filter.value.festival_id;
    if (filter.value.active !== '') f.active = filter.value.active;
    if (filter.value.is_live_ticket !== '') f.is_live_ticket = filter.value.is_live_ticket;
    return f;
}
function applyFilter() {
    loadList({ filter: buildFilter() });
}
function resetFilter() {
    filter.value = blankFilter();
    loadList();
}

// Справочники для селектов формы и фильтра.
const festivals = ref([]);
const qTypes = ref([]);
const emailBlades = ref([]);
const pdfBlades = ref([]);

const festivalName = (id) => {
    const f = festivals.value.find((x) => x.id === id);
    return f ? `${f.name}${f.year ? ' ' + f.year : ''}` : '—';
};

const dialogVisible = ref(false);
const blank = () => ({
    id: null,
    name: '',
    price: 0,
    sort: 0,
    groupLimit: null,
    is_live_ticket: false,
    is_parking: false,
    active: true,
    festival_id: '',
    festival_description: '',
    festival_email: '',
    festival_pdf: '',
    questionnaire_type_id: ''
});
const form = ref(blank());
const formError = computed(() => error.value);

function openCreate() {
    form.value = blank();
    error.value = null;
    dialogVisible.value = true;
}

async function openEdit(row) {
    // Прелоад из строки списка (плоские колонки), затем уточняем из getItem
    // (там festival.* вложен и приходят шаблоны письма/PDF/описание связки).
    form.value = {
        id: row.id,
        name: row.name ?? '',
        price: row.price ?? 0,
        sort: row.sort ?? 0,
        groupLimit: row.groupLimit ?? null,
        is_live_ticket: !!row.is_live_ticket,
        is_parking: !!row.is_parking,
        active: row.active === undefined ? true : !!row.active,
        festival_id: row.festival_id ?? '',
        festival_description: '',
        festival_email: '',
        festival_pdf: '',
        questionnaire_type_id: row.questionnaire_type_id ?? ''
    };
    error.value = null;
    dialogVisible.value = true;
    try {
        const r = await axios.get(`/api/v1/ticketType/getItem/${row.id}`);
        const item = r.data?.item;
        if (item) {
            form.value = {
                ...form.value,
                name: item.name ?? form.value.name,
                price: item.price ?? form.value.price,
                sort: item.sort ?? form.value.sort,
                groupLimit: item.groupLimit ?? null,
                is_live_ticket: !!item.is_live_ticket,
                is_parking: !!item.is_parking,
                active: item.active === undefined ? true : !!item.active,
                festival_id: item.festival?.id ?? form.value.festival_id,
                festival_description: item.festival?.description ?? '',
                festival_email: item.festival?.email ?? '',
                festival_pdf: item.festival?.pdf ?? '',
                questionnaire_type_id: item.questionnaire_type_id ?? ''
            };
        }
    } catch {
        // Детали из getItem некритичны — форма уже заполнена из строки списка.
    }
}

async function submit() {
    if (!form.value.name?.trim() || !form.value.festival_id) {
        error.value = 'Название и фестиваль обязательны';
        return;
    }
    // Ключи строго под TicketTypeDto::fromState + FestivalDto::fromState (см. квирк выше).
    const data = {
        name: form.value.name.trim(),
        price: Number(form.value.price) || 0,
        sort: Number(form.value.sort) || 0,
        groupLimit: form.value.groupLimit === null || form.value.groupLimit === '' ? null : Number(form.value.groupLimit),
        is_live_ticket: form.value.is_live_ticket,
        is_parking: form.value.is_parking,
        active: form.value.active,
        festival_id: form.value.festival_id,
        festival_description: form.value.festival_description || null,
        festival_email: form.value.festival_email || null,
        festival_pdf: form.value.festival_pdf || null,
        questionnaire_type_id: form.value.questionnaire_type_id || null
    };
    const res = await save(data, form.value.id);
    if (res.ok) {
        dialogVisible.value = false;
        toast.add({ severity: 'success', summary: form.value.id ? 'Тип билета отредактирован' : 'Тип билета создан', life: 2500 });
        await loadList({ filter: buildFilter() });
    }
}

async function onDelete(row) {
    const ok = await remove(row.id);
    if (ok) {
        toast.add({ severity: 'success', summary: 'Тип билета удалён', life: 2500 });
        await loadList({ filter: buildFilter() });
    } else {
        toast.add({ severity: 'error', summary: error.value || 'Не удалось удалить', life: 3500 });
    }
}

/** Справочники некритичны для сохранения — при ошибке селекты остаются пустыми. */
async function loadRefs() {
    const [f, q, b] = await Promise.allSettled([axios.get('/api/v1/festival/getFestivalList'), axios.post('/api/v1/questionnaireType/getList', { filter: {}, orderBy: {} }), axios.get('/api/v1/ticketType/getBlade')]);
    festivals.value = f.status === 'fulfilled' ? (f.value.data?.festivalDto ?? []) : [];
    qTypes.value = q.status === 'fulfilled' ? (q.value.data?.list ?? []) : [];
    if (b.status === 'fulfilled') {
        emailBlades.value = b.value.data?.list?.email ?? [];
        pdfBlades.value = b.value.data?.list?.pdf ?? [];
    }
}

// Опции селектов.
const festivalFilterOptions = computed(() => [{ id: '', label: 'Все' }, ...festivals.value.map((f) => ({ id: f.id, label: `${f.name}${f.year ? ' ' + f.year : ''}` }))]);
const festivalOptions = computed(() => festivals.value.map((f) => ({ id: f.id, label: `${f.name}${f.year ? ' ' + f.year : ''}` })));
const qTypeOptions = computed(() => [{ id: '', name: '— без анкеты —' }, ...qTypes.value]);
const emailOptions = computed(() => emailBlades.value);
const pdfOptions = computed(() => pdfBlades.value);

onMounted(() => {
    loadList();
    loadRefs();
});
</script>

<template>
    <div class="tt-view">
        <div class="tt-header">
            <div>
                <h1>Типы билетов</h1>
                <p class="tt-subtitle">Центральный справочник каталога: оргвзносы, живые билеты, парковка, групповые и детские билеты. Описание, шаблоны письма и PDF задаются на связке «тип билета ↔ фестиваль».</p>
            </div>
            <Button label="Создать" icon="pi pi-plus" @click="openCreate" />
        </div>

        <FilterBar @apply="applyFilter" @reset="resetFilter">
            <div class="fb-field">
                <label>Название</label>
                <InputText v-model="filter.name" placeholder="Часть названия" @keyup.enter="applyFilter" />
            </div>
            <div class="fb-field">
                <label>Фестиваль</label>
                <Select v-model="filter.festival_id" :options="festivalFilterOptions" option-label="label" option-value="id" placeholder="Все" filter />
            </div>
            <div class="fb-field">
                <label>Тип</label>
                <Select v-model="filter.is_live_ticket" :options="LIVE_FILTER_OPTIONS" option-label="label" option-value="value" />
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
                    <Column header="Фестиваль">
                        <template #body="{ data }">{{ data.festival_name || festivalName(data.festival_id) }}</template>
                    </Column>
                    <Column header="Текущая цена">
                        <template #body="{ data }">{{ data.current_price ?? data.price ?? '—' }}</template>
                    </Column>
                    <Column header="Лимит группы">
                        <template #body="{ data }">{{ data.groupLimit ?? '—' }}</template>
                    </Column>
                    <Column header="Тип">
                        <template #body="{ data }">
                            <Tag v-if="data.is_live_ticket" value="живой" severity="info" class="tt-tag" />
                            <Tag v-if="data.is_parking" value="парковка" severity="warn" class="tt-tag" />
                            <span v-if="!data.is_live_ticket && !data.is_parking">обычный</span>
                        </template>
                    </Column>
                    <Column header="Активен">
                        <template #body="{ data }"><Tag :value="data.active ? 'да' : 'нет'" :severity="data.active ? 'success' : 'secondary'" /></template>
                    </Column>
                    <Column header="" :style="{ width: '110px' }">
                        <template #body="{ data }">
                            <Button icon="pi pi-pencil" text rounded aria-label="Изменить" @click="openEdit(data)" />
                            <ConfirmDeleteButton :message="`Удалить тип билета «${data.name}»?`" @confirm="onDelete(data)" />
                        </template>
                    </Column>
                    <template #empty><div class="tt-empty">Типов билетов ещё нет. Создайте первый.</div></template>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="dialogVisible" :header="form.id ? 'Тип билета' : 'Новый тип билета'" modal :style="{ width: '560px' }">
            <div class="tt-form">
                <div class="tt-field">
                    <label>Название <span class="tt-req">*</span></label>
                    <InputText v-model="form.name" placeholder="Например: Оргвзнос" />
                </div>
                <div class="tt-field">
                    <label>Фестиваль <span class="tt-req">*</span></label>
                    <Select v-model="form.festival_id" :options="festivalOptions" option-label="label" option-value="id" placeholder="— выберите фестиваль —" filter />
                </div>
                <div class="tt-row2">
                    <div class="tt-field">
                        <label>Стартовая цена</label>
                        <InputNumber v-model="form.price" :min="0" :use-grouping="false" placeholder="0" />
                        <small class="tt-hint">Базовая цена; актуальная берётся из волн цен</small>
                    </div>
                    <div class="tt-field">
                        <label>Сорт</label>
                        <InputNumber v-model="form.sort" :min="0" :use-grouping="false" placeholder="0" />
                        <small class="tt-hint">Порядок в списке покупки</small>
                    </div>
                </div>
                <div class="tt-field">
                    <label>Лимит группы (для группового билета)</label>
                    <InputNumber v-model="form.groupLimit" :min="0" :use-grouping="false" placeholder="— не групповой —" show-clear />
                    <small class="tt-hint">Пусто — не групповой билет</small>
                </div>
                <div class="tt-field">
                    <label>Описание</label>
                    <Textarea v-model="form.festival_description" rows="3" auto-resize placeholder="Краткое описание типа билета" />
                </div>
                <div class="tt-field">
                    <label>Тип анкеты</label>
                    <Select v-model="form.questionnaire_type_id" :options="qTypeOptions" option-label="name" option-value="id" placeholder="— без анкеты —" filter />
                    <small class="tt-hint">Шаблон анкеты для гостей этого билета</small>
                </div>
                <div class="tt-field">
                    <label>Шаблон письма (blade)</label>
                    <Select v-model="form.festival_email" :options="emailOptions" placeholder="— по умолчанию —" filter show-clear />
                </div>
                <div class="tt-field">
                    <label>Шаблон билета PDF (blade)</label>
                    <Select v-model="form.festival_pdf" :options="pdfOptions" placeholder="— по умолчанию (pdf) —" filter show-clear />
                </div>
                <div class="tt-check">
                    <Checkbox v-model="form.is_live_ticket" :binary="true" input-id="tt-live" />
                    <label for="tt-live">Живой билет (выдаётся на месте, уникальный номер)</label>
                </div>
                <div class="tt-check">
                    <Checkbox v-model="form.is_parking" :binary="true" input-id="tt-parking" />
                    <label for="tt-parking">Парковка (форма ввода авто вместо ФИО гостей)</label>
                </div>
                <div class="tt-check">
                    <Checkbox v-model="form.active" :binary="true" input-id="tt-active" />
                    <label for="tt-active">Активен (виден в форме покупки)</label>
                </div>

                <Message v-if="form.id" severity="secondary" :closable="false">Волны цен и привязка опций настраиваются на отдельных экранах.</Message>
                <small v-if="formError" class="tt-error">{{ formError }}</small>
            </div>
            <template #footer>
                <Button label="Отмена" text @click="dialogVisible = false" />
                <Button label="Сохранить" icon="pi pi-check" :loading="saving" @click="submit" />
            </template>
        </Dialog>

        <ConfirmPopup />
    </div>
</template>

<style scoped>
.tt-view {
    padding: 1.5rem;
    max-width: 1280px;
    margin: 0 auto;
}
.tt-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.tt-header h1 {
    margin: 0;
    font-size: 1.6rem;
}
.tt-subtitle {
    margin: 0.25rem 0 0;
    color: var(--p-text-muted-color, #6b7280);
    max-width: 680px;
}
.tt-empty {
    text-align: center;
    padding: 1.5rem;
    color: var(--p-text-muted-color, #9ca3af);
}
.tt-tag {
    margin-right: 0.25rem;
}
.tt-form {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    padding-top: 0.5rem;
}
.tt-row2 {
    display: flex;
    gap: 0.9rem;
}
.tt-row2 .tt-field {
    flex: 1;
}
.tt-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.tt-field label {
    font-size: 0.8rem;
    font-weight: 600;
}
.tt-req {
    color: var(--p-red-500, #ef4444);
}
.tt-hint {
    color: var(--p-text-muted-color, #9ca3af);
    font-size: 0.75rem;
}
.tt-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.tt-error {
    color: var(--p-red-500, #ef4444);
}
</style>
