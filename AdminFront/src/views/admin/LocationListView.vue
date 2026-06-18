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
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import ConfirmPopup from 'primevue/confirmpopup';
import { useToast } from 'primevue/usetoast';

import { useCrud } from '@/composables/useCrud';
import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.vue';

// Локации (сцены) — справочник для заказов-списков. Бэкенд: /api/v1/location/* (admin).
const { list, loading, saving, error, loadList, save, remove } = useCrud('/api/v1/location');
const toast = useToast();

// Справочники для селектов формы.
const festivals = ref([]);
const qTypes = ref([]);
const emailBlades = ref([]);
const pdfBlades = ref([]);

const festivalName = (id) => {
    const f = festivals.value.find((x) => x.id === id);
    return f ? `${f.name}${f.year ? ' ' + f.year : ''}` : '—';
};
const qTypeName = (id) => (id ? qTypes.value.find((x) => x.id === id)?.name || '—' : '—');

const dialogVisible = ref(false);
const blank = () => ({
    id: null,
    name: '',
    description: '',
    festival_id: '',
    questionnaire_type_id: '',
    email_template: '',
    pdf_template: '',
    active: true
});
const form = ref(blank());
const formError = computed(() => error.value);

function openCreate() {
    form.value = blank();
    error.value = null;
    dialogVisible.value = true;
}

function openEdit(row) {
    form.value = {
        id: row.id,
        name: row.name ?? '',
        description: row.description ?? '',
        festival_id: row.festival_id ?? '',
        questionnaire_type_id: row.questionnaire_type_id ?? '',
        email_template: row.email_template ?? '',
        pdf_template: row.pdf_template ?? '',
        active: row.active === undefined ? true : !!row.active
    };
    error.value = null;
    dialogVisible.value = true;
}

async function submit() {
    if (!form.value.name?.trim() || !form.value.festival_id) {
        error.value = 'Название и фестиваль обязательны';
        return;
    }
    const data = {
        name: form.value.name.trim(),
        description: form.value.description || null,
        festival_id: form.value.festival_id || null,
        questionnaire_type_id: form.value.questionnaire_type_id || null,
        email_template: form.value.email_template || null,
        pdf_template: form.value.pdf_template || null,
        active: form.value.active
    };
    const res = await save(data, form.value.id);
    if (res.ok) {
        dialogVisible.value = false;
        toast.add({ severity: 'success', summary: form.value.id ? 'Локация отредактирована' : 'Локация создана', life: 2500 });
        await loadList();
    }
}

async function onDelete(row) {
    const ok = await remove(row.id);
    if (ok) {
        toast.add({ severity: 'success', summary: 'Локация удалена', life: 2500 });
        await loadList();
    } else {
        toast.add({ severity: 'error', summary: error.value || 'Не удалось удалить', life: 3500 });
    }
}

/** Справочники некритичны для сохранения — при ошибке селекты остаются пустыми. */
async function loadRefs() {
    const [f, q, b] = await Promise.allSettled([axios.get('/api/v1/festival/getFestivalList'), axios.post('/api/v1/questionnaireType/getList', { filter: { active: '1' }, orderBy: {} }), axios.get('/api/v1/ticketType/getBlade')]);
    festivals.value = f.status === 'fulfilled' ? (f.value.data?.festivalDto ?? []) : [];
    qTypes.value = q.status === 'fulfilled' ? (q.value.data?.list ?? []) : [];
    if (b.status === 'fulfilled') {
        emailBlades.value = b.value.data?.list?.email ?? [];
        pdfBlades.value = b.value.data?.list?.pdf ?? [];
    }
}

// Опции селектов с пустым пунктом-дефолтом.
const festivalOptions = computed(() => festivals.value.map((f) => ({ id: f.id, label: `${f.name}${f.year ? ' ' + f.year : ''}` })));
const qTypeOptions = computed(() => [{ id: '', name: '— без анкеты —' }, ...qTypes.value]);
const emailOptions = computed(() => ['', ...emailBlades.value]);
const pdfOptions = computed(() => ['', ...pdfBlades.value]);

onMounted(() => {
    loadList();
    loadRefs();
});
</script>

<template>
    <div class="loc-view">
        <div class="loc-header">
            <div>
                <h1>Локации</h1>
                <p class="loc-subtitle">Сцены/площадки фестиваля для заказов-списков (куратор создаёт список на локацию). Тип анкеты и шаблоны — для гостей этой локации.</p>
            </div>
            <Button label="Создать" icon="pi pi-plus" @click="openCreate" />
        </div>

        <Card>
            <template #content>
                <DataTable :value="list" :loading="loading" data-key="id" responsive-layout="scroll" paginator :rows="20" :rows-per-page-options="[20, 50, 100]">
                    <Column header="Название" field="name" sortable />
                    <Column header="Фестиваль"
                        ><template #body="{ data }">{{ festivalName(data.festival_id) }}</template></Column
                    >
                    <Column header="Тип анкеты"
                        ><template #body="{ data }">{{ qTypeName(data.questionnaire_type_id) }}</template></Column
                    >
                    <Column header="Письмо"
                        ><template #body="{ data }">{{ data.email_template || '—' }}</template></Column
                    >
                    <Column header="PDF"
                        ><template #body="{ data }">{{ data.pdf_template || '—' }}</template></Column
                    >
                    <Column header="Активна"
                        ><template #body="{ data }"><Tag :value="data.active ? 'да' : 'нет'" :severity="data.active ? 'success' : 'secondary'" /></template
                    ></Column>
                    <Column header="">
                        <template #body="{ data }">
                            <Button icon="pi pi-pencil" text rounded aria-label="Изменить" @click="openEdit(data)" />
                            <ConfirmDeleteButton :message="`Удалить локацию «${data.name}»?`" @confirm="onDelete(data)" />
                        </template>
                    </Column>
                    <template #empty><div class="loc-empty">Локаций нет</div></template>
                </DataTable>
            </template>
        </Card>

        <Dialog v-model:visible="dialogVisible" :header="form.id ? 'Локация' : 'Новая локация'" modal :style="{ width: '520px' }">
            <div class="loc-form">
                <div class="loc-field">
                    <label>Название <span class="loc-req">*</span></label>
                    <InputText v-model="form.name" placeholder="Например: Главная сцена" />
                </div>
                <div class="loc-field">
                    <label>Описание</label>
                    <Textarea v-model="form.description" rows="3" auto-resize placeholder="Краткое описание локации" />
                </div>
                <div class="loc-field">
                    <label>Фестиваль <span class="loc-req">*</span></label>
                    <Select v-model="form.festival_id" :options="festivalOptions" option-label="label" option-value="id" placeholder="— выберите фестиваль —" filter />
                </div>
                <div class="loc-field">
                    <label>Тип анкеты</label>
                    <Select v-model="form.questionnaire_type_id" :options="qTypeOptions" option-label="name" option-value="id" placeholder="— без анкеты —" filter />
                    <small class="loc-hint">Шаблон анкеты, который заполняют гости списка</small>
                </div>
                <div class="loc-field">
                    <label>Шаблон письма (blade)</label>
                    <Select v-model="form.email_template" :options="emailOptions" placeholder="— по умолчанию (orderListApproved) —" filter show-clear />
                </div>
                <div class="loc-field">
                    <label>Шаблон билета (blade)</label>
                    <Select v-model="form.pdf_template" :options="pdfOptions" placeholder="— по умолчанию (pdf) —" filter show-clear />
                </div>
                <div class="loc-check">
                    <Checkbox v-model="form.active" :binary="true" input-id="loc-active" />
                    <label for="loc-active">Активна (видна в форме создания списка)</label>
                </div>
                <small v-if="formError" class="loc-error">{{ formError }}</small>
            </div>
            <template #footer>
                <Button label="Отмена" text @click="dialogVisible = false" />
                <Button label="Сохранить" icon="pi pi-check" :loading="saving" @click="submit" />
            </template>
        </Dialog>

        <ConfirmPopup />
    </div>
</template>

<style scoped>
.loc-view {
    padding: 1.5rem;
    max-width: 1280px;
    margin: 0 auto;
}
.loc-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.loc-header h1 {
    margin: 0;
    font-size: 1.6rem;
}
.loc-subtitle {
    margin: 0.25rem 0 0;
    color: var(--p-text-muted-color, #6b7280);
    max-width: 680px;
}
.loc-empty {
    text-align: center;
    padding: 1.5rem;
    color: var(--p-text-muted-color, #9ca3af);
}
.loc-form {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    padding-top: 0.5rem;
}
.loc-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}
.loc-field label {
    font-size: 0.8rem;
    font-weight: 600;
}
.loc-req {
    color: var(--p-red-500, #ef4444);
}
.loc-hint {
    color: var(--p-text-muted-color, #9ca3af);
    font-size: 0.75rem;
}
.loc-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.loc-error {
    color: var(--p-red-500, #ef4444);
}
</style>
