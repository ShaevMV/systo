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
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import ConfirmPopup from 'primevue/confirmpopup';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

import FilterBar from '@/components/FilterBar.vue';

// Промокоды. Бэкенд: /api/v1/promoCode/* (admin). ВНИМАНИЕ: эндпоинты нестандартные —
// getListPromoCode / getItemPromoCode / savePromoCode (createOrUpdate). useCrud не подходит,
// поэтому работаем через axios напрямую. Структуру/стиль берём из LocationListView.
const API = '/api/v1/promoCode';

const toast = useToast();
const confirm = useConfirm();

const list = ref([]);
const loading = ref(false);
const saving = ref(false);
const error = ref(null);

// --- Фильтр ---
// В старом фронте серверного фильтра у промокодов НЕ было (getListPromoCode без тела).
// Поэтому фильтруем на клиенте: по имени + типу скидки + активности.
const blankFilter = () => ({ name: '', isPercent: '', active: '' });
const filter = ref(blankFilter());

const DISCOUNT_TYPE_OPTIONS = [
    { label: 'Все', value: '' },
    { label: 'Процент', value: 'percent' },
    { label: 'Фиксированная', value: 'fixed' }
];
const ACTIVE_OPTIONS = [
    { label: 'Все', value: '' },
    { label: 'Активные', value: '1' },
    { label: 'Неактивные', value: '0' }
];

// Клиентский фильтр поверх полного списка.
const filteredList = computed(() => {
    const f = filter.value;
    return list.value.filter((row) => {
        if (
            f.name &&
            !String(row.name ?? '')
                .toLowerCase()
                .includes(f.name.toLowerCase())
        ) {
            return false;
        }
        if (f.isPercent === 'percent' && !row.isPercent) {
            return false;
        }
        if (f.isPercent === 'fixed' && row.isPercent) {
            return false;
        }
        if (f.active === '1' && !row.isSuccess) {
            return false;
        }
        if (f.active === '0' && row.isSuccess) {
            return false;
        }
        return true;
    });
});

function applyFilter() {
    // Фильтрация клиентская — данные уже загружены, ничего дёргать не нужно.
}
function resetFilter() {
    filter.value = blankFilter();
}

// --- Справочник типов билетов для селекта (фестивали бэк проставляет сам, не показываем) ---
const ticketTypes = ref([]);

// --- Список ---
async function loadList() {
    loading.value = true;
    try {
        const { data } = await axios.get(`${API}/getListPromoCode`);
        // Ответ: { listPromoCode: [...] }. Сервер отдаёт объект-словарь, приводим к массиву.
        const raw = data?.listPromoCode ?? [];
        list.value = Array.isArray(raw) ? raw : Object.values(raw);
    } catch (e) {
        list.value = [];
        toast.add({ severity: 'error', summary: 'Не удалось загрузить промокоды', detail: e?.message, life: 3500 });
    } finally {
        loading.value = false;
    }
}

/** Справочник некритичен для сохранения — при ошибке селект остаётся пустым. */
async function loadRefs() {
    const [t] = await Promise.allSettled([axios.post('/api/v1/ticketType/getList', { filter: {}, orderBy: {} })]);
    ticketTypes.value = t.status === 'fulfilled' ? (t.value.data?.list ?? []) : [];
}

// --- Форма (create/edit) ---
const dialogVisible = ref(false);
const blank = () => ({
    id: null,
    name: '',
    discount: null,
    is_percent: false,
    active: true,
    limit: null,
    ticket_type_id: ''
});
const form = ref(blank());

const ticketTypeOptions = computed(() => [{ id: '', name: '— все типы билетов —' }, ...ticketTypes.value]);

function openCreate() {
    form.value = blank();
    error.value = null;
    dialogVisible.value = true;
}

// При редактировании подгружаем актуальные данные элемента (getItemPromoCode → toArrayForTable).
async function openEdit(row) {
    error.value = null;
    form.value = blank();
    try {
        const { data } = await axios.get(`${API}/getItemPromoCode/${row.id}`);
        form.value = {
            id: data.id ?? row.id,
            name: data.name ?? '',
            discount: data.discount ?? null,
            is_percent: !!data.is_percent,
            active: data.active === undefined ? true : !!data.active,
            limit: data.limit ?? null,
            ticket_type_id: data.ticket_type_id ?? ''
        };
    } catch {
        // Фолбэк на данные строки списка, если getItem недоступен.
        form.value = {
            id: row.id,
            name: row.name ?? '',
            discount: row.discount ?? null,
            is_percent: !!row.isPercent,
            active: row.isSuccess === undefined ? true : !!row.isSuccess,
            limit: row.limit?.limit ?? null,
            ticket_type_id: row.ticket_type_id ?? ''
        };
    }
    dialogVisible.value = true;
}

// Клиентская валидация (зеркалит правила бэка: discount > 0; при проценте <= 100).
function validate() {
    if (!form.value.name?.trim()) {
        return 'Название обязательно';
    }
    const d = Number(form.value.discount);
    if (!d || d <= 0) {
        return 'Скидка должна быть больше 0';
    }
    if (form.value.is_percent && d > 100) {
        return 'Скидка при проценте не может быть больше 100';
    }
    return null;
}

// Тело запроса. festival_id НЕ передаём — бэк проставляет его сам (мультифестивальный UUID).
function buildPayload(overrides = {}) {
    return {
        id: form.value.id || null,
        name: form.value.name.trim(),
        discount: Number(form.value.discount),
        is_percent: !!form.value.is_percent,
        active: !!form.value.active,
        limit: form.value.limit === '' || form.value.limit === null ? null : Number(form.value.limit),
        ticket_type_id: form.value.ticket_type_id || null,
        ...overrides
    };
}

async function submit() {
    error.value = null;
    const v = validate();
    if (v) {
        error.value = v;
        return;
    }
    saving.value = true;
    try {
        const payload = buildPayload();
        // savePromoCode/{id?} — с id обновляет, без id создаёт.
        const url = payload.id ? `${API}/savePromoCode/${payload.id}` : `${API}/savePromoCode`;
        await axios.post(url, payload);
        dialogVisible.value = false;
        toast.add({ severity: 'success', summary: form.value.id ? 'Промокод обновлён' : 'Промокод добавлен', life: 2500 });
        await loadList();
    } catch (e) {
        // Ошибки валидации бэка: { errors: { field: [..] } }.
        const errs = e?.response?.data?.errors;
        if (errs) {
            error.value = Object.values(errs).flat().join(' ');
        } else {
            error.value = e?.response?.data?.message || 'Не удалось сохранить промокод';
        }
    } finally {
        saving.value = false;
    }
}

// Удаления у промокодов НЕТ (нет роута delete). Вместо удаления — деактивация
// (savePromoCode с active=false). Активацию назад делаем тем же путём (active=true).
function askToggleActive(event, row) {
    const turnOff = !!row.isSuccess;
    confirm.require({
        target: event.currentTarget,
        message: turnOff ? `Деактивировать промокод «${row.name}»?` : `Активировать промокод «${row.name}»?`,
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: turnOff ? 'Деактивировать' : 'Активировать',
        rejectLabel: 'Отмена',
        acceptProps: { severity: turnOff ? 'danger' : 'success', size: 'small' },
        rejectProps: { text: true, size: 'small' },
        accept: () => toggleActive(row, !turnOff)
    });
}

// Деактивация/активация требует полного тела (createOrUpdate), поэтому сначала
// подтягиваем элемент через getItem, затем сохраняем с нужным active.
async function toggleActive(row, nextActive) {
    try {
        const { data } = await axios.get(`${API}/getItemPromoCode/${row.id}`);
        await axios.post(`${API}/savePromoCode/${row.id}`, {
            id: row.id,
            name: data.name,
            discount: data.discount,
            is_percent: !!data.is_percent,
            active: nextActive,
            limit: data.limit ?? null,
            ticket_type_id: data.ticket_type_id ?? null
        });
        toast.add({ severity: 'success', summary: nextActive ? 'Промокод активирован' : 'Промокод деактивирован', life: 2500 });
        await loadList();
    } catch (e) {
        toast.add({ severity: 'error', summary: e?.response?.data?.message || 'Не удалось изменить активность', life: 3500 });
    }
}

// Отображение лимита: limit.count из max (или ∞).
const limitText = (row) => {
    const used = row.limit?.count ?? 0;
    const max = row.limit?.limit;
    return `${used} / ${max === null || max === undefined ? '∞' : max}`;
};

onMounted(() => {
    loadList();
    loadRefs();
});
</script>

<template>
    <div class="pc-view">
        <div class="pc-header">
            <div>
                <h1>Промокоды</h1>
                <p class="pc-subtitle">Скидки на оргвзнос: процентные или фиксированные, с лимитом использований. Привязываются к типу билета или работают для всех. Удаления нет — неактуальные промокоды деактивируются.</p>
            </div>
            <Button label="Добавить" icon="pi pi-plus" @click="openCreate" />
        </div>

        <FilterBar @apply="applyFilter" @reset="resetFilter">
            <div class="fb-field">
                <label>Название</label>
                <InputText v-model="filter.name" placeholder="Поиск по названию" />
            </div>
            <div class="fb-field">
                <label>Тип скидки</label>
                <Select v-model="filter.isPercent" :options="DISCOUNT_TYPE_OPTIONS" option-label="label" option-value="value" />
            </div>
            <div class="fb-field">
                <label>Активность</label>
                <Select v-model="filter.active" :options="ACTIVE_OPTIONS" option-label="label" option-value="value" />
            </div>
        </FilterBar>

        <Card>
            <template #content>
                <DataTable :value="filteredList" :loading="loading" data-key="id" responsive-layout="scroll" paginator :rows="20" :rows-per-page-options="[20, 50, 100]">
                    <Column header="Название" field="name" sortable />
                    <Column header="Тип скидки">
                        <template #body="{ data }">
                            <Tag :value="data.isPercent ? 'Процент' : 'Фиксированная'" :severity="data.isPercent ? 'info' : 'secondary'" />
                        </template>
                    </Column>
                    <Column header="Скидка">
                        <template #body="{ data }">{{ data.discount }}{{ data.isPercent ? ' %' : ' ₽' }}</template>
                    </Column>
                    <Column header="Использований (всего / макс)">
                        <template #body="{ data }">{{ limitText(data) }}</template>
                    </Column>
                    <Column header="Тип билета">
                        <template #body="{ data }">{{ data.ticket_type_name || 'Все типы' }}</template>
                    </Column>
                    <Column header="Фестиваль">
                        <template #body="{ data }">{{ data.festival || '—' }}</template>
                    </Column>
                    <Column header="Активность">
                        <template #body="{ data }"><Tag :value="data.isSuccess ? 'Активный' : 'Не активный'" :severity="data.isSuccess ? 'success' : 'secondary'" /></template>
                    </Column>
                    <Column header="" :style="{ width: '150px' }">
                        <template #body="{ data }">
                            <Button icon="pi pi-pencil" text rounded aria-label="Изменить" @click="openEdit(data)" />
                            <Button
                                :icon="data.isSuccess ? 'pi pi-ban' : 'pi pi-check-circle'"
                                text
                                rounded
                                :severity="data.isSuccess ? 'danger' : 'success'"
                                :aria-label="data.isSuccess ? 'Деактивировать' : 'Активировать'"
                                @click="askToggleActive($event, data)"
                            />
                        </template>
                    </Column>
                    <template #empty><div class="pc-empty">Промокодов нет</div></template>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="dialogVisible" :header="form.id ? 'Промокод' : 'Новый промокод'" modal :style="{ width: '520px' }">
            <div class="pc-form">
                <div class="pc-field">
                    <label>Название <span class="pc-req">*</span></label>
                    <InputText v-model="form.name" placeholder="Например: AUTUMN2026" />
                </div>
                <div class="pc-field">
                    <label>Тип скидки</label>
                    <div class="pc-check">
                        <Checkbox v-model="form.is_percent" :binary="true" input-id="pc-is-percent" />
                        <label for="pc-is-percent">Процентная скидка (иначе — фиксированная сумма в ₽)</label>
                    </div>
                </div>
                <div class="pc-field">
                    <label>Скидка <span class="pc-req">*</span></label>
                    <InputNumber v-model="form.discount" :min="0" :max="form.is_percent ? 100 : undefined" :suffix="form.is_percent ? ' %' : ' ₽'" :max-fraction-digits="2" show-buttons fluid />
                    <small class="pc-hint">{{ form.is_percent ? 'Процент от цены билета (0–100)' : 'Сумма скидки в рублях' }}</small>
                </div>
                <div class="pc-field">
                    <label>Тип билета</label>
                    <Select v-model="form.ticket_type_id" :options="ticketTypeOptions" option-label="name" option-value="id" placeholder="— все типы билетов —" filter show-clear />
                    <small class="pc-hint">Оставьте пустым, чтобы промокод действовал на все типы оргвзноса</small>
                </div>
                <div class="pc-field">
                    <label>Лимит использований</label>
                    <InputNumber v-model="form.limit" :min="0" placeholder="∞ (без лимита)" show-buttons fluid />
                    <small class="pc-hint">Оставьте пустым для безлимитного промокода</small>
                </div>
                <div class="pc-check">
                    <Checkbox v-model="form.active" :binary="true" input-id="pc-active" />
                    <label for="pc-active">Активен</label>
                </div>
                <small v-if="error" class="pc-error">{{ error }}</small>
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
.pc-view {
    padding: 1.5rem;
    max-width: 1280px;
    margin: 0 auto;
}
.pc-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.pc-header h1 {
    margin: 0;
    font-size: 1.6rem;
}
.pc-subtitle {
    margin: 0.25rem 0 0;
    color: var(--p-text-muted-color, #6b7280);
    max-width: 680px;
}
.pc-empty {
    text-align: center;
    padding: 1.5rem;
    color: var(--p-text-muted-color, #9ca3af);
}
.pc-form {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    padding-top: 0.5rem;
}
.pc-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.pc-field label {
    font-size: 0.8rem;
    font-weight: 600;
}
.pc-req {
    color: var(--p-red-500, #ef4444);
}
.pc-hint {
    color: var(--p-text-muted-color, #9ca3af);
    font-size: 0.75rem;
}
.pc-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.pc-check label {
    font-weight: 400;
}
.pc-error {
    color: var(--p-red-500, #ef4444);
}
</style>
