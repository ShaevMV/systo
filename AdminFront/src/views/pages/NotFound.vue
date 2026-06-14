<script setup>
import FloatingConfigurator from '@/components/FloatingConfigurator.vue';
</script>

<template>
    <FloatingConfigurator />
    <div class="not-found-screen flex items-center justify-center min-h-screen overflow-hidden">
        <!-- Брендовый декор: НЛО — «страница улетела». Лёгким акцентом за карточкой. -->
        <img src="/img/brand/ufo.webp" alt="" aria-hidden="true" class="brand-decor brand-decor--ufo" />
        <div class="flex flex-col items-center justify-center relative z-10">
            <img src="/img/logo-solarsysto-2026.webp" alt="Solar Systo" class="mb-8 shrink-0" style="height: 4.5rem; width: auto" />
            <div style="border-radius: 56px; padding: 0.3rem; background: linear-gradient(180deg, color-mix(in srgb, var(--primary-color), transparent 60%) 10%, var(--surface-ground) 30%)">
                <div class="w-full bg-surface-0 dark:bg-surface-900 py-20 px-8 sm:px-20 flex flex-col items-center" style="border-radius: 53px">
                    <span class="font-display text-primary text-5xl mb-2">404</span>
                    <h1 class="text-surface-900 dark:text-surface-0 font-bold text-2xl lg:text-4xl mb-2">Страница не найдена</h1>
                    <div class="text-surface-600 dark:text-surface-200 mb-8 text-center">Запрошенный раздел недоступен или был перемещён.</div>
                    <Button as="router-link" label="На главную" to="/" />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.not-found-screen {
    position: relative;
}

.brand-decor {
    position: absolute;
    pointer-events: none;
    user-select: none;
    z-index: 0;
    opacity: 0.12;
}

:global(.app-dark) .brand-decor {
    opacity: 0.18;
}

.brand-decor--ufo {
    top: 12%;
    right: 12%;
    width: clamp(110px, 16vw, 220px);
    transform: rotate(-8deg);
}

@media (max-width: 640px) {
    .brand-decor--ufo {
        width: 90px;
        top: 6%;
        right: 6%;
        opacity: 0.08;
    }
}
</style>
