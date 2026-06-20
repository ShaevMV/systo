<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute } from 'vue-router';

// Версия сборки — видна в шапке (решение архитектора: SW-версионирование, чтобы билетёр
// не застрял на устаревшей оболочке). Подставляется при сборке через Vite define (PR-2).
const BUILD = import.meta.env.VITE_BUILD_VERSION || 'dev';

const route = useRoute();
const online = ref(navigator.onLine);
const setOnline = () => (online.value = navigator.onLine);

onMounted(() => {
    window.addEventListener('online', setOnline);
    window.addEventListener('offline', setOnline);
});
onUnmounted(() => {
    window.removeEventListener('online', setOnline);
    window.removeEventListener('offline', setOnline);
});

const netLabel = computed(() => (online.value ? 'онлайн' : 'офлайн'));

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
            <div class="kpp-net" :class="online ? 'is-online' : 'is-offline'">
                <i class="pi" :class="online ? 'pi-wifi' : 'pi-ban'"></i> {{ netLabel }}
            </div>
        </header>

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
.kpp-net { font-size: 0.85rem; opacity: 0.95; }
.kpp-net.is-offline { color: #ffe08a; }

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
</style>
