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
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import ConfirmPopup from 'primevue/confirmpopup';
import { useToast } from 'primevue/usetoast';

import { useCrud } from '@/composables/useCrud';
import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.vue';
import FilterBar from '@/components/FilterBar.vue';

// Опции к билетам (доп. условия со своей стоимостью, v2.6.0). Бэкенд: /api/v1/option/* —
// read публичный, write только admin. Контракт стандартный (getList/create/edit/delete),
// поэтому работаем через useCrud. Базовая сущность опции — name/active/festival_id;
// цена опции живёт отдельно (волны цен в option_price — отдельная под-фича, тут не трогаем).
// Привязка к типам билетов + описание per-связка передаётся в data.bindings[].
const { list, loading, saving, error, loadList, save, remove } = useCrud('/api/v1/option');
const toast = useToast();

// Фильтр (поля 1:1 со старым OptionFilter: name / festival_id / active).
const blankFilter = () => ({ name: '', festival_id: '', active: '' });
const filter = ref(blankFilter());
const ACTIVE_OPTIONS = [
    { label: 'Все', value: '' },
    { label: 'Активные', value: '1' },
    { label: 'Неактивные', value: '0' }
];

function applyFilter() {
    loadList({ filter: filter.value });
}
function resetFilter() {
    filter.value = blankFilter();
    loadList();
}

// Справочники для селектов и привязок.
const festivals = ref([]);
const ticketTypes = ref([]);

const festivalName = (id) => {
    const f = festivals.value.find((x) => x.id === id);
    return f ? `${f.name}${f.year ? ' ' + f.year : ''}` : '—';
};

const dialogVisible = ref(false);
const blank = () => ({
    id: null,
    name: '',
    festival_id: '',
    active: true,
    // map ticket_type_id -> description (привязки опции к типам билетов)
    bindings: {}
});
const form = ref(blank());
const formError = computed(() => error.value);

// Типы билетов выбранного фестиваля (как в старом OptionItem.availableTicketTypes).
const availableTicketTypes = computed(() => {
    const all = ticketTypes.value || [];
    if (!form.value.festival_id) return all;
    return all.filter((t) => !t.festivalList || t.festivalList.some((f) => f.id === form.value.festival_id));
});

const isBound = (ticketTypeId) => Object.prototype.hasOwnProperty.call(form.value.bindings, ticketTypeId);
const getDescription = (ticketTypeId) => form.value.bindings[ticketTypeId] ?? '';

function toggleBinding(ticketTypeId, checked) {
    const next = { ...form.value.bindings };
    if (checked) {
        next[ticketTypeId] = next[ticketTypeId] ?? '';
    } else {
        delete next[ticketTypeId];
    }
    form.value.bindings = next;
}
function setDescription(ticketTypeId, value) {
    form.value.bindings = { ...form.value.bindings, [ticketTypeId]: value };
}

function openCreate() {
    form.value = blank();
    error.value = null;
    dialogVisible.value = true;
}

async function openEdit(row) {
    form.value = {
        id: row.id,
        name: row.name ?? '',
        festival_id: row.festival_id ?? '',
        active: row.active === undefined ? true : !!row.active,
        bindings: {}
    };
    error.value = null;
    dialogVisible.value = true;
    // Привязки приходят только из getItem (в списке их нет) — подгружаем отдельно.
    try {
        const r = await axios.get(`/api/v1/option/getItem/${row.id}`);
        const dict = {};
        (r.data?.item?.bindings ?? []).forEach((b) => {
            dict[b.ticket_type_id] = b.description ?? '';
        });
        form.value.bindings = dict;
    } catch {
        // Привязки некритичны для отображения формы — оставляем пустыми.
    }
}

async function submit() {
    if (!form.value.name?.trim() || !form.value.festival_id) {
        error.value = 'Название и фестиваль обязательны';
        return;
    }
    const data = {
        name: form.value.name.trim(),
        festival_id: form.value.festival_id,
        active: form.value.active,
        bindings: Object.entries(form.value.bindings).map(([ticket_type_id, description]) => ({
            ticket_type_id,
            description: description || null
        }))
    };
    const res = await save(data, form.value.id);
    if (res.ok) {
        dialogVisible.value = false;
        toast.add({ severity: 'success', summary: form.value.id ? 'Опция отредактирована' : 'Опция создана', life: 2500 });
        await loadList({ filter: filter.value });
    }
}

async function onDelete(row) {
    const ok = await remove(row.id);
    if (ok) {
        toast.add({ severity: 'success', summary: 'Опция удалена', life: 2500 });
        await loadList({ filter: filter.value });
    } else {
        toast.add({ severity: 'error', summary: error.value || 'Не удалось удалить', life: 3500 });
    }
}

/** Справочники некритичны для сохранения — при ошибке селекты остаются пустыми. */
async function loadRefs() {
    const [f, t] = await Promise.allSettled([axios.get('/api/v1/festival/getFestivalList'), axios.post('/api/v1/ticketType/getList', { filter: {}, orderBy: {} })]);
    festivals.value = f.status === 'fulfilled' ? (f.value.data?.festivalDto ?? []) : [];
    ticketTypes.value = t.status === 'fulfilled' ? (t.value.data?.list ?? []) : [];
}

// Опции селектов.
const festivalFilterOptions = computed(() => [{ id: '', label: 'Все' }, ...festivals.value.map((f) => ({ id: f.id, label: `${f.name}${f.year ? ' ' + f.year : ''}` }))]);
const festivalOptions = computed(() => festivals.value.map((f) => ({ id: f.id, label: `${f.name}${f.year ? ' ' + f.year : ''}` })));

onMounted(() => {
    loadList();
    loadRefs();
});
</script>

<template>
    <div class="opt-view">
        <div class="opt-header">
            <div>
                <h1>Опции к билетам</h1>
                <p class="opt-subtitle">Дополнительные условия к билету со своей стоимостью (например «Саженец» к оргвзносу). Привязываются к типам билетов; описание гость видит в форме покупки. Цена опции (волны цен) задаётся отдельно.</p>
            </div>
            <Button label="Создать" icon="pi pi-plus" @click="openCreate" />
        </div>

        <FilterBar @apply="applyFilter" @reset="resetFilter">
            <div class="fb-field">
                <label>Название</label>
                <InputText v-model="filter.name" placeholder="Часть названия опции" @keyup.enter="applyFilter" />
            </div>
            <div class="fb-field">
                <label>Фестиваль</label>
                <Select v-model="filter.festival_id" :options="festivalFilterOptions" option-label="label" option-value="id" placeholder="Все" filter />
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
                        <template #body="{ data }">{{ festivalName(data.festival_id) }}</template>
                    </Column>
                    <Column header="Активна">
                        <template #body="{ data }"><Tag :value="data.active ? 'да' : 'нет'" :severity="data.active ? 'success' : 'secondary'" /></template>
                    </Column>
                    <Column header="" :style="{ width: '110px' }">
                        <template #body="{ data }">
                            <Button icon="pi pi-pencil" text rounded aria-label="Изменить" @click="openEdit(data)" />
                            <ConfirmDeleteButton :message="`Удалить опцию «${data.name}»? Привязки к типам билетов и волны цен будут удалены каскадно.`" @confirm="onDelete(data)" />
                        </template>
                    </Column>
                    <template #empty><div class="opt-empty">Опций ещё нет. Создайте первую.</div></template>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="dialogVisible" :header="form.id ? 'Опция' : 'Новая опция'" modal :style="{ width: '560px' }">
            <div class="opt-form">
                <div class="opt-field">
                    <label>Название <span class="opt-req">*</span></label>
                    <InputText v-model="form.name" placeholder="Например: Саженец" />
                </div>
                <div class="opt-field">
                    <label>Фестиваль <span class="opt-req">*</span></label>
                    <Select v-model="form.festival_id" :options="festivalOptions" option-label="label" option-value="id" placeholder="— выберите фестиваль —" filter />
                </div>
                <div class="opt-check">
                    <Checkbox v-model="form.active" :binary="true" input-id="opt-active" />
                    <label for="opt-active">Активна (видна гостям в форме покупки)</label>
                </div>

                <div class="opt-bindings">
                    <label class="opt-bindings-title">Привязка к типам билетов</label>
                    <small class="opt-hint">Отметьте типы билетов, к которым опция применима. Для каждой связки задайте своё описание — его увидит гость.</small>
                    <div v-for="tt in availableTicketTypes" :key="tt.id" class="opt-binding-row">
                        <div class="opt-check">
                            <Checkbox :model-value="isBound(tt.id)" :binary="true" :input-id="`opt-tt-${tt.id}`" @update:model-value="(v) => toggleBinding(tt.id, v)" />
                            <label :for="`opt-tt-${tt.id}`" class="opt-binding-name">{{ tt.name }}</label>
                        </div>
                        <Textarea v-if="isBound(tt.id)" :model-value="getDescription(tt.id)" rows="2" auto-resize placeholder="Например: Один саженец местного питомника в подарок" @update:model-value="(v) => setDescription(tt.id, v)" />
                    </div>
                    <small v-if="!availableTicketTypes.length" class="opt-hint">Нет типов билетов для выбранного фестиваля. Сначала создайте типы билетов.</small>
                </div>

                <small v-if="form.id" class="opt-hint">Волны цен опции настраиваются отдельно (после сохранения).</small>
                <small v-if="formError" class="opt-error">{{ formError }}</small>
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
.opt-view {
    padding: 1.5rem;
    max-width: 1280px;
    margin: 0 auto;
}
.opt-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.opt-header h1 {
    margin: 0;
    font-size: 1.6rem;
}
.opt-subtitle {
    margin: 0.25rem 0 0;
    color: var(--p-text-muted-color, #6b7280);
    max-width: 680px;
}
.opt-empty {
    text-align: center;
    padding: 1.5rem;
    color: var(--p-text-muted-color, #9ca3af);
}
.opt-form {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    padding-top: 0.5rem;
}
.opt-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.opt-field label {
    font-size: 0.8rem;
    font-weight: 600;
}
.opt-req {
    color: var(--p-red-500, #ef4444);
}
.opt-hint {
    color: var(--p-text-muted-color, #9ca3af);
    font-size: 0.75rem;
}
.opt-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.opt-bindings {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    border-top: 1px solid var(--p-content-border-color, #e5e7eb);
    padding-top: 0.75rem;
}
.opt-bindings-title {
    font-size: 0.85rem;
    font-weight: 600;
}
.opt-binding-row {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    padding: 0.4rem 0.5rem;
    border: 1px solid var(--p-content-border-color, #e5e7eb);
    border-radius: 6px;
}
.opt-binding-name {
    font-weight: 600;
}
.opt-error {
    color: var(--p-red-500, #ef4444);
}
</style>
