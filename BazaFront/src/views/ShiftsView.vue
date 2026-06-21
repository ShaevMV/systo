<script setup>
// Экран «Управление сменами» (Шаг 6): создать смену (состав + начальник) и закрыть.
// Доступ — право shift.compose (бэкенд гейтит). Начальник видит/закрывает только свою
// смену, administrator — все (изоляция на бэке). Назначение начальника: admin выбирает,
// начальник смены — авто (он сам).
import { ref, computed, onMounted } from 'vue';
import { loadShifts, loadShiftUsers, createShift, closeShift } from '@/services/shifts';

const shifts = ref([]);
const users = ref([]);
const isAdmin = ref(false);
const selected = ref(new Set()); // id выбранных в состав
const chiefId = ref(null);       // выбранный начальник (только admin)
const loading = ref(true);
const saving = ref(false);
const msg = ref(null);
const err = ref(null);

const selectedList = computed(() => users.value.filter((u) => selected.value.has(u.id)));

async function reload() {
    loading.value = true;
    err.value = null;
    try {
        const data = await loadShifts();
        shifts.value = data.shifts || [];
        isAdmin.value = !!data.is_admin;
        const u = await loadShiftUsers();
        users.value = u.users || [];
    } catch (e) {
        err.value = e?.response?.status === 403 ? 'Нет доступа (нужно право «Формирование смены»)' : 'Не удалось загрузить';
    } finally {
        loading.value = false;
    }
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
    if (selected.value.size === 0) {
        err.value = 'Выберите состав смены';
        return;
    }
    if (isAdmin.value && !chiefId.value) {
        err.value = 'Выберите начальника смены';
        return;
    }
    saving.value = true;
    msg.value = null;
    err.value = null;
    try {
        const payload = { members: Array.from(selected.value) };
        if (isAdmin.value) payload.chief_id = chiefId.value;
        await createShift(payload);
        msg.value = 'Смена создана';
        selected.value = new Set();
        chiefId.value = null;
        await reload();
    } catch (e) {
        err.value = e?.response?.status === 422 ? 'Проверьте состав/начальника' : 'Не удалось создать смену';
    } finally {
        saving.value = false;
    }
}

async function doClose(id) {
    if (!confirm('Закрыть смену?')) return;
    err.value = null;
    msg.value = null;
    try {
        await closeShift(id);
        msg.value = 'Смена закрыта';
        await reload();
    } catch (e) {
        err.value = e?.response?.status === 403 ? 'Можно закрыть только свою смену' : 'Не удалось закрыть';
    }
}
</script>

<template>
    <section class="shifts">
        <h2 class="sh-title">Управление сменами</h2>
        <p v-if="err" class="sh-err">{{ err }}</p>
        <p v-if="msg" class="sh-ok">{{ msg }}</p>

        <!-- Открытые смены -->
        <h3 class="sh-sub">Открытые смены</h3>
        <p v-if="loading" class="sh-muted">Загрузка…</p>
        <ul v-else class="sh-list">
            <li v-for="s in shifts" :key="s.id" class="sh-row">
                <div>
                    <div class="sh-chief"><i class="pi pi-user"></i> {{ s.chief_name || 'без начальника' }}</div>
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
            <button class="sh-save" :disabled="saving" @click="submit">
                {{ saving ? 'Создание…' : 'Создать смену' }}
            </button>
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
.sh-meta { color: #6b7280; font-size: 0.82rem; }
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
</style>
