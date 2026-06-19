<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import { useToast } from 'primevue/usetoast';

import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import Timeline from 'primevue/timeline';

const store = useStore();
const toast = useToast();

const arr = (getter) => {
    const v = store.getters[getter];
    return Array.isArray(v) ? v : [];
};
const list = computed(() => arr('appBazaDelivery/getList'));
const item = computed(() => store.getters['appBazaDelivery/getItem'] || {});
const history = computed(() => arr('appBazaDelivery/getHistory'));
const pagination = computed(() => store.getters['appBazaDelivery/getPagination']);
const isLoading = computed(() => store.getters['appBazaDelivery/getIsLoading']);

// Цель доставки → метка (зеркало target в baza_deliveries).
const TARGET_LABELS = {
    el_tickets: 'Обычный (el_tickets)',
    spisok_tickets: 'Список (spisok_tickets)',
    live_tickets: 'Живой (live_tickets)',
    auto: 'Авто'
};

// Статус → метка + severity Tag (зеркало BazaDeliveryStatus на бэке).
const STATUS_META = {
    queued: { label: 'В очереди', severity: 'secondary' },
    sending: { label: 'Отправляется', severity: 'info' },
    delivered: { label: 'Доставлен', severity: 'success' },
    failed: { label: 'Ошибка', severity: 'danger' }
};

const SOURCE_LABELS = { qr_pipeline: 'qr · выдача', org_event: 'org' };

const STATUS_OPTIONS = [{ label: 'Любой', value: '' }, ...Object.entries(STATUS_META).map(([value, m]) => ({ label: m.label, value }))];
const TARGET_OPTIONS = [{ label: 'Любая', value: '' }, ...Object.entries(TARGET_LABELS).map(([value, label]) => ({ label, value }))];
const SOURCE_OPTIONS = [{ label: 'Любой', value: '' }, ...Object.entries(SOURCE_LABELS).map(([value, label]) => ({ label, value }))];

const filters = reactive({ status: '', target: '', source: '', email: '' });

const targetLabel = (v) => TARGET_LABELS[v] || v || '—';
const statusMeta = (v) => STATUS_META[v] || { label: v || '—', severity: 'secondary' };
const sourceLabel = (v) => SOURCE_LABELS[v] || v || '—';
const formatDate = (v) => (v ? new Date(v).toLocaleString('ru-RU') : '—');
const historyLabel = (name) => statusMeta(String(name).replace('baza_', '')).label;

const dialogVisible = ref(false);
const resending = ref(false);

function load(extra = {}) {
    const clean = {};
    Object.entries(filters).forEach(([k, v]) => {
        if (v !== '' && v !== null && v !== undefined) clean[k] = v;
    });
    store.dispatch('appBazaDelivery/loadList', { filter: clean, page: 1, ...extra });
}

function onPage(event) {
    store.dispatch('appBazaDelivery/loadList', { page: event.page + 1, perPage: event.rows });
}

function resetFilters() {
    filters.status = '';
    filters.target = '';
    filters.source = '';
    filters.email = '';
    load();
}

async function openDetail(row) {
    await store.dispatch('appBazaDelivery/loadItem', { id: row.id });
    dialogVisible.value = true;
}

async function doResend() {
    if (!item.value?.id) return;
    resending.value = true;
    try {
        await store.dispatch('appBazaDelivery/resend', { id: item.value.id });
        await store.dispatch('appBazaDelivery/loadItem', { id: item.value.id });
        await store.dispatch('appBazaDelivery/loadList', {});
        toast.add({ severity: 'success', summary: 'Доставка поставлена на повтор', life: 2500 });
    } catch {
        toast.add({ severity: 'error', summary: 'Не удалось повторить доставку', life: 3500 });
    } finally {
        resending.value = false;
    }
}

onMounted(() => load());
</script>

<template>
    <div class="bd-view">
        <div class="bd-header">
            <div>
                <h1>Доставка в baza</h1>
                <p class="bd-subtitle">Весь путь билета в систему входа (Baza): в очереди → отправляется → доставлен. Где доставка застряла — видно по статусу и тексту ошибки. Застрявшую можно отправить повторно (до 10 попыток).</p>
            </div>
        </div>

        <Card class="bd-filters">
            <template #content>
                <div class="bd-filter-row">
                    <Select v-model="filters.status" :options="STATUS_OPTIONS" option-label="label" option-value="value" placeholder="Статус" />
                    <Select v-model="filters.target" :options="TARGET_OPTIONS" option-label="label" option-value="value" placeholder="Цель" />
                    <Select v-model="filters.source" :options="SOURCE_OPTIONS" option-label="label" option-value="value" placeholder="Источник" />
                    <InputText v-model="filters.email" placeholder="Email" @keyup.enter="load()" />
                    <Button label="Применить" icon="pi pi-search" @click="load()" />
                    <Button label="Сброс" icon="pi pi-times" severity="secondary" text @click="resetFilters" />
                </div>
            </template>
        </Card>

        <Card>
            <template #content>
                <DataTable
                    :value="list"
                    :loading="isLoading"
                    data-key="id"
                    lazy
                    paginator
                    :rows="pagination.perPage"
                    :total-records="pagination.total"
                    :first="(pagination.page - 1) * pagination.perPage"
                    :rows-per-page-options="[20, 50, 100]"
                    responsive-layout="scroll"
                    @page="onPage"
                >
                    <Column header="Создано"
                        ><template #body="{ data }">{{ formatDate(data.created_at) }}</template></Column
                    >
                    <Column header="ФИО"
                        ><template #body="{ data }">{{ data.name || '—' }}</template></Column
                    >
                    <Column header="Email" field="email" />
                    <Column header="Цель"
                        ><template #body="{ data }">{{ targetLabel(data.target) }}</template></Column
                    >
                    <Column header="Статус">
                        <template #body="{ data }"><Tag :value="statusMeta(data.status).label" :severity="statusMeta(data.status).severity" /></template>
                    </Column>
                    <Column header="Источник"
                        ><template #body="{ data }">{{ sourceLabel(data.source) }}</template></Column
                    >
                    <Column header="Попыток" field="attempts" />
                    <Column header="Доставлено"
                        ><template #body="{ data }">{{ formatDate(data.delivered_at) }}</template></Column
                    >
                    <Column header="Ошибка">
                        <template #body="{ data }"
                            ><span class="bd-error" :title="data.error || ''">{{ data.error || '—' }}</span></template
                        >
                    </Column>
                    <Column header="">
                        <template #body="{ data }">
                            <Button icon="pi pi-eye" text rounded aria-label="Детали" @click="openDetail(data)" />
                        </template>
                    </Column>
                    <template #empty><div class="bd-empty">Доставок нет</div></template>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="dialogVisible" header="Доставка в baza" modal :style="{ width: '52rem' }" :breakpoints="{ '960px': '90vw' }">
            <div v-if="item.id" class="bd-detail">
                <div class="bd-grid">
                    <div><span class="bd-key">Статус</span><Tag :value="statusMeta(item.status).label" :severity="statusMeta(item.status).severity" /></div>
                    <div><span class="bd-key">Цель</span>{{ targetLabel(item.target) }}</div>
                    <div><span class="bd-key">ФИО</span>{{ item.name || '—' }}</div>
                    <div><span class="bd-key">Email</span>{{ item.email || '—' }}</div>
                    <div v-if="item.number"><span class="bd-key">Номер</span>{{ item.number }}</div>
                    <div><span class="bd-key">Источник</span>{{ sourceLabel(item.source) }}</div>
                    <div><span class="bd-key">Попыток</span>{{ item.attempts }}</div>
                    <div><span class="bd-key">Создано</span>{{ formatDate(item.created_at) }}</div>
                    <div><span class="bd-key">Доставлено</span>{{ formatDate(item.delivered_at) }}</div>
                    <div v-if="item.order_id"><span class="bd-key">Заказ</span>{{ item.order_id }}</div>
                    <div v-if="item.ticket_id"><span class="bd-key">Билет</span>{{ item.ticket_id }}</div>
                </div>

                <div v-if="item.error" class="bd-error-box"><b>Где застряло:</b> {{ item.error }}</div>

                <h3 class="bd-th">Путь доставки</h3>
                <Timeline :value="history" class="bd-timeline">
                    <template #content="{ item: ev }">
                        <div class="bd-tl-name">{{ historyLabel(ev.event_name) }}</div>
                        <div class="bd-tl-meta">{{ formatDate(ev.occurred_at) }} · {{ ev.actor_type }}</div>
                    </template>
                </Timeline>
            </div>
            <template #footer>
                <Button label="Закрыть" text @click="dialogVisible = false" />
                <Button label="Повторить доставку" icon="pi pi-refresh" :loading="resending" @click="doResend" />
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
.bd-view {
    padding: 1.5rem;
    max-width: 1320px;
    margin: 0 auto;
}
.bd-header h1 {
    margin: 0;
    font-size: 1.6rem;
}
.bd-subtitle {
    margin: 0.25rem 0 1rem;
    color: var(--p-text-muted-color, #6b7280);
    max-width: 760px;
}
.bd-filters {
    margin-bottom: 1rem;
}
.bd-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
    align-items: center;
}
.bd-empty {
    text-align: center;
    padding: 1.5rem;
    color: var(--p-text-muted-color, #9ca3af);
}
.bd-error {
    display: inline-block;
    max-width: 18rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--p-red-500, #ef4444);
    vertical-align: bottom;
}
.bd-detail {
    padding-top: 0.5rem;
}
.bd-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.6rem 1.5rem;
}
.bd-grid > div {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}
.bd-key {
    display: inline-block;
    min-width: 7.5rem;
    font-weight: 600;
    color: var(--p-text-muted-color, #6b7280);
}
.bd-error-box {
    margin-top: 1rem;
    padding: 0.75rem;
    border-radius: 6px;
    background: var(--p-red-50, #fef2f2);
    color: var(--p-red-700, #b91c1c);
}
.bd-th {
    margin: 1.25rem 0 0.5rem;
    font-size: 1.05rem;
}
.bd-tl-name {
    font-weight: 600;
}
.bd-tl-meta {
    font-size: 0.8rem;
    color: var(--p-text-muted-color, #6b7280);
}
</style>
