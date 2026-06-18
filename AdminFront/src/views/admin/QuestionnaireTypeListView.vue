<script setup>
import { ref, computed, onMounted } from 'vue';

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
import ConfirmPopup from 'primevue/confirmpopup';
import { useToast } from 'primevue/usetoast';

import { useCrud } from '@/composables/useCrud';
import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.vue';
import FilterBar from '@/components/FilterBar.vue';

// Типы анкет — справочник шаблонов анкет (slug `code` + динамические поля `questions`).
// Бэкенд: /api/v1/questionnaireType/* (getList отдаёт { success, list } — стандартный контракт useCrud).
const { list, loading, saving, error, loadList, save, remove } = useCrud('/api/v1/questionnaireType');
const toast = useToast();

// Фильтр (поля 1:1 со старым QuestionnaireTypeFilter: название + активность).
const blankFilter = () => ({ name: '', active: '' });
const filter = ref(blankFilter());
const ACTIVE_OPTIONS = [
    { label: 'Все', value: '' },
    { label: 'Активные', value: '1' },
    { label: 'Неактивные', value: '0' }
];

function applyFilter() {
    loadList({ filter: filter.value, orderBy: {} });
}
function resetFilter() {
    filter.value = blankFilter();
    loadList({ filter: blankFilter(), orderBy: {} });
}

// Кол-во вопросов в строке таблицы (questions приходит массивом — Eloquent-каст 'array').
const questionsCount = (row) => (Array.isArray(row?.questions) ? row.questions.length : 0);

const dialogVisible = ref(false);
const blank = () => ({
    id: null,
    name: '',
    code: '',
    sort: 0,
    active: true,
    questionsJson: '[]'
});
const form = ref(blank());
const formError = computed(() => error.value);

function openCreate() {
    form.value = blank();
    error.value = null;
    dialogVisible.value = true;
}

function openEdit(row) {
    // questions может прийти массивом (норма) или строкой (на всякий случай) — нормализуем в pretty JSON.
    let questions = row.questions;
    if (typeof questions === 'string') {
        try {
            questions = JSON.parse(questions);
        } catch {
            questions = [];
        }
    }
    form.value = {
        id: row.id,
        name: row.name ?? '',
        code: row.code ?? '',
        sort: Number.isFinite(row.sort) ? row.sort : 0,
        active: row.active === undefined ? true : !!row.active,
        questionsJson: JSON.stringify(Array.isArray(questions) ? questions : [], null, 2)
    };
    error.value = null;
    dialogVisible.value = true;
}

async function submit() {
    if (!form.value.name?.trim()) {
        error.value = 'Название обязательно';
        return;
    }
    // Богатый field-by-field редактор `questions` — отдельная под-фича (follow-up).
    // Сейчас правим как JSON: парсим с понятной ошибкой.
    let questions;
    try {
        questions = JSON.parse(form.value.questionsJson || '[]');
    } catch {
        error.value = 'Некорректный JSON в поле questions';
        return;
    }
    if (!Array.isArray(questions)) {
        error.value = 'Поле questions должно быть JSON-массивом';
        return;
    }
    const data = {
        name: form.value.name.trim(),
        code: form.value.code?.trim() || null,
        sort: form.value.sort || 0,
        active: form.value.active,
        questions
    };
    const res = await save(data, form.value.id);
    if (res.ok) {
        dialogVisible.value = false;
        toast.add({ severity: 'success', summary: form.value.id ? 'Тип анкеты отредактирован' : 'Тип анкеты создан', life: 2500 });
        await loadList({ filter: filter.value, orderBy: {} });
    }
}

async function onDelete(row) {
    const ok = await remove(row.id);
    if (ok) {
        toast.add({ severity: 'success', summary: 'Тип анкеты удалён', life: 2500 });
        await loadList({ filter: filter.value, orderBy: {} });
    } else {
        toast.add({ severity: 'error', summary: error.value || 'Не удалось удалить', life: 3500 });
    }
}

onMounted(() => {
    loadList({ filter: blankFilter(), orderBy: {} });
});
</script>

<template>
    <div class="qt-view">
        <div class="qt-header">
            <div>
                <h1>Типы анкет</h1>
                <p class="qt-subtitle">Шаблоны анкет гостей: код (slug), название и набор динамических полей (questions). По коду тип привязывается к типам билетов и локациям.</p>
            </div>
            <Button label="Создать" icon="pi pi-plus" @click="openCreate" />
        </div>

        <FilterBar @apply="applyFilter" @reset="resetFilter">
            <div class="fb-field">
                <label>Название</label>
                <InputText v-model="filter.name" placeholder="Название типа анкеты" @keyup.enter="applyFilter" />
            </div>
            <div class="fb-field">
                <label>Активность</label>
                <Select v-model="filter.active" :options="ACTIVE_OPTIONS" option-label="label" option-value="value" />
            </div>
        </FilterBar>

        <Card>
            <template #content>
                <DataTable :value="list" :loading="loading" data-key="id" responsive-layout="scroll" paginator :rows="20" :rows-per-page-options="[20, 50, 100]">
                    <Column header="Код" field="code">
                        <template #body="{ data }">{{ data.code || '—' }}</template>
                    </Column>
                    <Column header="Название" field="name" sortable />
                    <Column header="Вопросов">
                        <template #body="{ data }">{{ questionsCount(data) }}</template>
                    </Column>
                    <Column header="Сортировка" field="sort" sortable />
                    <Column header="Активен">
                        <template #body="{ data }"><Tag :value="data.active ? 'да' : 'нет'" :severity="data.active ? 'success' : 'secondary'" /></template>
                    </Column>
                    <Column header="" :style="{ width: '110px' }">
                        <template #body="{ data }">
                            <Button icon="pi pi-pencil" text rounded aria-label="Изменить" @click="openEdit(data)" />
                            <ConfirmDeleteButton :message="`Удалить тип анкеты «${data.name}»?`" @confirm="onDelete(data)" />
                        </template>
                    </Column>
                    <template #empty><div class="qt-empty">Типов анкет нет</div></template>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="dialogVisible" :header="form.id ? 'Тип анкеты' : 'Новый тип анкеты'" modal :style="{ width: '640px' }">
            <div class="qt-form">
                <div class="qt-field">
                    <label>Название <span class="qt-req">*</span></label>
                    <InputText v-model="form.name" placeholder="Например: Гостевая анкета" />
                </div>
                <div class="qt-field">
                    <label>Код (slug)</label>
                    <InputText v-model="form.code" placeholder="Например: guest" />
                    <small class="qt-hint">Уникальный код для привязки (guest / new_user / child). По нему тип ищется в коде.</small>
                </div>
                <div class="qt-field">
                    <label>Сортировка</label>
                    <InputNumber v-model="form.sort" :use-grouping="false" show-buttons />
                </div>
                <div class="qt-field">
                    <label>Вопросы (questions, JSON)</label>
                    <Textarea v-model="form.questionsJson" rows="10" class="qt-json" placeholder='[{"name":"agy","title":"Возраст","type":"number","required":false}]' />
                    <small class="qt-hint">Массив объектов конфигурации полей анкеты. Редактируется как JSON. Богатый редактор полей — отдельная под-фича (follow-up).</small>
                </div>
                <div class="qt-check">
                    <Checkbox v-model="form.active" :binary="true" input-id="qt-active" />
                    <label for="qt-active">Активен</label>
                </div>
                <small v-if="formError" class="qt-error">{{ formError }}</small>
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
.qt-view {
    padding: 1.5rem;
    max-width: 1280px;
    margin: 0 auto;
}
.qt-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.qt-header h1 {
    margin: 0;
    font-size: 1.6rem;
}
.qt-subtitle {
    margin: 0.25rem 0 0;
    color: var(--p-text-muted-color, #6b7280);
    max-width: 680px;
}
.qt-empty {
    text-align: center;
    padding: 1.5rem;
    color: var(--p-text-muted-color, #9ca3af);
}
.qt-form {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    padding-top: 0.5rem;
}
.qt-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.qt-field label {
    font-size: 0.8rem;
    font-weight: 600;
}
.qt-req {
    color: var(--p-red-500, #ef4444);
}
.qt-hint {
    color: var(--p-text-muted-color, #9ca3af);
    font-size: 0.75rem;
}
.qt-json {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.8rem;
}
.qt-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.qt-error {
    color: var(--p-red-500, #ef4444);
}
</style>
