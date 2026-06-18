<script setup>
import Dialog from 'primevue/dialog';
import Timeline from 'primevue/timeline';
import ProgressSpinner from 'primevue/progressspinner';

import { orderHistoryEventLabel } from '@/composables/useOrders';

/**
 * Диалог истории заказа (Timeline) — единый для трёх экранов заказов.
 * История приходит admin-эндпоинтом `GET /api/v1/order/getHistory/{id}`:
 * [{ event_name, payload:{fromStatus,toStatus,...}, actor_type, occurred_at }].
 *
 * v-model:visible управляет показом; данные/загрузка передаются пропами,
 * чтобы диалог оставался «глупым» (загрузку ведёт useOrders в родителе).
 */
defineProps({
    visible: { type: Boolean, default: false },
    history: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    orderTitle: { type: String, default: '' }
});
const emit = defineEmits(['update:visible']);

function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

// Переход статуса в payload может называться по-разному (старое/новое именование).
function transition(payload) {
    if (!payload) return '';
    const from = payload.fromStatus ?? payload.from ?? null;
    const to = payload.toStatus ?? payload.to ?? null;
    if (from || to) return `${from || '—'} → ${to || '—'}`;
    if (payload.status) return payload.status;
    return '';
}
</script>

<template>
    <Dialog :visible="visible" modal :header="'История заказа ' + (orderTitle || '')" :style="{ width: '46rem' }" :breakpoints="{ '960px': '90vw', '640px': '100vw' }" class="oh-dialog" @update:visible="emit('update:visible', $event)">
        <div v-if="loading" class="oh-loading">
            <ProgressSpinner style="width: 40px; height: 40px" stroke-width="4" />
        </div>
        <div v-else-if="!history.length" class="oh-empty">История пуста</div>
        <Timeline v-else :value="history" class="oh-timeline">
            <template #content="{ item: ev }">
                <div class="oh-event">
                    <strong>{{ orderHistoryEventLabel(ev.event_name) }}</strong>
                    <span v-if="transition(ev.payload)" class="oh-transition">{{ transition(ev.payload) }}</span>
                </div>
                <small class="oh-meta">{{ formatDate(ev.occurred_at) }} · {{ ev.actor_type || '—' }}</small>
                <div v-if="ev.payload && ev.payload.comment" class="oh-comment">{{ ev.payload.comment }}</div>
            </template>
        </Timeline>
    </Dialog>
</template>

<style scoped>
.oh-loading {
    display: flex;
    justify-content: center;
    padding: 1.5rem;
}
.oh-empty {
    text-align: center;
    padding: 1.5rem;
    color: var(--p-text-muted-color, #9ca3af);
}
.oh-event {
    display: flex;
    gap: 0.5rem;
    align-items: baseline;
    flex-wrap: wrap;
}
.oh-transition {
    color: var(--p-text-muted-color, #6b7280);
}
.oh-meta {
    color: var(--p-text-muted-color, #9ca3af);
}
.oh-comment {
    margin-top: 0.25rem;
    font-style: italic;
    color: var(--p-text-color, #374151);
}
</style>
