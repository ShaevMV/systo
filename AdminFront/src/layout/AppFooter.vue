<script setup>
// Официальные соцсети фестиваля (берутся с 2026.solarsysto.ru).
const socials = [
    { name: 'Telegram', href: 'https://t.me/solarsysto', icon: '/img/brand/telegram-icon.webp' },
    { name: 'VK', href: 'https://vk.com/solarsysto', icon: '/img/brand/vk-icon.webp' },
    { name: 'YouTube', href: 'https://www.youtube.com/channel/UCBcMMab1S6sNtBo8Uar_31A', icon: '/img/brand/youtube-icon.webp' }
];
</script>

<template>
    <div class="layout-footer">
        <div class="layout-footer-brand">
            <span class="font-display text-primary">Solar Systo</span>
            <span class="ml-2">· Админка фестиваля</span>
        </div>
        <div class="layout-footer-socials">
            <a v-for="s in socials" :key="s.name" :href="s.href" :title="s.name" :aria-label="s.name" target="_blank" rel="noopener noreferrer" class="layout-footer-social">
                <img :src="s.icon" :alt="s.name" />
            </a>
        </div>
    </div>
</template>

<style scoped>
.layout-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.layout-footer-brand {
    display: flex;
    align-items: center;
}

.layout-footer-socials {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.layout-footer-social {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
    opacity: 0.7;
}

.layout-footer-social:hover {
    opacity: 1;
    transform: translateY(-1px);
}

.layout-footer-social img {
    width: 1.25rem;
    height: 1.25rem;
    object-fit: contain;
    display: block;
}

/* Соц-иконки белые. В светлой теме инвертируем в тёмный.
   ВАЖНО: весь селектор внутри :global(...) — иначе Vue scoped-CSS вешает
   filter:invert на весь <html> (см. аналогичный фикс в AppSidebar.vue). */
:global(html:not(.app-dark) .layout-footer-social img) {
    filter: invert(1) brightness(0.35);
}
</style>
