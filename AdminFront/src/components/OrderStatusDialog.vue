<script setup>
import { ref, computed, watch } from 'vue';

import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Message from 'primevue/message';

/**
 * Диалог смены статуса заказа — единый для трёх экранов.
 *
 * Допустимые следующие статусы берём ИЗ САМОГО заказа:
 * `order.listCorrectNextStatus` = { statusKey: 'Человеко-читаемая метка' }.
 * Матрицу переходов НЕ хардкодим (бэкенд считает её в Status::getListNextStatus()).
 *
 * Спец-поля по выбранному статусу:
 *  - difficulties_arose / difficulties_arose_list → обязателен комментарий;
 *  - live_ticket_issued → обязателен ввод номеров живых билетов на каждого гостя.
 *    liveList отправляем ОБЪЕКТОМ { [guest.id]: number } — бэкенд по ключу делает new Uuid().
 *
 * @emits confirm { status, comment?, liveList? }
 */
const props = defineProps({
    visible: { type: Boolean, default: false },
    // Текущий заказ (строка списка) — несёт id, kilter, status, listCorrectNextStatus, guests.
    order: { type: Object, default: () => ({}) },
    saving: { type: Boolean, default: false },
    // Ошибки валидации с бэкенда: { comment: [...], liveList: [...], role: '...' } или строка.
    errors: { type: [Object, String, Array], default: null }
});
const emit = defineEmits(['update:visible', 'confirm']);

const selectedStatus = ref(null);
const comment = ref('');
// liveNumber — объект { [guestId]: 'строка-номер' }.
const liveNumber = ref({});

// Опции селекта из самого заказа: { statusKey: label } → [{ value, label }].
const statusOptions = computed(() => {
    const map = props.order?.listCorrectNextStatus || {};
    return Object.entries(map).map(([value, label]) => ({ value, label }));
});

const guests = computed(() => props.order?.guests || []);

const needComment = computed(() => ['difficulties_arose', 'difficulties_arose_list'].includes(selectedStatus.value));
const needLiveList = computed(() => selectedStatus.value === 'live_ticket_issued');

// Сброс формы при каждом открытии/смене заказа.
watch(
    () => [props.visible, props.order?.id],
    ([vis]) => {
        if (vis) {
            selectedStatus.value = null;
            comment.value = '';
            const map = {};
            (props.order?.guests || []).forEach((g) => {
                map[g.id] = '';
            });
            liveNumber.value = map;
        }
    }
);

// Извлечь ошибку конкретного поля (errors может быть объектом массивов или строкой).
function fieldError(field) {
    const e = props.errors;
    if (!e) return '';
    if (typeof e === 'string') return field === '_' ? e : '';
    const v = e[field];
    if (Array.isArray(v)) return v[0];
    return v || '';
}

const generalError = computed(() => {
    const e = props.errors;
    if (!e) return '';
    if (typeof e === 'string') return e;
    return e.role || '';
});

const canConfirm = computed(() => {
    if (!selectedStatus.value) return false;
    if (needComment.value && !comment.value.trim()) return false;
    if (needLiveList.value) {
        // Все гости должны иметь введённый номер.
        return guests.value.length > 0 && guests.value.every((g) => String(liveNumber.value[g.id] || '').trim() !== '');
    }
    return true;
});

function confirm() {
    const payload = { status: selectedStatus.value };
    if (needComment.value) payload.comment = comment.value.trim();
    if (needLiveList.value) {
        // Отправляем как объект { guestId: numberString } — бэкенд ждёт ключ = guest.id.
        const map = {};
        Object.entries(liveNumber.value).forEach(([gid, num]) => {
            map[gid] = String(num).trim();
        });
        payload.liveList = map;
    }
    emit('confirm', payload);
}
</script>

<template>
    <Dialog :visible="visible" modal :header="'Смена статуса заказа №' + (order.kilter ?? '')" :style="{ width: '40rem' }" :breakpoints="{ '640px': '100vw' }" class="os-dialog" @update:visible="emit('update:visible', $event)">
        <div class="os-form">
            <div class="os-field">
                <label>Новый статус <span class="os-req">*</span></label>
                <Select v-model="selectedStatus" :options="statusOptions" option-label="label" option-value="value" placeholder="— выберите статус —" />
                <small v-if="statusOptions.length === 0" class="os-hint">Для этого заказа нет доступных переходов</small>
            </div>

            <!-- Комментарий (обязателен для difficulties) -->
            <div v-if="needComment" class="os-field">
                <label>Комментарий получателю <span class="os-req">*</span></label>
                <Textarea v-model="comment" rows="3" auto-resize placeholder="Опишите, что произошло — текст уйдёт в письмо" />
                <small v-if="fieldError('comment')" class="os-error">{{ fieldError('comment') }}</small>
            </div>

            <!-- Номера живых билетов (обязательно для live_ticket_issued) -->
            <div v-if="needLiveList" class="os-field">
                <label>Номера живых билетов <span class="os-req">*</span></label>
                <DataTable :value="guests" data-key="id" class="os-live-table" size="small">
                    <template #empty><div class="os-hint">Нет гостей</div></template>
                    <Column header="Гость" field="value" :style="{ minWidth: '14rem' }" />
                    <Column header="Номер билета" :style="{ minWidth: '10rem' }">
                        <template #body="{ data }">
                            <InputText v-model="liveNumber[data.id]" placeholder="Номер" class="os-live-input" />
                        </template>
                    </Column>
                </DataTable>
                <small v-if="fieldError('liveList')" class="os-error">{{ fieldError('liveList') }}</small>
            </div>

            <Message v-if="generalError" severity="error" :closable="false">{{ generalError }}</Message>
        </div>

        <template #footer>
            <Button label="Отмена" text @click="emit('update:visible', false)" />
            <Button label="Сменить статус" icon="pi pi-check" :loading="saving" :disabled="!canConfirm" @click="confirm" />
        </template>
    </Dialog>
</template>

<style scoped>
.os-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding-top: 0.5rem;
}
.os-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.os-field label {
    font-size: 0.8rem;
    font-weight: 600;
}
.os-req {
    color: var(--p-red-500, #ef4444);
}
.os-hint {
    color: var(--p-text-muted-color, #9ca3af);
    font-size: 0.75rem;
}
.os-error {
    color: var(--p-red-500, #ef4444);
    font-size: 0.78rem;
}
.os-live-input {
    width: 100%;
}
</style>
