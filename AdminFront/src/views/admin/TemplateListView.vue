<script setup>
import { ref, computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Card from 'primevue/card';

const store = useStore();
const router = useRouter();

const kindOptions = [
    { label: 'Все', value: '' },
    { label: 'Письма', value: 'email' },
    { label: 'PDF-билеты', value: 'pdf' }
];

const filter = ref({ kind: '' });

const list = computed(() => store.getters['appTemplate/getList']);
const isLoading = computed(() => store.getters['appTemplate/getIsLoading']);

function reload() {
    store.dispatch('appTemplate/loadList', { filter: { kind: filter.value.kind } });
}

function kindLabel(value) {
    return value === 'pdf' ? 'PDF-билет' : 'Письмо';
}

function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function openEditor(row) {
    router.push('/admin/templates/' + row.id);
}

function createNew() {
    router.push('/admin/templates/new');
}

onMounted(reload);
</script>

<template>
    <div class="tpl-view">
        <div class="tpl-header">
            <div>
                <h1>Шаблоны</h1>
                <p class="tpl-subtitle">Письма и PDF-билеты — редактирование без деплоя</p>
            </div>
            <Button label="Создать" icon="pi pi-plus" @click="createNew" />
        </div>

        <Card class="tpl-filter-card">
            <template #content>
                <div class="tpl-filter">
                    <div class="tpl-field">
                        <label>Тип</label>
                        <Select v-model="filter.kind" :options="kindOptions" option-label="label" option-value="value" @change="reload" />
                    </div>
                </div>
            </template>
        </Card>

        <Card>
            <template #content>
                <DataTable :value="list" :loading="isLoading" striped-rows data-key="id" scrollable scroll-height="flex" class="tpl-table">
                    <template #empty><div class="tpl-empty">Шаблоны не найдены</div></template>

                    <Column field="title" header="Название" sortable :style="{ minWidth: '14rem' }" />
                    <Column field="kind" header="Тип" :style="{ minWidth: '8rem' }">
                        <template #body="{ data }">
                            <Tag :value="kindLabel(data.kind)" :severity="data.kind === 'pdf' ? 'warn' : 'info'" />
                        </template>
                    </Column>
                    <Column field="engine" header="Движок" :style="{ minWidth: '6rem' }" />
                    <Column field="slug" header="Slug (привязка)" :style="{ minWidth: '12rem' }">
                        <template #body="{ data }"><code class="tpl-slug">{{ data.slug }}</code></template>
                    </Column>
                    <Column field="active" header="Статус" :style="{ minWidth: '8rem' }">
                        <template #body="{ data }">
                            <Tag :value="data.active ? 'активен' : 'черновик'" :severity="data.active ? 'success' : 'secondary'" />
                        </template>
                    </Column>
                    <Column field="updated_at" header="Изменён" :style="{ minWidth: '10rem' }">
                        <template #body="{ data }">{{ formatDate(data.updated_at) }}</template>
                    </Column>
                    <Column header="" frozen align-frozen="right" :style="{ minWidth: '7rem' }">
                        <template #body="{ data }">
                            <Button label="Открыть" icon="pi pi-pencil" size="small" text @click="openEditor(data)" />
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>
    </div>
</template>

<style scoped>
.tpl-view {
    padding: 1.5rem;
    max-width: 1280px;
    margin: 0 auto;
    min-width: 0;
}

.tpl-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.tpl-header h1 {
    margin: 0;
    font-size: 1.6rem;
}

.tpl-subtitle {
    margin: 0.25rem 0 0;
    color: var(--p-text-muted-color, #6b7280);
}

.tpl-filter-card {
    margin-bottom: 1.25rem;
}

.tpl-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    max-width: 220px;
}

.tpl-field label {
    font-size: 0.8rem;
    font-weight: 600;
}

.tpl-table {
    max-width: 100%;
}

.tpl-table :deep(.p-datatable-table-container) {
    overflow-x: auto;
}

.tpl-slug {
    font-size: 0.85rem;
    background: var(--p-content-hover-background, #f1f5f9);
    padding: 0.1rem 0.4rem;
    border-radius: 4px;
}

.tpl-empty {
    text-align: center;
    padding: 1.5rem;
    color: var(--p-text-muted-color, #9ca3af);
}

@media (max-width: 767px) {
    .tpl-view {
        padding: 0.75rem 0;
    }
    .tpl-header {
        flex-direction: column;
    }
    .tpl-header h1 {
        font-size: 1.35rem;
    }
}
</style>
