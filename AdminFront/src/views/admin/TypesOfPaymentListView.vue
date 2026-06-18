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

import { useCrud } from '@/composables/useCrud';
import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.vue';
import FilterBar from '@/components/FilterBar.vue';

// Типы оплат — справочник способов оплаты заказа. Бэкенд: /api/v1/typesOfPayment/* (роуты публичные).
// Перенос старого экрана FrontEnd `TypesOfPayment/*` на новые рельсы (useCrud + PrimeVue).
const { list, loading, saving, error, loadList, save, remove } = useCrud('/api/v1/typesOfPayment');
const toast = useToast();

// Выбор «да/нет/все» для select-фильтров. Бэкенд (TypesOfPaymentGetListFilter::fromState)
// читает active как строку === "true"; isBilling — как truthy. Передаём строки "true"/"false".
const TRISTATE_OPTIONS = [
    { label: 'Все', value: '' },
    { label: 'Да', value: 'true' },
    { label: 'Нет', value: 'false' }
];

// ── Фильтр (поля 1:1 со старым TypesOfPaymentFilter: название, активность, биллинг, продавец) ──
const blankFilter = () => ({ name: '', active: '', isBilling: '', userExternalId: '' });
const filter = ref(blankFilter());

/**
 * Бэкенд getList НЕ имеет `?? []` на filter/orderBy — всегда шлём оба ключа, иначе 500.
 * Квирк фильтра: userExternalId ожидается ОБЪЕКТОМ с .id ($data['userExternalId']['id']),
 * поэтому продавца оборачиваем в { id }.
 */
function buildListPayload() {
    const f = {
        name: filter.value.name || null,
        active: filter.value.active || null,
        isBilling: filter.value.isBilling || null,
        userExternalId: filter.value.userExternalId ? { id: filter.value.userExternalId } : null
    };
    return { filter: f, orderBy: {} };
}

function applyFilter() {
    loadList(buildListPayload());
}
function resetFilter() {
    filter.value = blankFilter();
    loadList(buildListPayload());
}

// ── Справочники для селектов (продавцы живых билетов + типы билетов) ──
const sellers = ref([]); // список аккаунтов (продавцы) — для user_external_id
const ticketTypes = ref([]); // типы билетов — для ticket_type_id

const sellerEmail = (id) => (id ? sellers.value.find((x) => x.id === id)?.email || '—' : '—');
const ticketTypeName = (id) => (id ? ticketTypes.value.find((x) => x.id === id)?.name || '—' : '—');

/** Справочники некритичны для сохранения — при ошибке селекты остаются пустыми. */
async function loadRefs() {
    const [a, t] = await Promise.allSettled([
        // account/getList — admin POST, фильтр обязателен (нет `?? []`), шлём пустой
        axios.post('/api/v1/account/getList', { filter: {}, orderBy: {} }),
        axios.post('/api/v1/ticketType/getList', { filter: {}, orderBy: {} })
    ]);
    sellers.value = a.status === 'fulfilled' ? (a.value.data?.list ?? []) : [];
    ticketTypes.value = t.status === 'fulfilled' ? (t.value.data?.list ?? []) : [];
}

// Опции селектов фильтра (с пунктом «Все»).
const sellerFilterOptions = computed(() => [{ id: '', label: 'Все' }, ...sellers.value.map((s) => ({ id: s.id, label: s.email }))]);
// Опции селектов формы (с пунктом «— не выбрано —» = пустая строка → бэкенд трактует как null).
const sellerFormOptions = computed(() => [{ id: '', label: '— не выбрано —' }, ...sellers.value.map((s) => ({ id: s.id, label: s.email }))]);
const ticketTypeFormOptions = computed(() => [{ id: '', label: '— не выбрано —' }, ...ticketTypes.value.map((t) => ({ id: t.id, label: t.name }))]);

// ── Форма создания/редактирования (поля из старого TypesOfPaymentItem) ──
const dialogVisible = ref(false);
const blank = () => ({
    id: null,
    name: '',
    card: '',
    sort: 0,
    user_external_id: '',
    ticket_type_id: '',
    email: '',
    active: true,
    is_billing: false
});
const form = ref(blank());
const formError = computed(() => error.value);

function openCreate() {
    form.value = blank();
    error.value = null;
    dialogVisible.value = true;
}

function openEdit(row) {
    form.value = {
        id: row.id,
        name: row.name ?? '',
        card: row.card ?? '',
        sort: Number(row.sort ?? 0),
        // В списке продавец/тип билета приходят вложенными объектами (seller{id}, ticket_type{id}).
        user_external_id: row.seller?.id ?? '',
        ticket_type_id: row.ticket_type?.id ?? '',
        email: row.email ?? '',
        active: !!row.active,
        is_billing: !!row.is_billing
    };
    error.value = null;
    dialogVisible.value = true;
}

async function submit() {
    if (!form.value.name?.trim()) {
        error.value = 'Название обязательно';
        return;
    }
    // Тело create/edit — ровно поля, которые читает TypesOfPaymentDto::fromState
    // (name, active, sort, is_billing — обязательны; card/email/user_external_id/ticket_type_id — опц.).
    const data = {
        name: form.value.name.trim(),
        card: form.value.card || null,
        sort: Number(form.value.sort) || 0,
        active: form.value.active,
        is_billing: form.value.is_billing,
        user_external_id: form.value.user_external_id || null,
        ticket_type_id: form.value.ticket_type_id || null,
        email: form.value.email || null
    };
    const res = await save(data, form.value.id);
    if (res.ok) {
        dialogVisible.value = false;
        toast.add({ severity: 'success', summary: form.value.id ? 'Тип оплаты отредактирован' : 'Тип оплаты создан', life: 2500 });
        await loadList(buildListPayload());
    }
}

async function onDelete(row) {
    const ok = await remove(row.id);
    if (ok) {
        toast.add({ severity: 'success', summary: 'Тип оплаты удалён', life: 2500 });
        await loadList(buildListPayload());
    } else {
        toast.add({ severity: 'error', summary: error.value || 'Не удалось удалить', life: 3500 });
    }
}

/** Дата создания — человекочитаемый формат (как в QrOrderListView). */
function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

onMounted(() => {
    loadList(buildListPayload());
    loadRefs();
});
</script>

<template>
    <div class="top-view">
        <div class="top-header">
            <div>
                <h1>Типы оплат</h1>
                <p class="top-subtitle">Способы оплаты заказа: название, реквизиты карты, продавец живых билетов, привязка к типу билета и шаблону письма. Флаг «биллинг» — заказ извне с авто-подтверждением.</p>
            </div>
            <Button label="Создать" icon="pi pi-plus" @click="openCreate" />
        </div>

        <FilterBar @apply="applyFilter" @reset="resetFilter">
            <div class="fb-field">
                <label>Название</label>
                <InputText v-model="filter.name" placeholder="Название способа оплаты" @keyup.enter="applyFilter" />
            </div>
            <div class="fb-field">
                <label>Активность</label>
                <Select v-model="filter.active" :options="TRISTATE_OPTIONS" option-label="label" option-value="value" />
            </div>
            <div class="fb-field">
                <label>Биллинг</label>
                <Select v-model="filter.isBilling" :options="TRISTATE_OPTIONS" option-label="label" option-value="value" />
            </div>
            <div class="fb-field">
                <label>Продавец живых билетов</label>
                <Select v-model="filter.userExternalId" :options="sellerFilterOptions" option-label="label" option-value="id" placeholder="Все" filter />
            </div>
        </FilterBar>

        <Card>
            <template #content>
                <DataTable :value="list" :loading="loading" data-key="id" responsive-layout="scroll" paginator :rows="20" :rows-per-page-options="[20, 50, 100]">
                    <Column header="Название" field="name" sortable />
                    <Column header="Сорт" field="sort" sortable :style="{ width: '80px' }" />
                    <Column header="Реализатор">
                        <template #body="{ data }">{{ data.seller?.email || sellerEmail(data.seller?.id) }}</template>
                    </Column>
                    <Column header="Тип билета">
                        <template #body="{ data }">{{ data.ticket_type?.name || ticketTypeName(data.ticket_type?.id) }}</template>
                    </Column>
                    <Column header="Биллинг">
                        <template #body="{ data }"><Tag :value="data.is_billing ? 'да' : 'нет'" :severity="data.is_billing ? 'info' : 'secondary'" /></template>
                    </Column>
                    <Column header="Активен">
                        <template #body="{ data }"><Tag :value="data.active ? 'да' : 'нет'" :severity="data.active ? 'success' : 'secondary'" /></template>
                    </Column>
                    <Column header="Создан">
                        <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                    </Column>
                    <Column header="" :style="{ width: '110px' }">
                        <template #body="{ data }">
                            <Button icon="pi pi-pencil" text rounded aria-label="Изменить" @click="openEdit(data)" />
                            <ConfirmDeleteButton :message="`Удалить тип оплаты «${data.name}»?`" @confirm="onDelete(data)" />
                        </template>
                    </Column>
                    <template #empty><div class="top-empty">Типов оплат нет</div></template>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="dialogVisible" :header="form.id ? 'Тип оплаты' : 'Новый тип оплаты'" modal :style="{ width: '560px' }">
            <div class="top-form">
                <div class="top-field">
                    <label>Название <span class="top-req">*</span></label>
                    <InputText v-model="form.name" placeholder="Например: Перевод на карту" />
                </div>
                <div class="top-field">
                    <label>Отдельно номер карты</label>
                    <InputText v-model="form.card" placeholder="Реквизиты карты (опционально)" />
                </div>
                <div class="top-field">
                    <label>Сорт</label>
                    <InputNumber v-model="form.sort" :use-grouping="false" show-buttons :min="0" />
                    <small class="top-hint">Порядок сортировки в списке способов оплаты</small>
                </div>
                <div class="top-field">
                    <label>Продавец живых билетов</label>
                    <Select v-model="form.user_external_id" :options="sellerFormOptions" option-label="label" option-value="id" placeholder="— не выбрано —" filter show-clear />
                </div>
                <div class="top-field">
                    <label>Тип билета</label>
                    <Select v-model="form.ticket_type_id" :options="ticketTypeFormOptions" option-label="label" option-value="id" placeholder="— не выбрано —" filter show-clear />
                    <small class="top-hint">Для какого типа билета доступен этот способ оплаты</small>
                </div>
                <div class="top-field">
                    <label>Шаблон письма (email)</label>
                    <InputText v-model="form.email" placeholder="Имя blade-шаблона письма (опционально)" />
                    <small class="top-hint">Имя шаблона из Backend/resources/views/email</small>
                </div>
                <div class="top-check">
                    <Checkbox v-model="form.active" :binary="true" input-id="top-active" />
                    <label for="top-active">Активен</label>
                </div>
                <div class="top-check">
                    <Checkbox v-model="form.is_billing" :binary="true" input-id="top-billing" />
                    <label for="top-billing">Биллинг (заказ извне с авто-подтверждением)</label>
                </div>
                <small v-if="formError" class="top-error">{{ formError }}</small>
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
.top-view {
    padding: 1.5rem;
    max-width: 1280px;
    margin: 0 auto;
}
.top-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.top-header h1 {
    margin: 0;
    font-size: 1.6rem;
}
.top-subtitle {
    margin: 0.25rem 0 0;
    color: var(--p-text-muted-color, #6b7280);
    max-width: 680px;
}
.top-empty {
    text-align: center;
    padding: 1.5rem;
    color: var(--p-text-muted-color, #9ca3af);
}
.top-form {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    padding-top: 0.5rem;
}
.top-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.top-field label {
    font-size: 0.8rem;
    font-weight: 600;
}
.top-req {
    color: var(--p-red-500, #ef4444);
}
.top-hint {
    color: var(--p-text-muted-color, #9ca3af);
    font-size: 0.75rem;
}
.top-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.top-error {
    color: var(--p-red-500, #ef4444);
}
</style>
