<script setup>
import { ref, computed, onMounted } from 'vue';
import { useStore } from 'vuex';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Timeline from 'primevue/timeline';
import Card from 'primevue/card';

const store = useStore();

const typeOrderOptions = [
    { label: 'Обычный', value: 'regular' },
    { label: 'Friendly', value: 'friendly' },
    { label: 'Список', value: 'list' },
    { label: 'Живой', value: 'live' }
];

const emptyFilter = () => ({ status: '', type_order: '', festival_id: '', email: '', city: '' });
const filter = ref(emptyFilter());

// Состояние пагинации/сортировки DataTable (server-side).
const first = ref(0);
const rows = ref(20);
const sortField = ref('created_at');
const sortOrder = ref(-1);

const detailsVisible = ref(false);

// Геттеры модуля.
const list = computed(() => store.getters['appQrOrder/getList']);
const item = computed(() => store.getters['appQrOrder/getItem']);
const history = computed(() => store.getters['appQrOrder/getHistory']);
const pagination = computed(() => store.getters['appQrOrder/getPagination']);
const isLoading = computed(() => store.getters['appQrOrder/getIsLoading']);

const festivals = computed(() => store.getters['appFestivalTickets/getFestivalList'] || []);
const festivalOptions = computed(() => festivals.value.map((f) => ({ label: `${f.name}${f.year ? ' ' + f.year : ''}`, value: f.id })));

const orderData = computed(() => item.value?.payload?.order_data || {});
const guests = computed(() => item.value?.payload?.guests || []);

function buildOrderBy() {
    return sortField.value ? { [sortField.value]: sortOrder.value > 0 ? 'asc' : 'desc' } : {};
}

function reload() {
    const page = Math.floor(first.value / rows.value) + 1;
    store.dispatch('appQrOrder/loadList', {
        page,
        perPage: rows.value,
        orderBy: buildOrderBy(),
        filter: { ...filter.value }
    });
}

function onLazy(event) {
    first.value = event.first ?? 0;
    rows.value = event.rows ?? rows.value;
    if (event.sortField) {
        sortField.value = event.sortField;
        sortOrder.value = event.sortOrder;
    }
    reload();
}

function applyFilter() {
    first.value = 0;
    reload();
}

function resetFilter() {
    filter.value = emptyFilter();
    first.value = 0;
    reload();
}

function openDetails(row) {
    detailsVisible.value = true;
    store.dispatch('appQrOrder/loadItem', { id: row.id });
    store.dispatch('appQrOrder/loadHistory', { id: row.id });
}

// Резолв имени фестиваля по id из загруженного списка.
function festivalName(id) {
    if (!id) return '—';
    const found = festivals.value.find((f) => f.id === id);
    return found ? `${found.name}${found.year ? ' ' + found.year : ''}` : id;
}

function typeOrderLabel(value) {
    return typeOrderOptions.find((o) => o.value === value)?.label || value || '—';
}

function statusSeverity(status) {
    const s = (status || '').toLowerCase();
    if (['оплачен', 'paid', 'выдан', 'issued'].includes(s)) return 'success';
    if (['создан', 'new'].includes(s)) return 'info';
    if (['отменён', 'отменен', 'cancel', 'canceled'].includes(s)) return 'danger';
    return 'warn';
}

function historyEventLabel(name) {
    return { created: 'Создан', status_changed: 'Смена статуса', issued: 'Билеты выданы' }[name] || name;
}

function formatPrice(value) {
    return `${Number(value || 0).toLocaleString('ru-RU')} ₽`;
}

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
    store.dispatch('appFestivalTickets/getListFestival');
    reload();
});
</script>

<template>
    <div class="qr-orders-view">
        <div class="qr-orders-header">
            <h1>QR-заказы</h1>
            <p class="qr-orders-subtitle">Заказы от витрины qr.spaceofjoy.ru — просмотр (read-only)</p>
        </div>

        <!-- Фильтры -->
        <Card class="qr-filter-card">
            <template #content>
                <div class="qr-filter-grid">
                    <div class="qr-field">
                        <label>Статус</label>
                        <InputText v-model="filter.status" placeholder="Напр. оплачен" />
                    </div>
                    <div class="qr-field">
                        <label>Тип заказа</label>
                        <Select v-model="filter.type_order" :options="typeOrderOptions" option-label="label" option-value="value" placeholder="Все" show-clear />
                    </div>
                    <div class="qr-field">
                        <label>Фестиваль</label>
                        <Select v-model="filter.festival_id" :options="festivalOptions" option-label="label" option-value="value" placeholder="Все" filter show-clear />
                    </div>
                    <div class="qr-field">
                        <label>Email</label>
                        <InputText v-model="filter.email" placeholder="Часть email" />
                    </div>
                    <div class="qr-field">
                        <label>Город</label>
                        <InputText v-model="filter.city" placeholder="Часть города" />
                    </div>
                    <div class="qr-field qr-field-actions">
                        <Button label="Применить" icon="pi pi-search" @click="applyFilter" />
                        <Button label="Сбросить" icon="pi pi-times" severity="secondary" outlined @click="resetFilter" />
                    </div>
                </div>
            </template>
        </Card>

        <!-- Таблица -->
        <Card class="qr-table-card">
            <template #content>
                <DataTable
                    :value="list"
                    :loading="isLoading"
                    lazy
                    paginator
                    :first="first"
                    :rows="rows"
                    :total-records="pagination.total"
                    :rows-per-page-options="[10, 20, 50, 100]"
                    :sort-field="sortField"
                    :sort-order="sortOrder"
                    data-key="id"
                    striped-rows
                    paginator-template="FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink RowsPerPageDropdown"
                    current-page-report-template="{first}–{last} из {totalRecords}"
                    @page="onLazy"
                    @sort="onLazy"
                >
                    <template #empty>
                        <div class="qr-empty qr-empty--illustrated">
                            <img src="/img/brand/sputnik.webp" alt="" aria-hidden="true" class="qr-empty-illustration" />
                            <span>Заказы не найдены</span>
                        </div>
                    </template>

                    <Column field="created_at" header="Создан" sortable>
                        <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                    </Column>
                    <Column field="email" header="Email" />
                    <Column field="status" header="Статус">
                        <template #body="{ data }">
                            <Tag :value="data.status" :severity="statusSeverity(data.status)" />
                        </template>
                    </Column>
                    <Column field="type_order" header="Тип">
                        <template #body="{ data }">{{ typeOrderLabel(data.type_order) }}</template>
                    </Column>
                    <Column field="festival_id" header="Фестиваль">
                        <template #body="{ data }">{{ festivalName(data.festival_id) }}</template>
                    </Column>
                    <Column field="city" header="Город" />
                    <Column field="total_price" header="Сумма" sortable>
                        <template #body="{ data }">{{ formatPrice(data.total_price) }}</template>
                    </Column>
                    <Column header="" :style="{ width: '6rem' }">
                        <template #body="{ data }">
                            <Button label="Детали" icon="pi pi-eye" size="small" text @click="openDetails(data)" />
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <!-- Детали заказа + история -->
        <Dialog v-model:visible="detailsVisible" modal :header="'Заказ ' + (item.id || '')" :style="{ width: '60rem' }" :breakpoints="{ '960px': '95vw' }">
            <div class="qr-details">
                <div class="qr-details-grid">
                    <div>
                        <span class="qr-label">Статус</span>
                        <Tag :value="item.status" :severity="statusSeverity(item.status)" />
                    </div>
                    <div><span class="qr-label">Тип</span> {{ typeOrderLabel(item.type_order) }}</div>
                    <div><span class="qr-label">Фестиваль</span> {{ orderData.festival?.title || festivalName(item.festival_id) }}</div>
                    <div><span class="qr-label">Email</span> {{ item.email }}</div>
                    <div><span class="qr-label">Телефон</span> {{ item.phone || '—' }}</div>
                    <div><span class="qr-label">Город</span> {{ item.city || '—' }}</div>
                    <div><span class="qr-label">Оплата</span> {{ orderData.types_of_payment?.title || '—' }}</div>
                    <div><span class="qr-label">Сумма</span> {{ formatPrice(item.total_price) }}</div>
                    <div><span class="qr-label">Создан</span> {{ formatDate(item.created_at) }}</div>
                    <div><span class="qr-label">Выдан</span> {{ item.issued_at ? formatDate(item.issued_at) : '—' }}</div>
                </div>

                <!-- Поля заказа-списка (curator/location/project) -->
                <div v-if="orderData.curator || orderData.location || orderData.project" class="qr-details-grid qr-list-block">
                    <div v-if="orderData.curator"><span class="qr-label">Куратор</span> {{ orderData.curator.name }} ({{ orderData.curator.email }})</div>
                    <div v-if="orderData.location"><span class="qr-label">Локация</span> {{ orderData.location.name }}</div>
                    <div v-if="orderData.project"><span class="qr-label">Проект</span> {{ orderData.project }}</div>
                </div>

                <div v-if="orderData.comment" class="qr-comment"><span class="qr-label">Комментарий</span> {{ orderData.comment }}</div>

                <!-- Гости -->
                <h3 class="qr-section-title">Гости ({{ guests.length }})</h3>
                <DataTable :value="guests" striped-rows class="qr-guests">
                    <template #empty><div class="qr-empty">Нет гостей</div></template>
                    <Column field="name" header="Имя" />
                    <Column field="email" header="Email" />
                    <Column header="Telegram">
                        <template #body="{ data }">{{ data.telegram || '—' }}</template>
                    </Column>
                    <Column header="Номер">
                        <template #body="{ data }">{{ data.number ?? '—' }}</template>
                    </Column>
                    <Column header="Билет">
                        <template #body="{ data }">{{ data.type_ticket?.title || '—' }}</template>
                    </Column>
                </DataTable>

                <!-- История -->
                <h3 class="qr-section-title">История</h3>
                <Timeline :value="history" class="qr-timeline">
                    <template #content="{ item: ev }">
                        <div class="qr-history-event">
                            <strong>{{ historyEventLabel(ev.event_name) }}</strong>
                            <span v-if="ev.payload?.from || ev.payload?.to" class="qr-history-transition"> {{ ev.payload.from || '—' }} → {{ ev.payload.to || '—' }} </span>
                            <span v-else-if="ev.payload?.status" class="qr-history-transition">{{ ev.payload.status }}</span>
                        </div>
                        <small class="qr-history-meta">{{ formatDate(ev.occurred_at) }} · {{ ev.actor_type }}</small>
                    </template>
                </Timeline>
            </div>
        </Dialog>
    </div>
</template>

<style scoped>
.qr-orders-view {
    padding: 1.5rem;
    max-width: 1280px;
    margin: 0 auto;
}

.qr-orders-header h1 {
    margin: 0;
    font-size: 1.6rem;
}

.qr-orders-subtitle {
    margin: 0.25rem 0 1.25rem;
    color: var(--p-text-muted-color, #6b7280);
}

.qr-filter-card {
    margin-bottom: 1.25rem;
}

.qr-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    align-items: end;
}

.qr-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.qr-field label {
    font-size: 0.8rem;
    font-weight: 600;
}

.qr-field-actions {
    flex-direction: row;
    gap: 0.5rem;
}

.qr-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.6rem 1.25rem;
    margin-bottom: 1rem;
}

.qr-list-block {
    border-top: 1px solid var(--p-content-border-color, #e5e7eb);
    padding-top: 0.75rem;
}

.qr-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--p-text-muted-color, #6b7280);
}

.qr-comment {
    margin-bottom: 1rem;
}

.qr-section-title {
    margin: 1.25rem 0 0.5rem;
    font-size: 1.05rem;
}

.qr-history-event {
    display: flex;
    gap: 0.5rem;
    align-items: baseline;
    flex-wrap: wrap;
}

.qr-history-transition {
    color: var(--p-text-muted-color, #6b7280);
}

.qr-history-meta {
    color: var(--p-text-muted-color, #9ca3af);
}

.qr-empty {
    text-align: center;
    padding: 1rem;
    color: var(--p-text-muted-color, #9ca3af);
}

/* Пустое состояние с брендовым акцентом (спутник) — деликатно, монохромно. */
.qr-empty--illustrated {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 2.5rem 1rem;
}

.qr-empty-illustration {
    width: 56px;
    height: auto;
    opacity: 0.35;
    filter: grayscale(1);
}

:global(.app-dark) .qr-empty-illustration {
    opacity: 0.45;
}
</style>
