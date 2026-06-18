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
import Tag from 'primevue/tag';
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

import FilterBar from '@/components/FilterBar.vue';

// Анкеты гостей — read-only список с просмотром и одобрением.
// Бэкенд (всё admin): /api/v1/questionnaire/* — см. routes/questionnaire.php.
// Это НЕ CRUD-справочник, поэтому useCrud не подходит — работаем axios напрямую.

const toast = useToast();
const confirm = useConfirm();

// --- Справочник статусов анкеты -------------------------------------------
// Бэкенд хранит строки 'NEW' / 'APPROVE' (QuestionnaireStatus). Фильтр старого
// фронта дополнительно слал in_clube/cancel/difficulties_arose — оставляем как
// опции селекта 1:1, реальная матрица — только NEW/APPROVE.
const STATUS_OPTIONS = [
    { label: 'Все', value: '' },
    { label: 'Новая', value: 'new' },
    { label: 'Одобрена', value: 'approve' },
    { label: 'В клубе', value: 'in_clube' },
    { label: 'Отменена', value: 'cancel' },
    { label: 'Возникли трудности', value: 'difficulties_arose' }
];

const IS_CLUB_OPTIONS = [
    { label: 'Все', value: '' },
    { label: 'Хочет', value: 'true' },
    { label: 'Не хочет', value: 'false' }
];

// Метка статуса для тега в таблице/деталях.
function statusLabel(status) {
    const map = { NEW: 'Новая', APPROVE: 'Одобрена' };
    return map[String(status || '').toUpperCase()] || status || '—';
}
function statusSeverity(status) {
    const s = String(status || '').toUpperCase();
    if (s === 'APPROVE') return 'success';
    if (s === 'NEW') return 'info';
    return 'secondary';
}

// --- Фильтр (поля 1:1 со старым QuestionnaireFilter) -----------------------
const blankFilter = () => ({
    email: '',
    telegram: '',
    vk: '',
    status: '',
    is_have_in_club: '',
    questionnaire_type_id: ''
});
const filter = ref(blankFilter());

// --- Список ----------------------------------------------------------------
const list = ref([]);
const loading = ref(false);

async function loadList() {
    loading.value = true;
    try {
        // Бэкенд читает плоское тело запроса (email/telegram/vk/is_have_in_club/status/questionnaire_type_id).
        const { data } = await axios.post('/api/v1/questionnaire/load', { ...filter.value });
        list.value = data?.questionnaireList ?? [];
    } catch (e) {
        list.value = [];
        toast.add({ severity: 'error', summary: 'Не удалось загрузить анкеты', detail: e?.response?.data?.message || '', life: 3500 });
    } finally {
        loading.value = false;
    }
}

function applyFilter() {
    loadList();
}
function resetFilter() {
    filter.value = blankFilter();
    loadList();
}

// --- Справочник типов анкет (для фильтра и колонки) ------------------------
const qTypes = ref([]);
const qTypeName = (id) => (id ? qTypes.value.find((t) => t.id === id)?.name || '—' : '—');
const qTypeFilterOptions = computed(() => [{ id: '', name: 'Все типы' }, ...qTypes.value]);

async function loadQTypes() {
    try {
        const { data } = await axios.post('/api/v1/questionnaireType/getList', { filter: {}, orderBy: {} });
        qTypes.value = data?.list ?? [];
    } catch {
        // Справочник некритичен — фильтр по типу просто останется без вариантов.
        qTypes.value = [];
    }
}

// --- Просмотр анкеты (Dialog) ----------------------------------------------
const detailsVisible = ref(false);
const item = ref(null);
const itemLoading = ref(false);

// Поля, которые показываем как отдельные строки (остальное уйдёт в extraData-список).
const KNOWN_FIELDS = ['agy', 'howManyTimes', 'questionForSysto', 'phone', 'status', 'is_have_in_club', 'email', 'telegram', 'vk', 'name', 'musicStyles', 'whereSysto', 'creationOfSisto', 'activeOfEvent', 'link'];
// Служебные ключи, которые не показываем вообще.
const HIDDEN_FIELDS = ['user_id', 'order_id', 'ticket_id', 'id', 'questionnaire_type_id', 'message'];

// Динамические поля анкеты (детская и пр.) — всё, что не известное и не служебное.
const extraData = computed(() => {
    if (!item.value) return [];
    return Object.entries(item.value)
        .filter(([key, value]) => !KNOWN_FIELDS.includes(key) && !HIDDEN_FIELDS.includes(key) && value !== null && value !== '' && value !== undefined)
        .map(([key, value]) => ({ key, value: formatValue(value) }));
});

function formatValue(value) {
    if (value === true) return 'да';
    if (value === false) return 'нет';
    if (value && typeof value === 'object') return JSON.stringify(value, null, 2);
    return value;
}

async function openDetails(row) {
    detailsVisible.value = true;
    item.value = null;
    itemLoading.value = true;
    try {
        const { data } = await axios.get('/api/v1/questionnaire/get/' + row.id);
        item.value = data?.questionnaire ?? null;
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Не удалось загрузить анкету', detail: e?.response?.data?.message || '', life: 3500 });
        detailsVisible.value = false;
    } finally {
        itemLoading.value = false;
    }
}

// --- Одобрение анкеты (NEW → APPROVE) --------------------------------------
const approving = ref(false);
const isNew = (status) => String(status || '').toUpperCase() === 'NEW';

function confirmApprove(row) {
    confirm.require({
        header: 'Одобрить анкету',
        message: `Одобрить анкету ${row.email || '№' + row.id}? Гостю уйдёт ссылка-приглашение.`,
        icon: 'pi pi-check-circle',
        acceptLabel: 'Одобрить',
        rejectLabel: 'Отмена',
        accept: () => approve(row)
    });
}

async function approve(row) {
    approving.value = true;
    try {
        const { data } = await axios.post('/api/v1/questionnaire/approve/' + row.id);
        toast.add({ severity: 'success', summary: data?.message || 'Анкета одобрена', life: 2500 });
        // Обновляем строку в списке и открытую деталь, если это она.
        if (item.value && item.value.id === row.id) item.value = { ...item.value, status: 'APPROVE' };
        await loadList();
    } catch (e) {
        toast.add({ severity: 'error', summary: e?.response?.data?.message || 'Не удалось одобрить анкету', life: 3500 });
    } finally {
        approving.value = false;
    }
}

// --- Повторная отправка ссылки на анкету -----------------------------------
const resending = ref(false);

async function resendLink(row) {
    if (!row.email) {
        toast.add({ severity: 'warn', summary: 'У анкеты нет email — некуда отправлять ссылку', life: 3000 });
        return;
    }
    resending.value = true;
    try {
        const { data } = await axios.post('/api/v1/questionnaire/notification/' + row.id, { email: row.email });
        toast.add({ severity: 'success', summary: data?.message || 'Ссылка на анкету отправлена', life: 2500 });
    } catch (e) {
        toast.add({ severity: 'error', summary: e?.response?.data?.message || 'Не удалось отправить ссылку', life: 3500 });
    } finally {
        resending.value = false;
    }
}

onMounted(() => {
    loadList();
    loadQTypes();
});
</script>

<template>
    <div class="qn-view">
        <div class="qn-header">
            <div>
                <h1>Анкеты</h1>
                <p class="qn-subtitle">Анкеты гостей — просмотр и одобрение. Одобрение анкеты (NEW → APPROVE) отправляет гостю ссылку-приглашение.</p>
            </div>
        </div>

        <FilterBar @apply="applyFilter" @reset="resetFilter">
            <div class="fb-field">
                <label>Email</label>
                <InputText v-model="filter.email" placeholder="Часть email" @keyup.enter="applyFilter" />
            </div>
            <div class="fb-field">
                <label>Telegram</label>
                <InputText v-model="filter.telegram" placeholder="Никнейм" @keyup.enter="applyFilter" />
            </div>
            <div class="fb-field">
                <label>VK</label>
                <InputText v-model="filter.vk" placeholder="Ссылка/ник" @keyup.enter="applyFilter" />
            </div>
            <div class="fb-field">
                <label>Статус</label>
                <Select v-model="filter.status" :options="STATUS_OPTIONS" option-label="label" option-value="value" />
            </div>
            <div class="fb-field">
                <label>Хочет в клуб</label>
                <Select v-model="filter.is_have_in_club" :options="IS_CLUB_OPTIONS" option-label="label" option-value="value" />
            </div>
            <div class="fb-field">
                <label>Тип анкеты</label>
                <Select v-model="filter.questionnaire_type_id" :options="qTypeFilterOptions" option-label="name" option-value="id" placeholder="Все типы" filter show-clear />
            </div>
        </FilterBar>

        <Card>
            <template #content>
                <DataTable :value="list" :loading="loading" data-key="id" responsive-layout="scroll" scrollable scroll-height="flex" paginator :rows="20" :rows-per-page-options="[20, 50, 100]" striped-rows class="qn-table">
                    <Column header="№" field="id" sortable :style="{ width: '5rem' }" />
                    <Column header="Email" field="email" sortable :style="{ minWidth: '13rem' }">
                        <template #body="{ data }">{{ data.email || '—' }}</template>
                    </Column>
                    <Column header="Телефон" :style="{ minWidth: '9rem' }">
                        <template #body="{ data }">{{ data.phone || '—' }}</template>
                    </Column>
                    <Column header="Telegram" :style="{ minWidth: '8rem' }">
                        <template #body="{ data }">{{ data.telegram || '—' }}</template>
                    </Column>
                    <Column header="Тип анкеты" :style="{ minWidth: '10rem' }">
                        <template #body="{ data }">{{ qTypeName(data.questionnaire_type_id) }}</template>
                    </Column>
                    <Column header="Статус" :style="{ minWidth: '8rem' }">
                        <template #body="{ data }"><Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" /></template>
                    </Column>
                    <Column header="" frozen align-frozen="right" :style="{ minWidth: '11rem' }">
                        <template #body="{ data }">
                            <Button icon="pi pi-eye" text rounded aria-label="Просмотр" @click="openDetails(data)" />
                            <Button v-if="isNew(data.status)" icon="pi pi-check" text rounded severity="success" aria-label="Одобрить" :loading="approving" @click="confirmApprove(data)" />
                            <Button v-if="data.email" icon="pi pi-send" text rounded severity="secondary" aria-label="Переслать ссылку" :loading="resending" @click="resendLink(data)" />
                        </template>
                    </Column>
                    <template #empty><div class="qn-empty">Анкет нет</div></template>
                </DataTable>
            </template>
        </Card>

        <!-- Просмотр анкеты -->
        <Dialog v-model:visible="detailsVisible" modal :header="'Анкета ' + (item?.email || (item?.id ? '№' + item.id : ''))" :style="{ width: '46rem' }" :breakpoints="{ '960px': '90vw', '640px': '100vw' }" class="qn-details-dialog">
            <div v-if="itemLoading" class="qn-empty">Загрузка…</div>
            <div v-else-if="item" class="qn-details">
                <div class="qn-details-grid">
                    <div>
                        <span class="qn-label">Статус</span>
                        <Tag :value="statusLabel(item.status)" :severity="statusSeverity(item.status)" />
                    </div>
                    <div><span class="qn-label">Email</span> {{ item.email || '—' }}</div>
                    <div><span class="qn-label">Телефон</span> {{ item.phone || '—' }}</div>
                    <div><span class="qn-label">Telegram</span> {{ item.telegram || '—' }}</div>
                    <div><span class="qn-label">VK</span> {{ item.vk || '—' }}</div>
                    <div><span class="qn-label">Имя</span> {{ item.name || '—' }}</div>
                    <div><span class="qn-label">Возраст</span> {{ item.agy ?? '—' }}</div>
                    <div><span class="qn-label">Сколько раз был на Систо</span> {{ item.howManyTimes || '—' }}</div>
                    <div><span class="qn-label">Тип анкеты</span> {{ qTypeName(item.questionnaire_type_id) }}</div>
                    <div><span class="qn-label">Хочет в клуб</span> {{ item.is_have_in_club ? 'да' : 'нет' }}</div>
                </div>

                <div v-if="item.questionForSysto || item.musicStyles || item.whereSysto || item.creationOfSisto || item.activeOfEvent" class="qn-details-text qn-block">
                    <div v-if="item.questionForSysto"><span class="qn-label">Вопрос Систо</span> {{ item.questionForSysto }}</div>
                    <div v-if="item.musicStyles"><span class="qn-label">Музыкальные стили</span> {{ item.musicStyles }}</div>
                    <div v-if="item.whereSysto"><span class="qn-label">Откуда узнал</span> {{ item.whereSysto }}</div>
                    <div v-if="item.creationOfSisto"><span class="qn-label">Что для тебя Систо</span> {{ item.creationOfSisto }}</div>
                    <div v-if="item.activeOfEvent"><span class="qn-label">Активность на событии</span> {{ item.activeOfEvent }}</div>
                </div>

                <!-- Динамические поля анкеты (детская и пр.) из JSON data -->
                <template v-if="extraData.length">
                    <h3 class="qn-section-title">Дополнительные поля</h3>
                    <DataTable :value="extraData" striped-rows class="qn-extra">
                        <Column field="key" header="Поле" :style="{ width: '40%' }" />
                        <Column field="value" header="Значение">
                            <template #body="{ data }"
                                ><span class="qn-extra-value">{{ data.value }}</span></template
                            >
                        </Column>
                    </DataTable>
                </template>

                <div v-if="item.link" class="qn-link">
                    <span class="qn-label">Ссылка на анкету</span> <a :href="item.link" target="_blank" rel="noopener">{{ item.link }}</a>
                </div>
            </div>
            <template #footer>
                <Button v-if="item && item.email" label="Переслать ссылку" icon="pi pi-send" text severity="secondary" :loading="resending" @click="resendLink(item)" />
                <Button v-if="item && isNew(item.status)" label="Одобрить" icon="pi pi-check" severity="success" :loading="approving" @click="confirmApprove(item)" />
                <Button label="Закрыть" text @click="detailsVisible = false" />
            </template>
        </Dialog>

        <ConfirmDialog />
    </div>
</template>

<style scoped>
.qn-view {
    padding: 1.5rem;
    max-width: 1280px;
    margin: 0 auto;
    min-width: 0;
}
.qn-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.qn-header h1 {
    margin: 0;
    font-size: 1.6rem;
}
.qn-subtitle {
    margin: 0.25rem 0 0;
    color: var(--p-text-muted-color, #6b7280);
    max-width: 680px;
}
.qn-table {
    max-width: 100%;
}
.qn-table :deep(.p-datatable-table-container) {
    overflow-x: auto;
}
.qn-empty {
    text-align: center;
    padding: 1.5rem;
    color: var(--p-text-muted-color, #9ca3af);
}
.qn-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.6rem 1.25rem;
    margin-bottom: 1rem;
}
.qn-details-text {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.qn-block {
    border-top: 1px solid var(--p-content-border-color, #e5e7eb);
    padding-top: 0.75rem;
}
.qn-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--p-text-muted-color, #6b7280);
}
.qn-section-title {
    margin: 1.25rem 0 0.5rem;
    font-size: 1.05rem;
}
.qn-extra-value {
    white-space: pre-wrap;
    word-break: break-word;
}
.qn-link {
    margin-top: 1rem;
    word-break: break-all;
}
.qn-details-dialog :deep(.p-dialog-content) {
    overflow-x: hidden;
}

/* Мобильная адаптация: детали в одну колонку, таблица скроллится. */
@media (max-width: 767px) {
    .qn-view {
        padding: 0.75rem 0;
    }
    .qn-header h1 {
        font-size: 1.35rem;
    }
    .qn-details-grid {
        grid-template-columns: 1fr;
    }
}
</style>
