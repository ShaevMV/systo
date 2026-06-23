<script setup>
// Экран «Регистрация персонала» (Шаг 5): админ заводит сотрудника + назначает роль.
// Доступ — право staff.manage (бэкенд гейтит; в меню виден только при наличии права).
import { ref, onMounted } from 'vue';
import { loadStaff, createStaff } from '@/services/staff';
import { notifySuccess } from '@/lib/notify';

const staff = ref([]);
const roles = ref([]);
const form = ref({ name: '', email: '', password: '', role: '', is_admin: false });
const loading = ref(true);
const saving = ref(false);

async function reload() {
    loading.value = true;
    try {
        const data = await loadStaff();
        staff.value = data.staff || [];
        roles.value = data.roles || [];
    } catch {
        /* ошибку загрузки покажет централизованный http.js-перехватчик (тост) */
    } finally {
        loading.value = false;
    }
}

async function submit() {
    saving.value = true;
    try {
        await createStaff({ ...form.value });
        notifySuccess('Сотрудник сохранён');
        form.value = { name: '', email: '', password: '', role: '', is_admin: false };
        await reload();
    } catch {
        /* ошибку покажет http.js-перехватчик (тост) */
    } finally {
        saving.value = false;
    }
}

onMounted(reload);
</script>

<template>
    <section class="staff">
        <h2 class="staff-title">Регистрация персонала</h2>

        <form class="staff-form" @submit.prevent="submit">
            <input v-model="form.name" class="fld" placeholder="Имя сотрудника" />
            <input v-model="form.email" class="fld" type="email" placeholder="Email (логин)" />
            <input v-model="form.password" class="fld" type="password" placeholder="Пароль (мин. 6)" autocomplete="new-password" />
            <select v-model="form.role" class="fld">
                <option value="">Роль смены (по умолчанию — билетёр)</option>
                <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
            </select>
            <label class="staff-admin">
                <input v-model="form.is_admin" type="checkbox" /> Администратор (полный доступ)
            </label>
            <button class="staff-save" type="submit" :disabled="saving">
                {{ saving ? 'Сохранение…' : 'Сохранить сотрудника' }}
            </button>
        </form>

        <h3 class="staff-sub">Сотрудники</h3>
        <p v-if="loading" class="staff-muted">Загрузка…</p>
        <ul v-else class="staff-list">
            <li v-for="u in staff" :key="u.id" class="staff-row">
                <div>
                    <div class="staff-name">{{ u.name }}</div>
                    <div class="staff-email">{{ u.email }}</div>
                </div>
                <span class="staff-role">{{ u.role_label }}</span>
            </li>
        </ul>
    </section>
</template>

<style scoped>
.staff { display: flex; flex-direction: column; gap: 0.75rem; }
.staff-title, .staff-sub { margin: 0; }
.staff-err { color: #c0392b; }
.staff-muted { color: #8a93a0; }
.staff-form { display: flex; flex-direction: column; gap: 0.6rem; background: #fff; border: 1px solid #e3e6ea; border-radius: 12px; padding: 1rem; }
.fld { min-height: 46px; padding: 0 0.8rem; border: 1px solid #cbd2da; border-radius: 10px; font-size: 1rem; }
.staff-admin { display: flex; align-items: center; gap: 0.5rem; color: #374151; }
.staff-save { min-height: 48px; border: 0; border-radius: 10px; background: #ff7900; color: #fff; font-weight: 700; font-size: 1rem; }
.staff-save:disabled { opacity: 0.6; }
.staff-ok { color: #1e9e54; font-weight: 600; }
.staff-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.4rem; }
.staff-row { display: flex; align-items: center; justify-content: space-between; background: #fff; border: 1px solid #e3e6ea; border-radius: 10px; padding: 0.6rem 0.9rem; }
.staff-name { font-weight: 600; }
.staff-email { color: #6b7280; font-size: 0.85rem; }
.staff-role { color: #ff7900; font-size: 0.85rem; white-space: nowrap; }
</style>
