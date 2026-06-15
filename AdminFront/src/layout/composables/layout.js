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

// Синхронизируем класс .app-dark на <html> с начальным состоянием конфига.
// Делается один раз при загрузке модуля (до маунта приложения).
// darkTheme:false → класс снимается, остаётся светлая тема. Переключатель light/dark рабочий.
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
    // Без document.startViewTransition: на экранах с canvas (графики дашборда) и iframe
    // (превью редактора шаблонов) снимок View-Transition залипает и оставляет замороженный
    // светлый кадр поверх уже тёмной страницы — «дымка пропадает» и держится до перезагрузки.
    // Мгновенное переключение класса надёжно на всех экранах.
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
