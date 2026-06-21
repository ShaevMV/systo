<script setup>
// Экран «Ещё» (Шаг 4): меню управления для PWA. Пункты видны по правам текущей роли
// (whoami). Сюда добавляются Права доступа (Шаг 4), Регистрация персонала (Шаг 5),
// Управление сменами (Шаг 6) — каждый гейтится своим правом.
import { computed, onMounted } from 'vue';
import { loadCurrentUser, useCurrentUser, hasPermission } from '@/services/user';

const user = useCurrentUser();

const allItems = [
    { label: 'Права доступа', sub: 'Матрица роль × действие', route: 'permissions', icon: 'pi-lock', perm: 'rbac.manage' },
    { label: 'Регистрация персонала', sub: 'Создать сотрудника', route: 'staff', icon: 'pi-user-plus', perm: 'staff.manage' },
    { label: 'Управление сменами', sub: 'Создать / закрыть смену', route: 'shifts', icon: 'pi-users', perm: 'shift.compose' }
];

// Показываем только то, на что есть право И для чего уже есть экран (route зарегистрирован).
const routeNames = new Set(['permissions', 'staff', 'shifts']);
const items = computed(() => allItems.filter((it) => routeNames.has(it.route) && hasPermission(it.perm)));

onMounted(loadCurrentUser);
</script>

<template>
    <section class="more">
        <div v-if="user" class="more-user">
            <i class="pi pi-user"></i>
            <div>
                <div class="more-name">{{ user.name || user.email || ('#' + user.id) }}</div>
                <div class="more-role">{{ user.role_label }}</div>
            </div>
        </div>

        <router-link v-for="it in items" :key="it.route" class="more-item" :to="{ name: it.route }">
            <i class="pi more-ico" :class="it.icon"></i>
            <div class="more-text">
                <div class="more-label">{{ it.label }}</div>
                <div class="more-sub">{{ it.sub }}</div>
            </div>
            <i class="pi pi-angle-right more-arrow"></i>
        </router-link>

        <p v-if="user && items.length === 0" class="more-empty">
            Нет разделов управления для вашей роли.
        </p>
    </section>
</template>

<style scoped>
.more { display: flex; flex-direction: column; gap: 0.75rem; }
.more-user {
    display: flex; align-items: center; gap: 0.6rem;
    background: #fff; border: 1px solid #e3e6ea; border-radius: 12px; padding: 0.8rem 1rem;
}
.more-user .pi-user { font-size: 1.6rem; color: #ff7900; }
.more-name { font-weight: 700; }
.more-role { color: #6b7280; font-size: 0.85rem; }
.more-item {
    display: flex; align-items: center; gap: 0.8rem; text-decoration: none; color: inherit;
    background: #fff; border: 1px solid #e3e6ea; border-radius: 12px; padding: 0.9rem 1rem;
}
.more-ico { font-size: 1.4rem; color: #ff7900; }
.more-text { flex: 1; }
.more-label { font-weight: 600; }
.more-sub { color: #6b7280; font-size: 0.85rem; }
.more-arrow { color: #aab; }
.more-empty { color: #8a93a0; text-align: center; padding: 1.5rem 0; }
</style>
