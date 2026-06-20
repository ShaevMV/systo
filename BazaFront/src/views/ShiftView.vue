<script setup>
// PR-1: заглушка. Текущая смена/КПП, состав, счётчики прохода, дренаж очереди — позже.
// Офлайн-вход по PIN + «офлайн-пакет смены» (TTL) — PR-6 (решение security/PM).
</script>

<template>
    <section class="shift-stub">
        <div class="card">
            <h3>Смена</h3>
            <p>Текущая смена, КПП, состав и счётчики прохода появятся здесь.</p>
            <small>Офлайн-вход по PIN + статус очереди впуска — PR-6.</small>
        </div>
    </section>
</template>

<style scoped>
.shift-stub { display: flex; flex-direction: column; gap: 1rem; }
.card { background: #fff; border-radius: 12px; padding: 1rem; }
.card h3 { margin: 0 0 0.5rem; }
.card small { color: #aab; }
</style>
