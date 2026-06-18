<script setup>
import { useConfirm } from 'primevue/useconfirm';
import Button from 'primevue/button';

/**
 * Кнопка-корзина с подтверждением удаления (PrimeVue ConfirmPopup).
 * Требует один `<ConfirmPopup />` где-то в дереве экрана (ConfirmationService
 * подключён глобально в `main.js`). На подтверждение эмитит `confirm`.
 *
 * Использование:
 *   <ConfirmDeleteButton message="Удалить локацию?" @confirm="onDelete(row)" />
 *   ...и один <ConfirmPopup /> на экране.
 */
const props = defineProps({
    message: { type: String, default: 'Удалить запись? Действие необратимо.' }
});
const emit = defineEmits(['confirm']);
const confirm = useConfirm();

function ask(event) {
    confirm.require({
        target: event.currentTarget,
        message: props.message,
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Удалить',
        rejectLabel: 'Отмена',
        acceptProps: { severity: 'danger', size: 'small' },
        rejectProps: { text: true, size: 'small' },
        accept: () => emit('confirm')
    });
}
</script>

<template>
    <Button icon="pi pi-trash" text rounded severity="danger" aria-label="Удалить" @click="ask" />
</template>
