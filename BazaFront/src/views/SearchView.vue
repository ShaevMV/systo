<script setup>
import { ref } from 'vue';

// PR-1: заглушка. Поиск без QR (/search → ticket_search) + офлайн по снимку + «Пропустить» — PR-5.
const q = ref('');
</script>

<template>
    <section class="search-stub">
        <input v-model="q" class="search-input" type="search" inputmode="search" placeholder="ФИО / телефон / № билета / госномер" />
        <div class="hint">
            <i class="pi pi-info-circle"></i>
            Поиск без QR (по всем полям) появится здесь — PR-5.
        </div>
    </section>
</template>

<style scoped>
.search-stub { display: flex; flex-direction: column; gap: 1rem; }
.search-input {
    width: 100%;
    min-height: 56px;
    padding: 0 1rem;
    font-size: 1.1rem;
    border: 1px solid #cbd2da;
    border-radius: 12px;
}
.hint { color: #8a93a0; display: flex; align-items: center; gap: 0.5rem; }
</style>
