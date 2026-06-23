<script setup>
// Экран «Фестивали» (TD-48): реестр фестивалей на КПП. Мастер каталога — основная система
// (org), здесь read-mostly + одна локальная отметка «активен для КПП» (попадает ли фестиваль
// в выбор при открытии смены). Гейтится правом festival.manage (по умолчанию — administrator).
import { ref, onMounted } from 'vue';
import { loadFestivalRegistry, setFestivalActiveForKpp } from '@/services/festivals';
import { notifySuccess } from '@/lib/notify';

const list = ref([]);
const loading = ref(true);
const busy = ref(null); // id фестиваля в процессе переключения

async function reload() {
    loading.value = true;
    try {
        list.value = await loadFestivalRegistry();
    } catch {
        /* ошибку покажет централизованный http.js-перехватчик (тост) */
    } finally {
        loading.value = false;
    }
}

async function toggle(f) {
    if (busy.value) return;
    const next = !f.active_for_kpp;
    busy.value = f.id;
    try {
        await setFestivalActiveForKpp(f.id, next);
        f.active_for_kpp = next; // меняем состояние только при успехе
        notifySuccess(next ? `«${f.name}» открыт для КПП` : `«${f.name}» скрыт с КПП`);
    } catch {
        /* http.js покажет ошибку; локальное состояние не трогаем */
    } finally {
        busy.value = null;
    }
}

onMounted(reload);
</script>

<template>
    <section class="fest">
        <h2 class="fest-title">Фестивали</h2>
        <p class="fest-note">
            <i class="pi pi-info-circle"></i>
            Список приходит из основной системы. Здесь — отметка «открыт для входа на КПП».
        </p>

        <p v-if="loading" class="fest-muted">Загрузка…</p>
        <ul v-else class="fest-list">
            <li v-for="f in list" :key="f.id" class="fest-row">
                <div class="fest-main">
                    <div class="fest-name">
                        <i class="pi pi-flag"></i> {{ f.name }}<span v-if="f.year" class="fest-year"> {{ f.year }}</span>
                    </div>
                    <div class="fest-meta">{{ f.active_for_kpp ? 'открыт для КПП' : 'скрыт с КПП' }}</div>
                </div>
                <button
                    class="fest-toggle"
                    :class="{ 'is-on': f.active_for_kpp }"
                    :disabled="busy === f.id"
                    @click="toggle(f)"
                >
                    {{ f.active_for_kpp ? 'ВКЛ' : 'выкл' }}
                </button>
            </li>
            <li v-if="!loading && list.length === 0" class="fest-muted">Фестивалей нет</li>
        </ul>
    </section>
</template>

<style scoped>
.fest { display: flex; flex-direction: column; gap: 0.75rem; }
.fest-title { margin: 0; }
.fest-note { margin: 0; color: #6b7280; font-size: 0.85rem; background: #fff; border: 1px solid #e3e6ea; border-radius: 10px; padding: 0.6rem 0.8rem; }
.fest-note .pi { color: #ff7900; margin-right: 0.2rem; }
.fest-muted { color: #8a93a0; text-align: center; padding: 1rem 0; }
.fest-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.5rem; }
.fest-row {
    display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;
    background: #fff; border: 1px solid #e3e6ea; border-radius: 12px; padding: 0.8rem 0.9rem;
}
.fest-name { font-weight: 700; }
.fest-name .pi { color: #ff7900; }
.fest-year { color: #6b7280; font-weight: 400; }
.fest-meta { color: #6b7280; font-size: 0.82rem; margin-top: 0.15rem; }
.fest-toggle {
    min-width: 64px; min-height: 44px; border: 2px solid #cbd2da; background: #f5f6f8;
    color: #6b7280; border-radius: 12px; font-weight: 700; font-size: 0.9rem;
}
.fest-toggle.is-on { border-color: #1e9e54; background: #1e9e54; color: #fff; }
.fest-toggle:disabled { opacity: 0.6; }
</style>
