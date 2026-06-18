<script setup>
import Card from 'primevue/card';
import Button from 'primevue/button';

/**
 * Единая панель фильтров над таблицей справочника/списка.
 * Поля передаются слотом (каждое — `<div class="fb-field"><label/>…</div>`),
 * кнопки «Применить/Сбросить» — встроены. UX перенесён из старого фронта
 * (см. FrontEnd `*Filter.vue`), оформление консистентно во всех экранах.
 *
 *   <FilterBar @apply="applyFilter" @reset="resetFilter">
 *       <div class="fb-field"><label>Название</label><InputText v-model="filter.name" /></div>
 *   </FilterBar>
 */
const emit = defineEmits(['apply', 'reset']);
</script>

<template>
    <Card class="filter-bar">
        <template #content>
            <div class="fb-row">
                <slot />
                <div class="fb-actions">
                    <Button label="Применить" icon="pi pi-filter" size="small" @click="emit('apply')" />
                    <Button label="Сбросить" icon="pi pi-filter-slash" severity="secondary" text size="small" @click="emit('reset')" />
                </div>
            </div>
        </template>
    </Card>
</template>

<style scoped>
.filter-bar {
    margin-bottom: 1rem;
}
.fb-row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 0.75rem;
}
.fb-actions {
    display: flex;
    gap: 0.5rem;
    margin-left: auto;
}
/* Стилизуем поля-слоты (slotted-контент — потомок FilterBar, :deep достаёт). */
.fb-row :deep(.fb-field) {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-width: 180px;
}
.fb-row :deep(.fb-field label) {
    font-size: 0.8rem;
    font-weight: 600;
}
</style>
