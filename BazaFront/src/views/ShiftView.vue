<script setup>
// Экран «Смена» (Ф5, PR-6): офлайн-PIN + готовность офлайна + закрытие смены (wipe).
//   - Задать/сменить PIN (самообслуживание, реш. C10) — нужен для шифрования кэша.
//   - Счётчики: снимок / blacklist / неотправленные намерения.
//   - Заблокировать (ключ из памяти) / Закрыть смену (стереть кэш+ключ, реш. C11/wipe).
import { ref, onMounted } from 'vue';
import { hasPin, isUnlocked, setPin, lock, wipe } from '@/services/pin';
import { snapshotCount } from '@/db/snapshot';
import { blacklistCount } from '@/db/blacklist';
import { pendingCount } from '@/db/queue';
import { notifySuccess, notifyWarn } from '@/lib/notify';

const pinSet = ref(false);
const unlocked = ref(false);
const newPin = ref('');
const counts = ref({ snapshot: 0, blacklist: 0, pending: 0 });

async function refresh() {
    pinSet.value = await hasPin();
    unlocked.value = isUnlocked();
    counts.value = {
        snapshot: await snapshotCount().catch(() => 0),
        blacklist: await blacklistCount().catch(() => 0),
        pending: await pendingCount().catch(() => 0)
    };
}

async function savePin() {
    const p = newPin.value.trim();
    if (!/^\d{4,6}$/.test(p)) {
        notifyWarn('PIN — 4–6 цифр');
        return;
    }
    await setPin(p);
    newPin.value = '';
    notifySuccess('PIN сохранён. Кэш шифруется.');
    await refresh();
}

function doLock() {
    lock();
    notifySuccess('Заблокировано. Для доступа введите PIN.');
    refresh();
}

async function closeShift() {
    if (!confirm('Закрыть смену? Локальный кэш и PIN будут стёрты с устройства.')) {
        return;
    }
    await wipe();
    notifySuccess('Смена закрыта, кэш стёрт.');
    await refresh();
}

onMounted(refresh);
</script>

<template>
    <section class="shift">
        <div class="card">
            <h3>Офлайн-доступ (PIN)</h3>
            <p v-if="pinSet" class="ok"><i class="pi pi-lock"></i> PIN задан — кэш шифруется.
                <span v-if="unlocked">Разблокировано.</span>
            </p>
            <p v-else class="warn"><i class="pi pi-exclamation-triangle"></i> PIN не задан — офлайн-кэш не шифруется.</p>

            <form class="pin-form" @submit.prevent="savePin">
                <input
                    v-model="newPin"
                    class="pin-input"
                    type="password"
                    inputmode="numeric"
                    autocomplete="off"
                    :placeholder="pinSet ? 'Новый PIN (4–6 цифр)' : 'Задать PIN (4–6 цифр)'"
                />
                <button type="submit" class="pin-btn">{{ pinSet ? 'Сменить' : 'Задать' }}</button>
            </form>
        </div>

        <div class="card">
            <h3>Готовность офлайна</h3>
            <ul class="counts">
                <li><span>Снимок билетов</span><b>{{ counts.snapshot }}</b></li>
                <li><span>Отозванных (blacklist)</span><b>{{ counts.blacklist }}</b></li>
                <li><span>Намерений в очереди</span><b>{{ counts.pending }}</b></li>
            </ul>
        </div>

        <div class="card">
            <h3>Управление сменой</h3>
            <div class="actions">
                <button class="btn-lock" :disabled="!pinSet" @click="doLock">Заблокировать</button>
                <button class="btn-wipe" @click="closeShift">Закрыть смену (стереть кэш)</button>
            </div>
        </div>
    </section>
</template>

<style scoped>
.shift { display: flex; flex-direction: column; gap: 1rem; }
.card { background: #fff; border-radius: 12px; padding: 1rem; }
.card h3 { margin: 0 0 0.6rem; }
.ok { color: #1e9e54; margin: 0 0 0.75rem; }
.warn { color: #b8860b; margin: 0 0 0.75rem; }

.pin-form { display: flex; gap: 0.5rem; }
.pin-input { flex: 1; min-height: 48px; padding: 0 0.8rem; border: 1px solid #cbd2da; border-radius: 10px; font-size: 1.05rem; }
.pin-btn { min-width: 90px; border: 0; background: #ff7900; color: #fff; border-radius: 10px; font-weight: 700; }

.counts { list-style: none; margin: 0; padding: 0; }
.counts li { display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid #f0f2f4; }
.counts li:last-child { border-bottom: 0; }
.counts b { font-variant-numeric: tabular-nums; }

.actions { display: flex; flex-direction: column; gap: 0.6rem; }
.btn-lock { min-height: 48px; border: 1px solid #cbd2da; background: #fff; border-radius: 10px; font-size: 1rem; }
.btn-lock:disabled { opacity: 0.5; }
.btn-wipe { min-height: 48px; border: 0; background: #c0392b; color: #fff; border-radius: 10px; font-size: 1rem; font-weight: 700; }
</style>
