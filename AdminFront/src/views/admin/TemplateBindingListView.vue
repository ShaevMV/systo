<script setup>
import { ref, computed, onMounted } from 'vue';
import { useStore } from 'vuex';

import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';

const store = useStore();

// Справочники приводим к массиву — защита от не-массивных ответов API (иначе спред/.map падает).
const arr = (getter) => {
    const v = store.getters[getter];
    return Array.isArray(v) ? v : [];
};
const list = computed(() => arr('appTemplateBinding/getList'));
const festivals = computed(() => arr('appTemplateBinding/getFestivals'));
const ticketTypes = computed(() => arr('appTemplateBinding/getTicketTypes'));
const emailTemplates = computed(() => arr('appTemplateBinding/getEmailTemplates'));
const pdfTemplates = computed(() => arr('appTemplateBinding/getPdfTemplates'));
const isLoading = computed(() => store.getters['appTemplateBinding/getIsLoading']);

const ORDER_TYPES = [
    { label: 'Любой', value: '' },
    { label: 'Обычный', value: 'regular' },
    { label: 'Friendly', value: 'friendly' },
    { label: 'Список', value: 'list' },
    { label: 'Живой', value: 'live' }
];

const dialogVisible = ref(false);
const saving = ref(false);
const errorMsg = ref('');
const blank = () => ({ id: null, festival_id: '', order_type: '', ticket_type_id: '', email_template_id: '', pdf_template_id: '', is_default: false, active: true });
const form = ref(blank());

// Отображение id → читаемое имя (из загруженных справочников).
const festivalName = (id) => (id ? festivals.value.find((f) => f.id === id)?.name || '—' : 'Любой');
const ticketTypeName = (id) => (id ? ticketTypes.value.find((t) => t.id === id)?.name || '—' : 'Любой');
const templateTitle = (id) => {
    const t = [...emailTemplates.value, ...pdfTemplates.value].find((x) => x.id === id);
    return t ? t.title || t.slug : '—';
};
const orderTypeLabel = (v) => ORDER_TYPES.find((o) => o.value === (v || ''))?.label || v;

// Опции селектов (с пунктом «Любой/—» = пустое значение).
const festivalOptions = computed(() => [{ name: 'Любой', id: '' }, ...festivals.value]);
const ticketTypeOptions = computed(() => [{ name: 'Любой', id: '' }, ...ticketTypes.value]);
const emailOptions = computed(() => [{ title: '— нет —', id: '' }, ...emailTemplates.value.map((t) => ({ id: t.id, title: t.title || t.slug }))]);
const pdfOptions = computed(() => [{ title: '— нет —', id: '' }, ...pdfTemplates.value.map((t) => ({ id: t.id, title: t.title || t.slug }))]);

function openCreate() {
    form.value = blank();
    errorMsg.value = '';
    dialogVisible.value = true;
}

function openEdit(row) {
    form.value = {
        id: row.id,
        festival_id: row.festival_id || '',
        order_type: row.order_type || '',
        ticket_type_id: row.ticket_type_id || '',
        email_template_id: row.email_template_id || '',
        pdf_template_id: row.pdf_template_id || '',
        is_default: !!row.is_default,
        active: row.active === undefined ? true : !!row.active
    };
    errorMsg.value = '';
    dialogVisible.value = true;
}

async function save() {
    saving.value = true;
    errorMsg.value = '';
    const data = {
        festival_id: form.value.festival_id || null,
        order_type: form.value.order_type || null,
        ticket_type_id: form.value.ticket_type_id || null,
        email_template_id: form.value.email_template_id || null,
        pdf_template_id: form.value.pdf_template_id || null,
        is_default: form.value.is_default,
        active: form.value.active
    };
    try {
        const action = form.value.id ? 'appTemplateBinding/edit' : 'appTemplateBinding/create';
        const r = await store.dispatch(action, { id: form.value.id, data });
        if (r && r.success === false) {
            errorMsg.value = r.message || 'Ошибка';
            return;
        }
        dialogVisible.value = false;
        await store.dispatch('appTemplateBinding/loadList');
    } catch (e) {
        errorMsg.value = e.response?.data?.message || 'Ошибка сохранения';
    } finally {
        saving.value = false;
    }
}

async function remove(row) {
    if (!window.confirm('Удалить привязку?')) return;
    await store.dispatch('appTemplateBinding/remove', { id: row.id });
    await store.dispatch('appTemplateBinding/loadList');
}

onMounted(() => {
    store.dispatch('appTemplateBinding/loadList');
    store.dispatch('appTemplateBinding/loadRefs');
});
</script>

<template>
    <div class="tb-view">
        <div class="tb-header">
            <div>
                <h1>Привязки шаблонов</h1>
                <p class="tb-subtitle">Какой шаблон письма и PDF-билета использовать для типа заказа и типа билета. Без подходящей привязки — поведение по умолчанию (как раньше).</p>
            </div>
            <Button label="Создать" icon="pi pi-plus" @click="openCreate" />
        </div>

        <Card>
            <template #content>
                <DataTable :value="list" :loading="isLoading" data-key="id" responsive-layout="scroll">
                    <Column header="Фестиваль"><template #body="{ data }">{{ festivalName(data.festival_id) }}</template></Column>
                    <Column header="Тип заказа"><template #body="{ data }">{{ orderTypeLabel(data.order_type) }}</template></Column>
                    <Column header="Тип билета"><template #body="{ data }">{{ ticketTypeName(data.ticket_type_id) }}</template></Column>
                    <Column header="Письмо"><template #body="{ data }">{{ data.email_template_id ? templateTitle(data.email_template_id) : '—' }}</template></Column>
                    <Column header="PDF"><template #body="{ data }">{{ data.pdf_template_id ? templateTitle(data.pdf_template_id) : '—' }}</template></Column>
                    <Column header="Дефолт"><template #body="{ data }"><Tag v-if="data.is_default" value="по умолчанию" severity="info" /></template></Column>
                    <Column header="Активна"><template #body="{ data }"><Tag :value="data.active ? 'да' : 'нет'" :severity="data.active ? 'success' : 'secondary'" /></template></Column>
                    <Column header="">
                        <template #body="{ data }">
                            <Button icon="pi pi-pencil" text rounded aria-label="Изменить" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" text rounded severity="danger" aria-label="Удалить" @click="remove(data)" />
                        </template>
                    </Column>
                    <template #empty><div class="tb-empty">Привязок нет — используется поведение по умолчанию</div></template>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="dialogVisible" :header="form.id ? 'Привязка' : 'Новая привязка'" modal :style="{ width: '480px' }">
            <div class="tb-form">
                <div class="tb-field">
                    <label>Фестиваль</label>
                    <Select v-model="form.festival_id" :options="festivalOptions" option-label="name" option-value="id" placeholder="Любой" />
                </div>
                <div class="tb-field">
                    <label>Тип заказа</label>
                    <Select v-model="form.order_type" :options="ORDER_TYPES" option-label="label" option-value="value" />
                </div>
                <div class="tb-field">
                    <label>Тип билета</label>
                    <Select v-model="form.ticket_type_id" :options="ticketTypeOptions" option-label="name" option-value="id" placeholder="Любой" filter />
                </div>
                <div class="tb-field">
                    <label>Шаблон письма</label>
                    <Select v-model="form.email_template_id" :options="emailOptions" option-label="title" option-value="id" placeholder="— нет —" filter />
                </div>
                <div class="tb-field">
                    <label>Шаблон PDF-билета</label>
                    <Select v-model="form.pdf_template_id" :options="pdfOptions" option-label="title" option-value="id" placeholder="— нет —" filter />
                </div>
                <div class="tb-check"><Checkbox v-model="form.is_default" :binary="true" input-id="isdef" /><label for="isdef">По умолчанию (fallback)</label></div>
                <div class="tb-check"><Checkbox v-model="form.active" :binary="true" input-id="isact" /><label for="isact">Активна</label></div>
                <small v-if="errorMsg" class="tb-error">{{ errorMsg }}</small>
            </div>
            <template #footer>
                <Button label="Отмена" text @click="dialogVisible = false" />
                <Button label="Сохранить" icon="pi pi-check" :loading="saving" @click="save" />
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
.tb-view {
    padding: 1.5rem;
    max-width: 1280px;
    margin: 0 auto;
}
.tb-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.tb-header h1 {
    margin: 0;
    font-size: 1.6rem;
}
.tb-subtitle {
    margin: 0.25rem 0 0;
    color: var(--p-text-muted-color, #6b7280);
    max-width: 680px;
}
.tb-empty {
    text-align: center;
    padding: 1.5rem;
    color: var(--p-text-muted-color, #9ca3af);
}
.tb-form {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    padding-top: 0.5rem;
}
.tb-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.tb-field label {
    font-size: 0.8rem;
    font-weight: 600;
}
.tb-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.tb-error {
    color: var(--p-red-500, #ef4444);
}
</style>
