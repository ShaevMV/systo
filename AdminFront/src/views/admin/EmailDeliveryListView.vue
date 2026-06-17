<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useStore } from 'vuex';

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

const arr = (getter) => {
    const v = store.getters[getter];
    return Array.isArray(v) ? v : [];
};
const list = computed(() => arr('appEmailDelivery/getList'));
const item = computed(() => store.getters['appEmailDelivery/getItem'] || {});
const history = computed(() => arr('appEmailDelivery/getHistory'));
const pagination = computed(() => store.getters['appEmailDelivery/getPagination']);
const isLoading = computed(() => store.getters['appEmailDelivery/getIsLoading']);

// Каталог событий (метки) — зеркало EmailEvent::LABEL на бэке.
const EVENT_LABELS = {
    order_created: 'Заказ создан',
    order_paid: 'Заказ оплачен',
    order_paid_friendly: 'Заказ оплачен (Friendly)',
    order_paid_live: 'Живой билет оплачен',
    order_cancel: 'Заказ отменён',
    order_changed: 'Данные заказа изменены',
    order_difficulties: 'Трудности с заказом',
    order_live_issued: 'Живой билет выдан',
    list_approved: 'Список одобрен',
    list_cancel: 'Список отменён',
    list_difficulties: 'Трудности со списком',
    user_registered: 'Регистрация пользователя',
    password_reset: 'Сброс пароля',
    invite: 'Приглашение',
    questionnaire: 'Анкета гостя'
};

// Статус → метка + severity Tag.
const STATUS_META = {
    queued: { label: 'В очереди', severity: 'secondary' },
    sending: { label: 'Отправляется', severity: 'info' },
    sent: { label: 'Отправлено на SMTP', severity: 'info' },
    delivered: { label: 'Доставлено', severity: 'success' },
    opened: { label: 'Прочитано', severity: 'success' },
    failed: { label: 'Ошибка', severity: 'danger' },
    bounced: { label: 'Отскок', severity: 'danger' }
};

const SOURCE_LABELS = { qr_pipeline: 'qr · выдача', qr_intake: 'qr · уведомление', org_event: 'org' };

const STATUS_OPTIONS = [{ label: 'Любой', value: '' }, ...Object.entries(STATUS_META).map(([value, m]) => ({ label: m.label, value }))];
const EVENT_OPTIONS = [{ label: 'Любое', value: '' }, ...Object.entries(EVENT_LABELS).map(([value, label]) => ({ label, value }))];
const SOURCE_OPTIONS = [{ label: 'Любой', value: '' }, ...Object.entries(SOURCE_LABELS).map(([value, label]) => ({ label, value }))];

const filters = reactive({ status: '', event: '', recipient: '', source: '' });

const eventLabel = (v) => EVENT_LABELS[v] || v || '—';
const statusMeta = (v) => STATUS_META[v] || { label: v || '—', severity: 'secondary' };
const sourceLabel = (v) => SOURCE_LABELS[v] || v || '—';
const formatDate = (v) => (v ? new Date(v).toLocaleString('ru-RU') : '—');
const historyLabel = (name) => statusMeta(String(name).replace('email_', '')).label;

const dialogVisible = ref(false);
const resending = ref(false);

function load(extra = {}) {
    const clean = {};
    Object.entries(filters).forEach(([k, v]) => {
        if (v !== '' && v !== null && v !== undefined) clean[k] = v;
    });
    store.dispatch('appEmailDelivery/loadList', { filter: clean, page: 1, ...extra });
}

function onPage(event) {
    store.dispatch('appEmailDelivery/loadList', { page: event.page + 1, perPage: event.rows });
}

function resetFilters() {
    filters.status = '';
    filters.event = '';
    filters.recipient = '';
    filters.source = '';
    load();
}

async function openDetail(row) {
    await store.dispatch('appEmailDelivery/loadItem', { id: row.id });
    dialogVisible.value = true;
}

async function doResend() {
    if (!item.value?.id) return;
    resending.value = true;
    try {
        await store.dispatch('appEmailDelivery/resend', { id: item.value.id });
        await store.dispatch('appEmailDelivery/loadItem', { id: item.value.id });
        await store.dispatch('appEmailDelivery/loadList', {});
    } finally {
        resending.value = false;
    }
}

onMounted(() => load());
</script>

<template>
    <div class="ed-view">
        <div class="ed-header">
            <div>
                <h1>Доставка писем</h1>
                <p class="ed-subtitle">
                    Весь путь письма: в очереди → отправлено на SMTP → (доставлено/прочитано). Где письмо застряло — видно по статусу и тексту ошибки.
                    Можно отправить повторно.
                </p>
            </div>
        </div>

        <Card class="ed-filters">
            <template #content>
                <div class="ed-filter-row">
                    <Select v-model="filters.status" :options="STATUS_OPTIONS" option-label="label" option-value="value" placeholder="Статус" />
                    <Select v-model="filters.event" :options="EVENT_OPTIONS" option-label="label" option-value="value" placeholder="Событие" filter />
                    <Select v-model="filters.source" :options="SOURCE_OPTIONS" option-label="label" option-value="value" placeholder="Источник" />
                    <InputText v-model="filters.recipient" placeholder="Email получателя" @keyup.enter="load()" />
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
                    <Column header="Создано"><template #body="{ data }">{{ formatDate(data.created_at) }}</template></Column>
                    <Column header="Получатель" field="recipient" />
                    <Column header="Событие"><template #body="{ data }">{{ eventLabel(data.event) }}</template></Column>
                    <Column header="Статус">
                        <template #body="{ data }"><Tag :value="statusMeta(data.status).label" :severity="statusMeta(data.status).severity" /></template>
                    </Column>
                    <Column header="Источник"><template #body="{ data }">{{ sourceLabel(data.source) }}</template></Column>
                    <Column header="Попыток" field="attempts" />
                    <Column header="Отправлено"><template #body="{ data }">{{ formatDate(data.sent_at) }}</template></Column>
                    <Column header="Ошибка">
                        <template #body="{ data }"><span class="ed-error" :title="data.error || ''">{{ data.error || '—' }}</span></template>
                    </Column>
                    <Column header="">
                        <template #body="{ data }">
                            <Button icon="pi pi-eye" text rounded aria-label="Детали" @click="openDetail(data)" />
                        </template>
                    </Column>
                    <template #empty><div class="ed-empty">Писем нет</div></template>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="dialogVisible" header="Письмо" modal :style="{ width: '52rem' }" :breakpoints="{ '960px': '90vw' }">
            <div v-if="item.id" class="ed-detail">
                <div class="ed-grid">
                    <div><span class="ed-key">Статус</span><Tag :value="statusMeta(item.status).label" :severity="statusMeta(item.status).severity" /></div>
                    <div><span class="ed-key">Событие</span>{{ eventLabel(item.event) }}</div>
                    <div><span class="ed-key">Получатель</span>{{ item.recipient }}</div>
                    <div><span class="ed-key">Источник</span>{{ sourceLabel(item.source) }}</div>
                    <div><span class="ed-key">Шаблон</span>{{ item.template_slug || '—' }}</div>
                    <div><span class="ed-key">Попыток</span>{{ item.attempts }}</div>
                    <div><span class="ed-key">Создано</span>{{ formatDate(item.created_at) }}</div>
                    <div><span class="ed-key">Отправлено</span>{{ formatDate(item.sent_at) }}</div>
                    <div><span class="ed-key">Прочитано</span>{{ formatDate(item.opened_at) }}</div>
                    <div v-if="item.aggregate_id"><span class="ed-key">Заказ</span>{{ item.aggregate_id }}</div>
                </div>

                <div v-if="item.error" class="ed-error-box"><b>Где застряло:</b> {{ item.error }}</div>

                <h3 class="ed-th">Путь письма</h3>
                <Timeline :value="history" class="ed-timeline">
                    <template #content="{ item: ev }">
                        <div class="ed-tl-name">{{ historyLabel(ev.event_name) }}</div>
                        <div class="ed-tl-meta">{{ formatDate(ev.occurred_at) }} · {{ ev.actor_type }}</div>
                    </template>
                </Timeline>
            </div>
            <template #footer>
                <Button label="Закрыть" text @click="dialogVisible = false" />
                <Button label="Отправить повторно" icon="pi pi-refresh" :loading="resending" @click="doResend" />
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
.ed-view {
    padding: 1.5rem;
    max-width: 1320px;
    margin: 0 auto;
}
.ed-header h1 {
    margin: 0;
    font-size: 1.6rem;
}
.ed-subtitle {
    margin: 0.25rem 0 1rem;
    color: var(--p-text-muted-color, #6b7280);
    max-width: 760px;
}
.ed-filters {
    margin-bottom: 1rem;
}
.ed-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
    align-items: center;
}
.ed-empty {
    text-align: center;
    padding: 1.5rem;
    color: var(--p-text-muted-color, #9ca3af);
}
.ed-error {
    display: inline-block;
    max-width: 18rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--p-red-500, #ef4444);
    vertical-align: bottom;
}
.ed-detail {
    padding-top: 0.5rem;
}
.ed-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.6rem 1.5rem;
}
.ed-grid > div {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}
.ed-key {
    display: inline-block;
    min-width: 7.5rem;
    font-weight: 600;
    color: var(--p-text-muted-color, #6b7280);
}
.ed-error-box {
    margin-top: 1rem;
    padding: 0.75rem;
    border-radius: 6px;
    background: var(--p-red-50, #fef2f2);
    color: var(--p-red-700, #b91c1c);
}
.ed-th {
    margin: 1.25rem 0 0.5rem;
    font-size: 1.05rem;
}
.ed-tl-name {
    font-weight: 600;
}
.ed-tl-meta {
    font-size: 0.8rem;
    color: var(--p-text-muted-color, #6b7280);
}
</style>
