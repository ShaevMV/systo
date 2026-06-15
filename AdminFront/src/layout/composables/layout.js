import { computed, reactive } from 'vue';

// По умолчанию — СВЕТЛАЯ тема (владелец просил светлый, читаемый интерфейс).
// Тёмная тема остаётся доступной через переключатель в топбаре/конфигураторе.
const layoutConfig = reactive({
    preset: 'Aura',
    primary: 'orange',
    surface: null,
    // По умолчанию — СВЕТЛАЯ тема (владелец просил светлый, читаемый интерфейс).
    // Тёмная фестивальная (плам-космос, тона 2026.solarsysto.ru) — по переключателю в топбаре.
    darkTheme: false,
    menuMode: 'static'
});

// Синхронизируем класс .app-dark на <html> с начальным состоянием конфига (до маунта).
// darkTheme:false → класс снят, стартуем в светлой теме. Переключатель light/dark — в топбаре.
if (typeof document !== 'undefined') {
    document.documentElement.classList.toggle('app-dark', layoutConfig.darkTheme);
}

const layoutState = reactive({
    staticMenuInactive: false,
    overlayMenuActive: false,
    profileSidebarVisible: false,
    configSidebarVisible: false,
    sidebarExpanded: false,
    menuHoverActive: false,
    activeMenuItem: null,
    activePath: null
});

export function useLayout() {
    // Переключение светлая/тёмная: меняем флаг и класс .app-dark на <html>.
    const toggleDarkMode = () => {
        layoutConfig.darkTheme = !layoutConfig.darkTheme;
        document.documentElement.classList.toggle('app-dark', layoutConfig.darkTheme);
    };

    const toggleMenu = () => {
        if (isDesktop()) {
            if (layoutConfig.menuMode === 'static') {
                layoutState.staticMenuInactive = !layoutState.staticMenuInactive;
            }

            if (layoutConfig.menuMode === 'overlay') {
                layoutState.overlayMenuActive = !layoutState.overlayMenuActive;
            }
        } else {
            layoutState.mobileMenuActive = !layoutState.mobileMenuActive;
        }
    };

    const toggleConfigSidebar = () => {
        layoutState.configSidebarVisible = !layoutState.configSidebarVisible;
    };

    const hideMobileMenu = () => {
        layoutState.mobileMenuActive = false;
    };

    const changeMenuMode = (event) => {
        layoutConfig.menuMode = event.value;
        layoutState.staticMenuInactive = false;
        layoutState.mobileMenuActive = false;
        layoutState.sidebarExpanded = false;
        layoutState.menuHoverActive = false;
        layoutState.anchored = false;
    };

    const isDarkTheme = computed(() => layoutConfig.darkTheme);
    const isDesktop = () => window.innerWidth > 991;

    const hasOpenOverlay = computed(() => layoutState.overlayMenuActive);

    return {
        layoutConfig,
        layoutState,
        isDarkTheme,
        toggleDarkMode,
        toggleConfigSidebar,
        toggleMenu,
        hideMobileMenu,
        changeMenuMode,
        isDesktop,
        hasOpenOverlay
    };
}
