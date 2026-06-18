<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useStore } from 'vuex';
import axios from 'axios';

import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

import FilterBar from '@/components/FilterBar.vue';
import OrderHistoryDialog from '@/components/OrderHistoryDialog.vue';
import OrderStatusDialog from '@/components/OrderStatusDialog.vue';
import { useOrders, orderStatusSeverity } from '@/composables/useOrders';

// Дефолтный фестиваль (как в старом фронте). Для заказов-списков festivalId — обязателен.
const DEFAULT_FESTIVAL_ID = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';

// Заказы-списки. Бэкенд: POST /api/v1/order/getListsList (role admin,manager).
const { list, loading, saving, error, item, history, loadingItem, loadingHistory, loadList, loadItem, loadHistory, changeStatus } = useOrders('/getListsList');
const store = useStore();
const toast = useToast();

const festivals = computed(() => store.getters['appFestivalTickets/getFestivalList'] || []);
// Локации (сцены) — фильтруются по выбранному фестивалю.
const locations = ref([]);

const STATUS_OPTIONS = [
    { label: 'Любой', value: '' },
    { label: 'Список ожидает проверки', value: 'new_list' },
    { label: 'Список одобрен', value: 'approve_list' },
    { label: 'Список отменён', value: 'cancel_list' },
    { label: 'Возникли трудности', value: 'difficulties_arose_list' }
];

// Поля фильтра 1:1 со старым OrderLists/FilterOrder (festivalId — обязателен).
const blankFilter = () => ({
    festivalId: DEFAULT_FESTIVAL_ID,
    email: '',
    name: '',
    status: '',
    locationId: '',
    project: ''
});
const filter = ref(blankFilter());

const festivalOptions = computed(() => festivals.value.map((f) => ({ id: f.id, label: `${f.name}${f.year ? ' ' + f.year : ''}` })));
const locationOptions = computed(() => [{ id: '', label: 'Все' }, ...locations.value.map((l) => ({ id: l.id, label: l.name }))]);

function buildFilterBody() {
    return {
        festivalId: filter.value.festivalId || DEFAULT_FESTIVAL_ID,
        email: filter.value.email || null,
        name: filter.value.name || null,
        status: filter.value.status || '',
        locationId: filter.value.locationId || null,
        project: filter.value.project || null
    };
}

function applyFilter() {
    loadList(buildFilterBody());
}
function resetFilter() {
    filter.value = blankFilter();
    loadLocations();
    loadList(buildFilterBody());
}

/** Локации зависят от фестиваля — перегружаем при его смене (некритично, allSettled-аналог). */
async function loadLocations() {
    try {
        const r = await axios.post('/api/v1/location/getList', {
            filter: { festival_id: filter.value.festivalId || DEFAULT_FESTIVAL_ID, active: '1' },
            orderBy: { name: 'asc' }
        });
        locations.value = Array.isArray(r.data?.list) ? r.data.list : [];
    } catch (e) {
        locations.value = [];
    }
}

// При смене фестиваля — обновляем список локаций и сбрасываем выбранную.
watch(
    () => filter.value.festivalId,
    () => {
        filter.value.locationId = '';
        loadLocations();
    }
);

// --- Деталь ---
const detailsVisible = ref(false);
function openDetails(row) {
    detailsVisible.value = true;
    loadItem(row.id);
}
const detailOrder = computed(() => item.value || {});
const detailGuests = computed(() => detailOrder.value?.guests || []);

// --- История ---
const historyVisible = ref(false);
function openHistory(row) {
    historyVisible.value = true;
    loadHistory(row.id);
}

// --- Смена статуса ---
const statusVisible = ref(false);
const statusOrder = ref({});
function openStatus(row) {
    statusOrder.value = row;
    error.value = null;
    statusVisible.value = true;
}
async function onConfirmStatus(payload) {
    const res = await changeStatus({ id: statusOrder.value.id, ...payload });
    if (res.ok) {
        statusVisible.value = false;
        toast.add({ severity: 'success', summary: 'Статус изменён', detail: res.status?.humanStatus || '', life: 2500 });
        applyFilter();
    } else {
        toast.add({ severity: 'error', summary: 'Не удалось сменить статус', life: 3500 });
    }
}

function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
function guestsPreview(guests) {
    if (!guests || !guests.length) return '—';
    const names = guests
        .slice(0, 3)
        .map((g) => g.value)
        .join(', ');
    return names + (guests.length > 3 ? '…' : '');
}
function hasTransitions(row) {
    return row.listCorrectNextStatus && Object.keys(row.listCorrectNextStatus).length > 0;
}

onMounted(() => {
    store.dispatch('appFestivalTickets/getListFestival');
    loadLocations();
    applyFilter();
});
</script>

<template>
    <div class="ord-view">
        <Toast />
        <div class="ord-header">
            <h1>Заказы-списки</h1>
            <p class="ord-subtitle">Куратор создаёт список гостей на локацию, admin/manager одобряет. После одобрения получателю уходят PDF-билеты, гостям — анкеты.</p>
        </div>

        <FilterBar @apply="applyFilter" @reset="resetFilter">
            <div class="fb-field">
                <label>Фестиваль</label>
                <Select v-model="filter.festivalId" :options="festivalOptions" option-label="label" option-value="id" filter />
            </div>
            <div class="fb-field">
                <label>Email получателя</label>
                <InputText v-model="filter.email" placeholder="Часть email" @keyup.enter="applyFilter" />
            </div>
            <div class="fb-field">
                <label>Имя гостя</label>
                <InputText v-model="filter.name" placeholder="Часть имени" @keyup.enter="applyFilter" />
            </div>
            <div class="fb-field">
                <label>Статус</label>
                <Select v-model="filter.status" :options="STATUS_OPTIONS" option-label="label" option-value="value" />
            </div>
            <div class="fb-field">
                <label>Локация</label>
                <Select v-model="filter.locationId" :options="locationOptions" option-label="label" option-value="id" filter />
            </div>
            <div class="fb-field">
                <label>Проект</label>
                <InputText v-model="filter.project" placeholder="Часть названия проекта" @keyup.enter="applyFilter" />
            </div>
        </FilterBar>

        <Card class="ord-table-card">
            <template #content>
                <DataTable
                    :value="list"
                    :loading="loading"
                    data-key="id"
                    striped-rows
                    scrollable
                    scroll-height="flex"
                    paginator
                    :rows="20"
                    :rows-per-page-options="[20, 50, 100]"
                    class="ord-data-table"
                    paginator-template="FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink RowsPerPageDropdown"
                    current-page-report-template="{first}–{last} из {totalRecords}"
                >
                    <template #empty><div class="ord-empty">Заказы-списки не найдены</div></template>

                    <Column field="kilter" header="№" sortable :style="{ minWidth: '5rem' }" />
                    <Column field="curator_email" header="Email куратора" :style="{ minWidth: '13rem' }">
                        <template #body="{ data }">{{ data.curator_email || '—' }}</template>
                    </Column>
                    <Column field="curator_name" header="ФИО куратора" :style="{ minWidth: '11rem' }">
                        <template #body="{ data }">{{ data.curator_name || '—' }}</template>
                    </Column>
                    <Column field="email" header="Email получателя" :style="{ minWidth: '13rem' }" />
                    <Column field="location_name" header="Локация" :style="{ minWidth: '10rem' }">
                        <template #body="{ data }">{{ data.location_name || '—' }}</template>
                    </Column>
                    <Column field="project" header="Проект" :style="{ minWidth: '9rem' }">
                        <template #body="{ data }">{{ data.project || '—' }}</template>
                    </Column>
                    <Column header="Гости" :style="{ minWidth: '12rem' }">
                        <template #body="{ data }">{{ guestsPreview(data.guests) }}</template>
                    </Column>
                    <Column field="count" header="Кол-во" :style="{ minWidth: '5rem' }" />
                    <Column field="status" header="Статус" :style="{ minWidth: '11rem' }">
                        <template #body="{ data }">
                            <Tag :value="data.humanStatus || data.status" :severity="orderStatusSeverity(data.status)" />
                        </template>
                    </Column>
                    <Column header="" frozen align-frozen="right" :style="{ minWidth: '9rem' }">
                        <template #body="{ data }">
                            <Button icon="pi pi-eye" size="small" text aria-label="Детали" @click="openDetails(data)" />
                            <Button icon="pi pi-history" size="small" text aria-label="История" @click="openHistory(data)" />
                            <Button icon="pi pi-sync" size="small" text :disabled="!hasTransitions(data)" aria-label="Сменить статус" @click="openStatus(data)" />
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <!-- Детали (без цены/типа билета — у списков их нет) -->
        <Dialog v-model:visible="detailsVisible" modal :header="'Заказ-список №' + (detailOrder.kilter ?? '')" :style="{ width: '52rem' }" :breakpoints="{ '960px': '90vw', '640px': '100vw' }" class="ord-details-dialog">
            <div v-if="loadingItem" class="ord-empty">Загрузка…</div>
            <div v-else class="ord-details">
                <div class="ord-details-grid">
                    <div><span class="ord-label">Статус</span><Tag :value="detailOrder.humanStatus || detailOrder.status" :severity="orderStatusSeverity(detailOrder.status)" /></div>
                    <div><span class="ord-label">Email получателя</span> {{ detailOrder.email || '—' }}</div>
                    <div><span class="ord-label">Куратор</span> {{ detailOrder.curator_name || detailOrder.curator_email || '—' }}</div>
                    <div><span class="ord-label">Локация</span> {{ detailOrder.location_name || '—' }}</div>
                    <div><span class="ord-label">Проект</span> {{ detailOrder.project || '—' }}</div>
                    <div><span class="ord-label">Телефон</span> {{ detailOrder.phone || '—' }}</div>
                    <div><span class="ord-label">Дата</span> {{ detailOrder.dateBuy || formatDate(detailOrder.created_at) }}</div>
                </div>
                <div v-if="detailOrder.lastComment" class="ord-comment"><span class="ord-label">Комментарий</span> {{ detailOrder.lastComment }}</div>

                <h3 class="ord-section-title">Гости ({{ detailGuests.length }})</h3>
                <DataTable :value="detailGuests" striped-rows scrollable class="ord-guests" data-key="id">
                    <template #empty><div class="ord-empty">Нет гостей</div></template>
                    <Column field="value" header="Гость" :style="{ minWidth: '14rem' }" />
                    <Column field="email" header="Email" :style="{ minWidth: '12rem' }">
                        <template #body="{ data }">{{ data.email || '—' }}</template>
                    </Column>
                </DataTable>
            </div>
        </Dialog>

        <!-- История -->
        <OrderHistoryDialog v-model:visible="historyVisible" :history="history" :loading="loadingHistory" :order-title="''" />

        <!-- Смена статуса (комментарий обязателен для difficulties_arose_list) -->
        <OrderStatusDialog v-model:visible="statusVisible" :order="statusOrder" :saving="saving" :errors="error" @confirm="onConfirmStatus" />
    </div>
</template>

<style scoped>
.ord-view {
    padding: 1.5rem;
    max-width: 1280px;
    margin: 0 auto;
    min-width: 0;
}
.ord-header h1 {
    margin: 0;
    font-size: 1.6rem;
}
.ord-subtitle {
    margin: 0.25rem 0 1.25rem;
    color: var(--p-text-muted-color, #6b7280);
    max-width: 720px;
}
.ord-data-table {
    max-width: 100%;
}
.ord-data-table :deep(.p-datatable-table-container) {
    overflow-x: auto;
}
.ord-empty {
    text-align: center;
    padding: 1.25rem;
    color: var(--p-text-muted-color, #9ca3af);
}
.ord-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.6rem 1.25rem;
    margin-bottom: 1rem;
}
.ord-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--p-text-muted-color, #6b7280);
}
.ord-comment {
    margin-bottom: 1rem;
}
.ord-section-title {
    margin: 1.25rem 0 0.5rem;
    font-size: 1.05rem;
}
.ord-details-dialog :deep(.p-dialog-content) {
    overflow-x: hidden;
}

@media (max-width: 767px) {
    .ord-view {
        padding: 0.75rem 0;
    }
    .ord-header h1 {
        font-size: 1.35rem;
    }
    .ord-table-card :deep(.p-card-body) {
        padding: 0.85rem;
    }
    .ord-details-grid {
        grid-template-columns: 1fr;
    }
}
</style>
