<script setup>
import { ref, computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';

import Card from 'primevue/card';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const store = useStore();
const router = useRouter();

const typeOrderLabels = { regular: 'Обычный', friendly: 'Friendly', list: 'Список', live: 'Живой' };

// Застрявшие доставки билетов в baza (AF-4) — число failed по выбранному фестивалю.
const stuckTickets = computed(() => store.getters['appBazaDelivery/getStuck'] || 0);

function goToBazaDelivery() {
    router.push('/admin/baza-delivery');
}

const periodOptions = [
    { label: 'Всё время', value: 'all' },
    { label: 'Последние 30 дней', value: '30' },
    { label: 'Последние 7 дней', value: '7' }
];

const filter = ref({ festival_id: '', period: 'all' });

// Геттеры дашборда.
const stats = computed(() => store.getters['appDashboard/getStats'] || {});
const isLoading = computed(() => store.getters['appDashboard/getIsLoading']);
const totals = computed(() => stats.value.totals || { orders: 0, revenue: 0 });
const byStatus = computed(() => stats.value.byStatus || []);
const byType = computed(() => stats.value.byType || []);
const timeseries = computed(() => stats.value.timeseries || []);

const avgCheck = computed(() => (totals.value.orders > 0 ? Math.round(totals.value.revenue / totals.value.orders) : 0));

const festivals = computed(() => store.getters['appFestivalTickets/getFestivalList'] || []);
const festivalOptions = computed(() => festivals.value.map((f) => ({ label: `${f.name}${f.year ? ' ' + f.year : ''}`, value: f.id })));

// Брендовая палитра (Solar Systo — тёплые акценты).
const palette = ['#f59e0b', '#6366f1', '#10b981', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899'];

const revenueChartData = computed(() => ({
    labels: timeseries.value.map((p) => formatDay(p.date)),
    datasets: [
        {
            label: 'Выручка, ₽',
            data: timeseries.value.map((p) => p.revenue),
            backgroundColor: '#f59e0b',
            borderRadius: 6,
            maxBarThickness: 42
        }
    ]
}));

const revenueChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, ticks: { callback: (v) => Number(v).toLocaleString('ru-RU') } }
    }
};

const typeChartData = computed(() => ({
    labels: byType.value.map((r) => typeOrderLabel(r.type_order)),
    datasets: [
        {
            data: byType.value.map((r) => r.orders),
            backgroundColor: byType.value.map((_, i) => palette[i % palette.length])
        }
    ]
}));

const typeChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' } }
};

function buildFilter() {
    const out = {};
    if (filter.value.festival_id) out.festival_id = filter.value.festival_id;
    if (filter.value.period && filter.value.period !== 'all') {
        const days = Number(filter.value.period);
        const from = new Date();
        from.setDate(from.getDate() - days);
        out.date_from = from.toISOString().slice(0, 10);
    }
    return out;
}

function reload() {
    store.dispatch('appDashboard/loadStats', { filter: buildFilter() });
    store.dispatch('appBazaDelivery/loadStats', { festival_id: filter.value.festival_id });
}

function resetFilter() {
    filter.value = { festival_id: '', period: 'all' };
    reload();
}

function typeOrderLabel(value) {
    return typeOrderLabels[value] || value || '—';
}

function statusSeverity(status) {
    const s = (status || '').toLowerCase();
    if (['оплачен', 'paid', 'выдан', 'issued'].includes(s)) return 'success';
    if (['создан', 'new'].includes(s)) return 'info';
    if (['отменён', 'отменен', 'cancel', 'canceled'].includes(s)) return 'danger';
    return 'warn';
}

function formatPrice(value) {
    return `${Number(value || 0).toLocaleString('ru-RU')} ₽`;
}

function formatNumber(value) {
    return Number(value || 0).toLocaleString('ru-RU');
}

function formatDay(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' });
}

onMounted(() => {
    store.dispatch('appFestivalTickets/getListFestival');
    reload();
});
</script>

<template>
    <div class="dash-view">
        <div class="dash-header">
            <h1>Дашборд</h1>
            <p class="dash-subtitle">Сводка по заказам витрины qr.spaceofjoy.ru — продажи и выручка (read-only)</p>
        </div>

        <!-- Фильтры -->
        <Card class="dash-filter-card">
            <template #content>
                <div class="dash-filter-grid">
                    <div class="dash-field">
                        <label>Фестиваль</label>
                        <Select v-model="filter.festival_id" :options="festivalOptions" option-label="label" option-value="value" placeholder="Все" filter show-clear />
                    </div>
                    <div class="dash-field">
                        <label>Период</label>
                        <Select v-model="filter.period" :options="periodOptions" option-label="label" option-value="value" />
                    </div>
                    <div class="dash-field dash-field-actions">
                        <Button label="Применить" icon="pi pi-search" :loading="isLoading" @click="reload" />
                        <Button label="Сбросить" icon="pi pi-times" severity="secondary" outlined @click="resetFilter" />
                    </div>
                </div>
            </template>
        </Card>

        <!-- Карточки итогов -->
        <div class="dash-kpis">
            <Card class="dash-kpi">
                <template #content>
                    <span class="dash-kpi-label"><i class="pi pi-shopping-cart"></i> Заказы</span>
                    <span class="dash-kpi-value">{{ formatNumber(totals.orders) }}</span>
                </template>
            </Card>
            <Card class="dash-kpi">
                <template #content>
                    <span class="dash-kpi-label"><i class="pi pi-wallet"></i> Выручка</span>
                    <span class="dash-kpi-value">{{ formatPrice(totals.revenue) }}</span>
                </template>
            </Card>
            <Card class="dash-kpi">
                <template #content>
                    <span class="dash-kpi-label"><i class="pi pi-chart-line"></i> Средний чек</span>
                    <span class="dash-kpi-value">{{ formatPrice(avgCheck) }}</span>
                </template>
            </Card>
            <Card class="dash-kpi dash-kpi-clickable" :class="{ 'dash-kpi-alert': stuckTickets > 0 }" @click="goToBazaDelivery">
                <template #content>
                    <span class="dash-kpi-label"><i class="pi pi-server"></i> Застряли в baza</span>
                    <span class="dash-kpi-value">{{ formatNumber(stuckTickets) }}</span>
                </template>
            </Card>
        </div>

        <!-- Графики -->
        <div class="dash-charts">
            <Card class="dash-chart-card dash-chart-wide">
                <template #title>Выручка по дням</template>
                <template #content>
                    <div class="dash-chart-box">
                        <Chart type="bar" :data="revenueChartData" :options="revenueChartOptions" class="dash-chart" />
                    </div>
                    <div v-if="!timeseries.length" class="dash-empty">Нет данных за выбранный период</div>
                </template>
            </Card>
            <Card class="dash-chart-card">
                <template #title>Заказы по типам</template>
                <template #content>
                    <div class="dash-chart-box">
                        <Chart type="doughnut" :data="typeChartData" :options="typeChartOptions" class="dash-chart" />
                    </div>
                    <div v-if="!byType.length" class="dash-empty">Нет данных</div>
                </template>
            </Card>
        </div>

        <!-- Таблицы разрезов -->
        <div class="dash-tables">
            <Card class="dash-table-card">
                <template #title>По статусам</template>
                <template #content>
                    <DataTable :value="byStatus" :loading="isLoading" striped-rows data-key="status">
                        <template #empty><div class="dash-empty">Нет данных</div></template>
                        <Column field="status" header="Статус">
                            <template #body="{ data }"><Tag :value="data.status" :severity="statusSeverity(data.status)" /></template>
                        </Column>
                        <Column field="orders" header="Заказы">
                            <template #body="{ data }">{{ formatNumber(data.orders) }}</template>
                        </Column>
                        <Column field="revenue" header="Выручка">
                            <template #body="{ data }">{{ formatPrice(data.revenue) }}</template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
            <Card class="dash-table-card">
                <template #title>По типам заказов</template>
                <template #content>
                    <DataTable :value="byType" :loading="isLoading" striped-rows data-key="type_order">
                        <template #empty><div class="dash-empty">Нет данных</div></template>
                        <Column field="type_order" header="Тип">
                            <template #body="{ data }">{{ typeOrderLabel(data.type_order) }}</template>
                        </Column>
                        <Column field="orders" header="Заказы">
                            <template #body="{ data }">{{ formatNumber(data.orders) }}</template>
                        </Column>
                        <Column field="revenue" header="Выручка">
                            <template #body="{ data }">{{ formatPrice(data.revenue) }}</template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>

<style scoped>
.dash-view {
    padding: 1.5rem;
    max-width: 1280px;
    margin: 0 auto;
    min-width: 0;
}

.dash-header h1 {
    margin: 0;
    font-size: 1.6rem;
}

.dash-subtitle {
    margin: 0.25rem 0 1.25rem;
    color: var(--p-text-muted-color, #6b7280);
}

.dash-filter-card {
    margin-bottom: 1.25rem;
}

.dash-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}

.dash-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.dash-field label {
    font-size: 0.8rem;
    font-weight: 600;
}

.dash-field-actions {
    flex-direction: row;
    gap: 0.5rem;
}

/* KPI-карточки */
.dash-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.dash-kpi :deep(.p-card-body) {
    padding: 1.1rem 1.25rem;
}

.dash-kpi-label {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--p-text-muted-color, #6b7280);
}

.dash-kpi-value {
    display: block;
    margin-top: 0.35rem;
    font-size: 1.7rem;
    font-weight: 700;
    line-height: 1.1;
}

.dash-kpi-clickable {
    cursor: pointer;
    transition: box-shadow 0.15s ease;
}

.dash-kpi-clickable:hover {
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.dash-kpi-alert .dash-kpi-value {
    color: var(--p-red-500, #ef4444);
}

/* Графики */
.dash-charts {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.dash-chart-box {
    position: relative;
    height: 320px;
}

.dash-chart {
    height: 320px;
}

/* Таблицы */
.dash-tables {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.dash-table-card :deep(.p-datatable) {
    font-size: 0.92rem;
}

.dash-empty {
    text-align: center;
    padding: 1rem;
    color: var(--p-text-muted-color, #9ca3af);
}

/* Планшет/мобайл — всё в одну колонку */
@media (max-width: 991px) {
    .dash-charts,
    .dash-tables {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767px) {
    .dash-view {
        padding: 0.75rem 0;
    }

    .dash-header h1 {
        font-size: 1.35rem;
    }

    .dash-filter-card :deep(.p-card-body),
    .dash-kpi :deep(.p-card-body) {
        padding: 0.85rem;
    }

    .dash-field-actions {
        flex-direction: column;
    }

    .dash-field-actions :deep(.p-button) {
        width: 100%;
    }
}
</style>
