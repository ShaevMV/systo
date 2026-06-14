<script setup>
import { useLayout } from '@/layout/composables/layout';
import { onBeforeUnmount, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import AppMenu from './AppMenu.vue';

const { layoutState, isDesktop, hasOpenOverlay } = useLayout();
const route = useRoute();
const sidebarRef = ref(null);
let outsideClickListener = null;

watch(
    () => route.path,
    (newPath) => {
        if (isDesktop()) layoutState.activePath = null;
        else layoutState.activePath = newPath;

        layoutState.overlayMenuActive = false;
        layoutState.mobileMenuActive = false;
        layoutState.menuHoverActive = false;
    },
    { immediate: true }
);

watch(hasOpenOverlay, (newVal) => {
    if (isDesktop()) {
        if (newVal) bindOutsideClickListener();
        else unbindOutsideClickListener();
    }
});

const bindOutsideClickListener = () => {
    if (!outsideClickListener) {
        outsideClickListener = (event) => {
            if (isOutsideClicked(event)) {
                layoutState.overlayMenuActive = false;
            }
        };

        document.addEventListener('click', outsideClickListener);
    }
};

const unbindOutsideClickListener = () => {
    if (outsideClickListener) {
        document.removeEventListener('click', outsideClickListener);
        outsideClickListener = null;
    }
};

const isOutsideClicked = (event) => {
    const topbarButtonEl = document.querySelector('.layout-menu-button');

    return !(sidebarRef.value.isSameNode(event.target) || sidebarRef.value.contains(event.target) || topbarButtonEl?.isSameNode(event.target) || topbarButtonEl?.contains(event.target));
};

onBeforeUnmount(() => {
    unbindOutsideClickListener();
});
</script>

<template>
    <div ref="sidebarRef" class="layout-sidebar">
        <AppMenu />
        <!-- Брендовый акцент внизу сайдбара: фирменный знак-птица. Деликатно, без текста. -->
        <div class="layout-sidebar-brand">
            <img src="/img/brand/nota-logo.webp" alt="Solar Systo" class="layout-sidebar-brand-icon" />
        </div>
    </div>
</template>

<style scoped>
.layout-sidebar {
    display: flex;
    flex-direction: column;
}

.layout-sidebar-brand {
    margin-top: auto;
    display: flex;
    justify-content: center;
    padding: 1.25rem 0 0.75rem;
}

.layout-sidebar-brand-icon {
    width: 2.25rem;
    height: auto;
    object-fit: contain;
    opacity: 0.4;
    transition: opacity 0.2s ease;
}

.layout-sidebar-brand:hover .layout-sidebar-brand-icon {
    opacity: 0.7;
}

/* Знак белый — в светлой теме подкрашиваем в тёмный, чтобы был виден. */
:global(html:not(.app-dark)) .layout-sidebar-brand-icon {
    filter: invert(1) brightness(0.45);
}
</style>
