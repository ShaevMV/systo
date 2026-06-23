<script setup>
// Экран «Управление сменами» (Шаг 6): создать смену (состав + начальник) и закрыть.
// Доступ — право shift.compose (бэкенд гейтит). Начальник видит/закрывает только свою
// смену, administrator — все (изоляция на бэке). Назначение начальника: admin выбирает,
// начальник смены — авто (он сам).
import { ref, computed, onMounted } from 'vue';
import { loadShifts, loadShiftUsers, createShift, closeShift } from '@/services/shifts';
import { loadKppFestivals } from '@/services/festivals';
import { notifySuccess } from '@/lib/notify';

const shifts = ref([]);
const users = ref([]);
const isAdmin = ref(false);
const selected = ref(new Set()); // id выбранных в состав
const chiefId = ref(null);       // выбранный начальник (только admin)
const festivals = ref([]);       // активные для КПП (TD-48)
const festivalId = ref(null);    // выбранный фестиваль смены
const loading = ref(true);
const saving = ref(false);

const selectedList = computed(() => users.value.filter((u) => selected.value.has(u.id)));
// Один активный фестиваль — авто-выбор (норма дня); несколько — обязателен выбор.
const needFestivalChoice = computed(() => festivals.value.length > 1);

// Чего не хватает для создания смены (для disabled-кнопки + подсказки рядом, чтобы не
// гадать — раньше причина выводилась вверху экрана, далеко от кнопки).
const createBlockedReason = computed(() => {
    if (selected.value.size === 0) return 'выберите состав смены';
    if (isAdmin.value && !chiefId.value) return 'назначьте начальника (кнопка «сделать начальником»)';
    if (needFestivalChoice.value && !festivalId.value) return 'выберите фестиваль смены';
    return null;
});

// Авто-выбор единственного фестиваля; иначе сбрасываем невалидный выбор.
function applyFestivalDefault() {
    if (festivals.value.length === 1) {
        festivalId.value = festivals.value[0].id;
    } else if (!festivals.value.some((f) => f.id === festivalId.value)) {
        festivalId.value = null;
    }
}

async function reload() {
    loading.value = true;
    try {
        const data = await loadShifts();
        shifts.value = data.shifts || [];
        isAdmin.value = !!data.is_admin;
        const u = await loadShiftUsers();
        users.value = u.users || [];
    } catch {
        /* ошибку загрузки покажет централизованный http.js-перехватчик (тост) */
    } finally {
        loading.value = false;
    }

    // Реестр фестивалей — ВСПОМОГАТЕЛЬНЫЙ: его сбой НЕ должен ронять экран смен (критический
    // путь КПП). Мягкая деградация: нет реестра → просто без селектора фестиваля.
    try {
        festivals.value = await loadKppFestivals();
    } catch {
        festivals.value = [];
    }
    applyFestivalDefault();
}

function toggle(id) {
    const s = new Set(selected.value);
    if (s.has(id)) {
        s.delete(id);
        if (chiefId.value === id) chiefId.value = null;
    } else {
        s.add(id);
    }
    selected.value = s;
}

async function submit() {
    // Защита: кнопка и так disabled при createBlockedReason; сюда не попадём, пока невалидно.
    if (createBlockedReason.value) return;
    saving.value = true;
    try {
        const payload = { members: Array.from(selected.value) };
        if (isAdmin.value) payload.chief_id = chiefId.value;
        if (festivalId.value) payload.festival_id = festivalId.value;
        await createShift(payload);
        notifySuccess('Смена создана');
        selected.value = new Set();
        chiefId.value = null;
        await reload();
    } catch {
        /* ошибку покажет http.js-перехватчик (тост) */
    } finally {
        saving.value = false;
    }
}

async function doClose(id) {
    if (!confirm('Закрыть смену?')) return;
    try {
        await closeShift(id);
        notifySuccess('Смена закрыта');
        await reload();
    } catch {
        /* ошибку покажет http.js-перехватчик (тост) */
    }
}

// Без этого вызова «Открытые смены» висели на «Загрузка…»: onMounted был импортирован, но не дёргался.
onMounted(reload);
</script>

<template>
    <section class="shifts">
        <h2 class="sh-title">Управление сменами</h2>

        <!-- Открытые смены -->
        <h3 class="sh-sub">Открытые смены</h3>
        <p v-if="loading" class="sh-muted">Загрузка…</p>
        <ul v-else class="sh-list">
            <li v-for="s in shifts" :key="s.id" class="sh-row">
                <div>
                    <div class="sh-chief"><i class="pi pi-user"></i> {{ s.chief_name || 'без начальника' }}</div>
                    <div class="sh-fest" v-if="s.festival_name"><i class="pi pi-flag"></i> {{ s.festival_name }}</div>
                    <div class="sh-meta">Состав: {{ s.members_count }} · впущено эл/жив/спис/авто:
                        {{ s.counts.el }}/{{ s.counts.live }}/{{ s.counts.spisok }}/{{ s.counts.auto }}</div>
                    <div class="sh-meta" v-if="s.start">с {{ s.start }}</div>
                </div>
                <button class="sh-close" @click="doClose(s.id)">Закрыть</button>
            </li>
            <li v-if="!loading && shifts.length === 0" class="sh-muted">Открытых смен нет</li>
        </ul>

        <!-- Создание смены -->
        <h3 class="sh-sub">Создать смену</h3>
        <div class="sh-form">
            <!-- ① Фестиваль смены (TD-48): один — авто-выбор; несколько — обязателен выбор. -->
            <div class="sh-fest-pick" v-if="festivals.length">
                <div class="sh-fest-label">Фестиваль смены<span v-if="needFestivalChoice" class="sh-req"> · обязательно</span></div>
                <template v-if="needFestivalChoice">
                    <button
                        v-for="f in festivals"
                        :key="f.id"
                        type="button"
                        class="sh-fest-card"
                        :class="{ 'is-on': festivalId === f.id }"
                        @click="festivalId = f.id"
                    >
                        <i class="pi pi-flag"></i> {{ f.name }}<span v-if="f.year" class="sh-fest-year"> {{ f.year }}</span>
                    </button>
                </template>
                <div v-else class="sh-fest-auto">
                    <i class="pi pi-flag"></i> {{ festivals[0].name }}<span v-if="festivals[0].year"> {{ festivals[0].year }}</span>
                    <span class="sh-fest-hint">выбран автоматически</span>
                </div>
            </div>

            <div class="sh-members">
                <label v-for="u in users" :key="u.id" class="sh-member">
                    <input type="checkbox" :checked="selected.has(u.id)" @change="toggle(u.id)" />
                    <span>{{ u.name }}</span>
                    <span class="sh-role">{{ u.role_label }}</span>
                    <button
                        v-if="isAdmin && selected.has(u.id)"
                        type="button"
                        class="sh-chief-pick"
                        :class="{ 'is-chief': chiefId === u.id }"
                        @click="chiefId = u.id"
                    >{{ chiefId === u.id ? '★ начальник' : 'сделать начальником' }}</button>
                </label>
            </div>
            <p v-if="!isAdmin" class="sh-muted">Начальником станете вы (автоматически).</p>
            <button class="sh-save" :disabled="saving || !!createBlockedReason" @click="submit">
                {{ saving ? 'Создание…' : 'Создать смену' }}
            </button>
            <p v-if="createBlockedReason && !saving" class="sh-hint">Чтобы создать смену: {{ createBlockedReason }}</p>
        </div>
    </section>
</template>

<style scoped>
.shifts { display: flex; flex-direction: column; gap: 0.75rem; }
.sh-title, .sh-sub { margin: 0; }
.sh-err { color: #c0392b; }
.sh-ok { color: #1e9e54; font-weight: 600; }
.sh-muted { color: #8a93a0; }
.sh-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.5rem; }
.sh-row { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; background: #fff; border: 1px solid #e3e6ea; border-radius: 12px; padding: 0.7rem 0.9rem; }
.sh-chief { font-weight: 700; }
.sh-fest { color: #ff7900; font-size: 0.82rem; font-weight: 600; }
.sh-meta { color: #6b7280; font-size: 0.82rem; }
.sh-fest-pick { display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center; }
.sh-fest-label { flex-basis: 100%; font-weight: 600; }
.sh-req { color: #c0392b; font-weight: 600; }
.sh-fest-card { min-height: 56px; padding: 0.4rem 0.9rem; border: 2px solid #ff7900; background: #fff; color: #ff7900; border-radius: 12px; font-weight: 600; font-size: 0.95rem; }
.sh-fest-card.is-on { background: #ff7900; color: #fff; }
.sh-fest-year { opacity: 0.8; }
.sh-fest-auto { display: flex; align-items: center; gap: 0.4rem; color: #5a6573; background: #f5f6f8; border-radius: 10px; padding: 0.5rem 0.8rem; font-weight: 600; }
.sh-fest-hint { color: #8a93a0; font-weight: 400; font-size: 0.8rem; margin-left: 0.3rem; }
.sh-close { min-height: 42px; padding: 0 1rem; border: 0; border-radius: 10px; background: #c0392b; color: #fff; font-weight: 600; }
.sh-form { background: #fff; border: 1px solid #e3e6ea; border-radius: 12px; padding: 1rem; display: flex; flex-direction: column; gap: 0.6rem; }
.sh-members { display: flex; flex-direction: column; gap: 0.35rem; max-height: 45vh; overflow-y: auto; }
.sh-member { display: flex; align-items: center; gap: 0.5rem; }
.sh-member span { }
.sh-role { color: #6b7280; font-size: 0.8rem; margin-left: auto; }
.sh-chief-pick { border: 1px solid #ff7900; background: #fff; color: #ff7900; border-radius: 8px; font-size: 0.78rem; padding: 0.2rem 0.5rem; }
.sh-chief-pick.is-chief { background: #ff7900; color: #fff; }
.sh-save { min-height: 48px; border: 0; border-radius: 10px; background: #ff7900; color: #fff; font-weight: 700; font-size: 1rem; }
.sh-save:disabled { opacity: 0.6; }
.sh-hint { margin: 0; color: #8a93a0; font-size: 0.85rem; text-align: center; }
</style>
