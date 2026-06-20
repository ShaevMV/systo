<script setup>
// Экран «Сканер» (Ф5, PR-4) — главный экран КПП.
//   камера (нативный BarcodeDetector) → офлайн-сверка по снимку / онлайн /api/scan →
//   цветовой светофор-вердикт во весь экран (реш. #16=A) → «Пропустить» (64px).
// Офлайн: оптимистичная отметка в очередь намерений (досыл при сети). Звук/вибро.
//
// QR-движок — нативный BarcodeDetector (Chrome/Android — основное устройство КПП), без
// тяжёлых зависимостей. Где не поддержан (часть iOS/Firefox) — фолбэк на ручной ввод №.
// JS-полифилл (jsQR/qr-scanner) для полной кросс-браузерности — PR-4-follow-up (нужен npm на CI).
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { resolveScan, doEnter } from '@/services/scan';
import { syncSnapshot } from '@/services/snapshotSync';
import { snapshotCount } from '@/db/snapshot';

const video = ref(null);
let stream = null;
let detector = null;
let scanTimer = null;

const cameraError = ref(null);
const manual = ref('');
const snapCount = ref(0);
const busy = ref(false);
const entering = ref(false);

const result = ref(null); // null — оверлей скрыт, камера активна
const enterMsg = ref(null);

const online = ref(navigator.onLine);
const setOnline = () => (online.value = navigator.onLine);

const overlayClass = computed(() => (result.value ? `verdict verdict--${result.value.color}` : ''));

async function refreshSnapCount() {
    try {
        snapCount.value = await snapshotCount();
    } catch {
        snapCount.value = 0;
    }
}

// Обратная связь: вибро + короткий бип. Красный — длиннее/ниже, зелёный/жёлтый — мягко.
function feedback(color) {
    try {
        if (navigator.vibrate) {
            navigator.vibrate(color === 'red' ? [120, 60, 120] : 60);
        }
    } catch {
        /* вибро не поддержано */
    }
    try {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return;
        const ctx = new Ctx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.frequency.value = color === 'red' ? 220 : 880;
        gain.gain.value = 0.05;
        osc.connect(gain).connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.15);
        osc.onended = () => ctx.close();
    } catch {
        /* звук не поддержан */
    }
}

async function handleText(text) {
    if (busy.value || !text) return;
    busy.value = true;
    try {
        const v = await resolveScan(text, { online: online.value });
        result.value = v; // оверлей показан → скан-петля сама приостановится
        enterMsg.value = null;
        feedback(v.color);
    } finally {
        busy.value = false;
    }
}

function submitManual() {
    const v = manual.value.trim();
    if (v) handleText(v);
}

async function pass() {
    if (!result.value?.enterRef || entering.value) return;
    entering.value = true;
    try {
        const r = await doEnter(result.value.enterRef, { online: online.value });
        if (r.ok) {
            enterMsg.value = r.queued ? 'Впуск записан офлайн — досыл при сети' : 'Впущен';
            setTimeout(next, 700);
        } else {
            enterMsg.value = r.message || 'Не удалось впустить';
            feedback('red');
        }
    } finally {
        entering.value = false;
    }
}

function next() {
    result.value = null;
    enterMsg.value = null;
    manual.value = '';
    // скан-петля возобновится сама (result === null)
}

// ── Камера через нативный BarcodeDetector ─────────────────────────────────────
async function startCamera() {
    if (!('BarcodeDetector' in window)) {
        cameraError.value = 'Сканер камеры не поддержан этим браузером — вводите № вручную.';
        return;
    }
    try {
        detector = new window.BarcodeDetector({ formats: ['qr_code'] });
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } }
        });
        if (video.value) {
            video.value.srcObject = stream;
            await video.value.play();
        }
        // Детектируем ~4 раза/сек (баланс скорости и нагрузки на слабый телефон).
        scanTimer = setInterval(detectTick, 250);
    } catch (e) {
        // Нет камеры / не secure-context (нужен HTTPS, PR-7) → ручной ввод.
        cameraError.value = String(e?.message || e);
    }
}

async function detectTick() {
    if (!detector || !video.value || result.value || busy.value) return;
    try {
        const codes = await detector.detect(video.value);
        if (codes && codes.length) {
            handleText(codes[0].rawValue);
        }
    } catch {
        /* кадр не распознан — продолжаем */
    }
}

function stopCamera() {
    if (scanTimer) {
        clearInterval(scanTimer);
        scanTimer = null;
    }
    if (stream) {
        stream.getTracks().forEach((t) => t.stop());
        stream = null;
    }
    detector = null;
}

onMounted(() => {
    window.addEventListener('online', setOnline);
    window.addEventListener('offline', setOnline);
    refreshSnapCount();

    // Синк снимка best-effort (облако-мастер): тянем дельту, не блокируя экран.
    if (online.value) {
        syncSnapshot().then(refreshSnapCount);
    }

    startCamera();
});

onUnmounted(() => {
    window.removeEventListener('online', setOnline);
    window.removeEventListener('offline', setOnline);
    stopCamera();
});
</script>

<template>
    <section class="scan">
        <!-- Камера -->
        <div class="scan-camera" v-show="!result">
            <video ref="video" class="scan-video" muted playsinline></video>
            <div v-if="cameraError" class="scan-cam-error">
                <i class="pi pi-video"></i>
                <p>Камера недоступна</p>
                <small>{{ cameraError }}</small>
            </div>
            <div class="scan-snap" v-if="snapCount > 0">офлайн-снимок: {{ snapCount }}</div>

            <!-- Ручной ввод № под камерой (фолбэк) -->
            <form class="scan-manual" @submit.prevent="submitManual">
                <input
                    v-model="manual"
                    class="scan-manual-input"
                    inputmode="numeric"
                    placeholder="Ввести № вручную"
                />
                <button type="submit" class="scan-manual-btn" :disabled="busy">
                    <i class="pi pi-search"></i>
                </button>
            </form>
            <router-link class="scan-search-link" :to="{ name: 'search' }">
                Поиск по ФИО / телефону →
            </router-link>
        </div>

        <!-- Светофор-вердикт во весь экран -->
        <div v-if="result" :class="overlayClass">
            <div class="verdict-head">
                <span class="verdict-title">{{ result.title }}</span>
                <span v-if="!result.online" class="verdict-badge">офлайн</span>
            </div>

            <div v-if="result.ticket" class="verdict-card">
                <div class="vc-name">{{ result.ticket.name }}</div>
                <div class="vc-row">
                    <span class="vc-type">{{ result.ticket.typeTicket }}</span>
                    <span
                        v-if="result.ticket.color"
                        class="vc-color"
                        :style="{ background: result.ticket.color }"
                        :title="'Браслет: ' + result.ticket.color"
                    ></span>
                </div>
                <div v-if="result.ticket.status" class="vc-status">{{ result.ticket.status }}</div>
                <!-- B5: офлайн часть данных недоступна -->
                <div v-if="!result.ticket.online" class="vc-online-only">
                    <span><i class="pi pi-phone"></i> доступно онлайн</span>
                    <span><i class="pi pi-comment"></i> доступно онлайн</span>
                </div>
            </div>

            <p v-if="result.reason" class="verdict-reason">{{ result.reason }}</p>
            <p v-if="enterMsg" class="verdict-enter-msg">{{ enterMsg }}</p>

            <div class="verdict-actions">
                <button v-if="result.enterRef" class="verdict-pass" :disabled="entering" @click="pass">
                    <i class="pi pi-check"></i> ПРОПУСТИТЬ
                </button>
                <button class="verdict-next" @click="next">
                    {{ result.enterRef ? 'Отмена' : 'Сканировать ещё' }}
                </button>
            </div>
        </div>
    </section>
</template>

<style scoped>
.scan { height: 100%; display: flex; flex-direction: column; }

/* Камера */
.scan-camera { position: relative; flex: 1; display: flex; flex-direction: column; gap: 0.75rem; }
.scan-video {
    width: 100%;
    flex: 1;
    min-height: 45vh;
    object-fit: cover;
    background: #000;
    border-radius: 12px;
}
.scan-cam-error {
    position: absolute; inset: 0;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 0.4rem; color: #8a93a0; text-align: center; padding: 1rem;
}
.scan-cam-error .pi { font-size: 2.4rem; }
.scan-cam-error small { color: #aab; word-break: break-word; }
.scan-snap {
    position: absolute; top: 8px; left: 8px;
    background: rgba(0, 0, 0, 0.5); color: #fff;
    font-size: 0.7rem; padding: 2px 8px; border-radius: 10px;
}

.scan-manual { display: flex; gap: 0.5rem; }
.scan-manual-input {
    flex: 1; min-height: 48px; padding: 0 0.8rem;
    border: 1px solid #cbd2da; border-radius: 10px; font-size: 1.1rem;
}
.scan-manual-btn {
    min-width: 56px; border: 0; background: #ff7900; color: #fff;
    border-radius: 10px; font-size: 1.1rem;
}
.scan-search-link { text-align: center; color: #6b7280; text-decoration: none; font-size: 0.9rem; }

/* Светофор */
.verdict {
    flex: 1;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 1rem; border-radius: 12px; padding: 1.25rem; color: #fff; text-align: center;
}
.verdict--green { background: #1e9e54; }
.verdict--yellow { background: #d99100; }
.verdict--red { background: #c0392b; }

.verdict-head { display: flex; align-items: center; gap: 0.6rem; }
.verdict-title { font-size: 2rem; font-weight: 800; }
.verdict-badge {
    background: rgba(255, 255, 255, 0.25); border-radius: 10px;
    padding: 0.15rem 0.5rem; font-size: 0.8rem;
}

.verdict-card {
    background: rgba(255, 255, 255, 0.15); border-radius: 12px;
    padding: 0.9rem 1.1rem; width: 100%; max-width: 420px;
}
.vc-name { font-size: 1.5rem; font-weight: 700; }
.vc-row { display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 0.3rem; }
.vc-type { font-size: 1.05rem; }
.vc-color { width: 18px; height: 18px; border-radius: 50%; border: 2px solid rgba(255, 255, 255, 0.8); display: inline-block; }
.vc-status { margin-top: 0.3rem; opacity: 0.9; }
.vc-online-only {
    display: flex; justify-content: center; gap: 1rem; margin-top: 0.5rem;
    font-size: 0.8rem; opacity: 0.8;
}

.verdict-reason { font-size: 1rem; opacity: 0.95; margin: 0; }
.verdict-enter-msg { font-weight: 700; margin: 0; }

.verdict-actions { display: flex; flex-direction: column; gap: 0.6rem; width: 100%; max-width: 420px; }
.verdict-pass {
    min-height: 64px; border: 0; border-radius: 12px;
    background: #fff; color: #1e9e54; font-size: 1.3rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center; gap: 0.5rem;
}
.verdict-pass:disabled { opacity: 0.6; }
.verdict-next {
    min-height: 48px; border: 1px solid rgba(255, 255, 255, 0.7);
    background: transparent; color: #fff; border-radius: 10px; font-size: 1rem;
}
</style>
