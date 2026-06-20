<script setup>
// PR-1: экран-заглушка. Камера/сканер (QrScanner, локальный) + офлайн-сверка по снимку
// IndexedDB + «Пропустить» (/api/enter) — приходят в PR-4 (см. .claude/specs/baza-f5-pwa.md).
</script>

<template>
    <section class="scan-stub">
        <div class="scan-frame">
            <i class="pi pi-qrcode"></i>
            <p>Сканер появится здесь</p>
            <small>камера + офлайн-сверка билета → «Пропустить» (PR-4)</small>
        </div>
        <router-link class="big-btn" :to="{ name: 'search' }">
            <i class="pi pi-search"></i> Ввести № вручную
        </router-link>
    </section>
</template>

<style scoped>
.scan-stub { display: flex; flex-direction: column; gap: 1rem; height: 100%; }
.scan-frame {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border: 2px dashed #cbd2da;
    border-radius: 12px;
    color: #8a93a0;
    min-height: 40vh;
}
.scan-frame .pi { font-size: 3rem; }
.scan-frame small { color: #aab; text-align: center; padding: 0 1rem; }
.big-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    min-height: 60px;
    background: #ff7900;
    color: #fff;
    border-radius: 12px;
    text-decoration: none;
    font-size: 1.1rem;
    font-weight: 600;
}
</style>
