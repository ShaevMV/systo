<script setup>
// Экран «Права доступа» (Шаг 4): матрица роль×действие в новом PWA (перенос старого Blade).
// Строки — действия, столбцы — роли. administrator задизейблен (суперроль, всегда всё).
// Форма = источник правды: снятый чекбокс снимает право. Доступ — право rbac.manage (бэкенд гейтит).
import { ref, onMounted } from 'vue';
import { loadMatrix, saveMatrix } from '@/services/permissions';

const roles = ref([]);
const actions = ref([]);
const adminRole = ref('administrator');
// granted[role] = Set(action) — локальное состояние чекбоксов.
const granted = ref({});
const loading = ref(true);
const saving = ref(false);
const msg = ref(null);
const err = ref(null);

async function load() {
    loading.value = true;
    err.value = null;
    try {
        const data = await loadMatrix();
        roles.value = data.roles || [];
        actions.value = data.actions || [];
        adminRole.value = data.admin_role || 'administrator';
        const m = data.matrix || {};
        const g = {};
        for (const r of roles.value) {
            g[r.value] = new Set(m[r.value] || []);
        }
        granted.value = g;
    } catch (e) {
        err.value = e?.response?.status === 403 ? 'Нет доступа (нужно право «Управление матрицей прав»)' : 'Не удалось загрузить матрицу';
    } finally {
        loading.value = false;
    }
}

function isAdmin(role) {
    return role === adminRole.value;
}

function has(role, action) {
    return isAdmin(role) || (granted.value[role]?.has(action) ?? false);
}

function toggle(role, action) {
    if (isAdmin(role)) return; // суперроль не редактируется
    const set = granted.value[role] || new Set();
    if (set.has(action)) set.delete(action);
    else set.add(action);
    granted.value = { ...granted.value, [role]: set };
}

async function save() {
    saving.value = true;
    msg.value = null;
    err.value = null;
    try {
        const perm = {};
        for (const r of roles.value) {
            if (isAdmin(r.value)) continue;
            perm[r.value] = Array.from(granted.value[r.value] || []);
        }
        await saveMatrix(perm);
        msg.value = 'Права доступа сохранены';
    } catch (e) {
        err.value = e?.response?.status === 403 ? 'Нет доступа' : 'Не удалось сохранить';
    } finally {
        saving.value = false;
    }
}

onMounted(load);
</script>

<template>
    <section class="perm">
        <h2 class="perm-title">Права доступа</h2>
        <p v-if="err" class="perm-err">{{ err }}</p>
        <p v-if="loading" class="perm-muted">Загрузка…</p>

        <div v-if="!loading && !err" class="perm-table-wrap">
            <table class="perm-table">
                <thead>
                    <tr>
                        <th class="perm-action-col">Действие</th>
                        <th v-for="r in roles" :key="r.value" :class="{ 'is-admin': isAdmin(r.value) }">{{ r.label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in actions" :key="a.value">
                        <td class="perm-action-col">{{ a.label }}</td>
                        <td v-for="r in roles" :key="r.value" class="perm-cell">
                            <input
                                type="checkbox"
                                :checked="has(r.value, a.value)"
                                :disabled="isAdmin(r.value)"
                                @change="toggle(r.value, a.value)"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="!loading && !err" class="perm-actions">
            <button class="perm-save" :disabled="saving" @click="save">
                {{ saving ? 'Сохранение…' : 'Сохранить' }}
            </button>
            <span v-if="msg" class="perm-ok">{{ msg }}</span>
        </div>
        <p class="perm-muted">Администратор — суперроль (всегда полный доступ, не редактируется).</p>
    </section>
</template>

<style scoped>
.perm { display: flex; flex-direction: column; gap: 0.75rem; }
.perm-title { margin: 0; }
.perm-err { color: #c0392b; }
.perm-muted { color: #8a93a0; font-size: 0.85rem; }
.perm-table-wrap { overflow-x: auto; }
.perm-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.perm-table th, .perm-table td { border: 1px solid #e3e6ea; padding: 0.5rem; text-align: center; }
.perm-action-col { text-align: left; min-width: 11rem; }
.perm-table th.is-admin { color: #ff7900; }
.perm-cell input { width: 20px; height: 20px; }
.perm-actions { display: flex; align-items: center; gap: 0.75rem; }
.perm-save {
    min-height: 48px; padding: 0 1.4rem; border: 0; border-radius: 10px;
    background: #ff7900; color: #fff; font-weight: 700; font-size: 1rem;
}
.perm-save:disabled { opacity: 0.6; }
.perm-ok { color: #1e9e54; font-weight: 600; }
</style>
