import { computed, reactive } from 'vue';

// По умолчанию — СВЕТЛАЯ тема (владелец просил светлый, читаемый интерфейс).
// Тёмная тема остаётся доступной через переключатель в топбаре/конфигураторе.
const layoutConfig = reactive({
    preset: 'Aura',
    primary: 'orange',
    surface: null,
    darkTheme: false,
    menuMode: 'static'
});

// Тёмная тема удалена — приложение ВСЕГДА светлое (владелец просил светлый интерфейс).
// Гарантируем, что класс .app-dark снят с <html> (на случай старого состояния в кеше).
if (typeof document !== 'undefined') {
    document.documentElement.classList.remove('app-dark');
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
        toggleConfigSidebar,
        toggleMenu,
        hideMobileMenu,
        changeMenuMode,
        isDesktop,
        hasOpenOverlay
    };
}
