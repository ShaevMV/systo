<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useRegisterSW } from 'virtual:pwa-register/vue';
import { pendingCount } from '@/db/queue';
import { hasPin, isUnlocked, unlock } from '@/services/pin';
import { drainQueue } from '@/services/drain';

// Версия сборки — видна в шапке (решение архитектора: SW-версионирование, чтобы билетёр
// не застрял на устаревшей оболочке). Подставляется при сборке через Vite define (PR-2).
const BUILD = import.meta.env.VITE_BUILD_VERSION || 'dev';

const route = useRoute();
const online = ref(navigator.onLine);
const setOnline = () => (online.value = navigator.onLine);

// При появлении связи — дренаж офлайн-намерений впуска в облако (PR-8).
const onNetworkOnline = () => {
    online.value = true;
    drainQueue().then(refreshQueued);
};

// Service worker (Workbox, registerType='prompt'): когда подъехала новая версия —
// показываем кнопку «обновить», НЕ перезагружаем молча посреди потока гостей.
const { needRefresh, updateServiceWorker } = useRegisterSW();
function applyUpdate() {
    updateServiceWorker(true);
}

// Сколько офлайн-намерений ждут досыла — бейдж в шапке.
const queued = ref(0);
async function refreshQueued() {
    try {
        queued.value = await pendingCount();
    } catch {
        queued.value = 0;
    }
}

onMounted(() => {
    window.addEventListener('online', onNetworkOnline);
    window.addEventListener('offline', setOnline);
    window.addEventListener('baza-queue-changed', refreshQueued);
    refreshQueued();
    checkLock();
    // Досыл накопленных офлайн-намерений при старте, если уже онлайн.
    if (online.value) {
        drainQueue().then(refreshQueued);
    }
});
onUnmounted(() => {
    window.removeEventListener('online', onNetworkOnline);
    window.removeEventListener('offline', setOnline);
    window.removeEventListener('baza-queue-changed', refreshQueued);
});

const netLabel = computed(() => (online.value ? 'онлайн' : 'офлайн'));

// PIN-gate (PR-6): если PIN задан и не разблокирован — блокируем экран до ввода PIN.
// Если PIN не задан — гейт не показывается (приложение работает как обычно).
const locked = ref(false);
const pinInput = ref('');
const pinError = ref(null);

async function checkLock() {
    locked.value = (await hasPin()) && !isUnlocked();
}

async function doUnlock() {
    const r = await unlock(pinInput.value);
    pinInput.value = '';
    if (r.ok) {
        locked.value = false;
        pinError.value = null;
    } else if (r.reason === 'wiped') {
        pinError.value = 'Слишком много попыток — кэш стёрт. Войдите онлайн и задайте PIN заново.';
    } else {
        pinError.value = `Неверный PIN. Осталось попыток: ${r.left ?? '?'}`;
    }
}

const tabs = [
    { name: 'scan', label: 'Скан', icon: 'pi-qrcode' },
    { name: 'search', label: 'Поиск', icon: 'pi-search' },
    { name: 'shift', label: 'Смена', icon: 'pi-users' }
];
</script>

<template>
    <div class="kpp-app">
        <header class="kpp-header">
            <div class="kpp-title">КПП · Вход</div>
            <div class="kpp-head-right">
                <span v-if="queued > 0" class="kpp-queued" title="Намерений ждёт досыла">
                    <i class="pi pi-clock"></i> {{ queued }}
                </span>
                <span class="kpp-net" :class="online ? 'is-online' : 'is-offline'">
                    <i class="pi" :class="online ? 'pi-wifi' : 'pi-ban'"></i> {{ netLabel }}
                </span>
            </div>
        </header>

        <div v-if="needRefresh" class="kpp-update">
            <span>Доступна новая версия приложения.</span>
            <button class="kpp-update-btn" @click="applyUpdate">Обновить</button>
        </div>

        <main class="kpp-main">
            <router-view />
        </main>

        <nav class="kpp-tabbar">
            <router-link
                v-for="t in tabs"
                :key="t.name"
                :to="{ name: t.name }"
                class="kpp-tab"
                :class="{ 'is-active': route.name === t.name }"
            >
                <i class="pi" :class="t.icon"></i>
                <span>{{ t.label }}</span>
            </router-link>
        </nav>

        <div class="kpp-build">v{{ BUILD }}</div>

        <!-- PIN-gate (PR-6): блокировка офлайн-доступа к зашифрованному кэшу -->
        <div v-if="locked" class="kpp-lock">
            <div class="kpp-lock-box">
                <i class="pi pi-lock"></i>
                <h2>Введите PIN</h2>
                <p>Доступ к офлайн-данным КПП</p>
                <form @submit.prevent="doUnlock">
                    <input
                        v-model="pinInput"
                        class="kpp-lock-input"
                        type="password"
                        inputmode="numeric"
                        autocomplete="off"
                        placeholder="PIN"
                    />
                    <button type="submit" class="kpp-lock-btn">Разблокировать</button>
                </form>
                <p v-if="pinError" class="kpp-lock-err">{{ pinError }}</p>
            </div>
        </div>
    </div>
</template>

<style>
* { box-sizing: border-box; }
body { margin: 0; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }

.kpp-app {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    min-height: 100dvh;
    background: #f5f6f8;
}

.kpp-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: #ff7900;
    color: #fff;
    font-weight: 600;
    padding-top: max(0.75rem, env(safe-area-inset-top));
}
.kpp-head-right { display: flex; align-items: center; gap: 0.6rem; }
.kpp-net { font-size: 0.85rem; opacity: 0.95; }
.kpp-net.is-offline { color: #ffe08a; }
.kpp-queued {
    font-size: 0.8rem;
    background: rgba(255, 255, 255, 0.25);
    padding: 0.1rem 0.4rem;
    border-radius: 10px;
}

.kpp-update {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.6rem 1rem;
    background: #1f2937;
    color: #fff;
    font-size: 0.9rem;
}
.kpp-update-btn {
    border: 0;
    background: #ff7900;
    color: #fff;
    font-weight: 600;
    padding: 0.4rem 0.9rem;
    border-radius: 6px;
}

.kpp-main { flex: 1; padding: 1rem; overflow-y: auto; }

.kpp-tabbar {
    display: flex;
    background: #fff;
    border-top: 1px solid #e3e6ea;
    padding-bottom: env(safe-area-inset-bottom);
}
.kpp-tab {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    padding: 0.6rem 0;
    color: #6b7280;
    text-decoration: none;
    font-size: 0.8rem;
}
.kpp-tab .pi { font-size: 1.4rem; }
.kpp-tab.is-active { color: #ff7900; }

.kpp-build {
    position: fixed;
    bottom: 4.4rem;
    right: 0.4rem;
    font-size: 0.65rem;
    color: #aab;
    pointer-events: none;
}

.kpp-lock {
    position: fixed; inset: 0; z-index: 1000;
    background: rgba(31, 41, 55, 0.96);
    display: flex; align-items: center; justify-content: center; padding: 1.5rem;
}
.kpp-lock-box { background: #fff; border-radius: 16px; padding: 1.5rem; width: 100%; max-width: 360px; text-align: center; }
.kpp-lock-box .pi { font-size: 2.4rem; color: #ff7900; }
.kpp-lock-box h2 { margin: 0.4rem 0 0; }
.kpp-lock-box p { color: #6b7280; margin: 0.3rem 0 1rem; }
.kpp-lock-input {
    width: 100%; min-height: 56px; text-align: center; letter-spacing: 0.3rem;
    font-size: 1.5rem; border: 1px solid #cbd2da; border-radius: 12px; margin-bottom: 0.75rem;
}
.kpp-lock-btn {
    width: 100%; min-height: 52px; border: 0; border-radius: 12px;
    background: #ff7900; color: #fff; font-size: 1.1rem; font-weight: 700;
}
.kpp-lock-err { color: #c0392b !important; margin-top: 0.75rem; }
</style>
