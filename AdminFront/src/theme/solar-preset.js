// Брендовый preset фестиваля Solar Systo поверх Aura (@primeuix/themes).
//
// Идея: тёмный космический фон в фирменных пурпур/навигатор тонах,
// тёплый оранжевый акцент как primary (читаемый на тёмном),
// лайм как success, винно-красный как danger.
// Источник палитры — реальные hex с https://2026.solarsysto.ru/.
//
// Palette helpers внизу файла собирают шкалы 50…950 так, чтобы:
//  - брендовый hex попадал в «опорный» тон (500/600),
//  - 50…400 — осветление к белому, 600…950 — затемнение к чёрному.

import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';

// --- Брендовые акценты ---

// PRIMARY — оранжевый #ff7900 (тёплый, читаемый на тёмном фоне).
// Опорный тон — 500.
const solarOrange = {
    50: '#fff4e6',
    100: '#ffe2bf',
    200: '#ffce93',
    300: '#ffb866',
    400: '#ffa033',
    500: '#ff7900', // бренд
    600: '#e66a00',
    700: '#bf5600',
    800: '#994400',
    900: '#7a3600',
    950: '#421d00'
};

// SUCCESS / вторичный акцент — лаймово-солнечный #95b801. Опорный тон — 500.
const solarLime = {
    50: '#f6fbe6',
    100: '#e8f4bd',
    200: '#d6ec8e',
    300: '#c2e25c',
    400: '#aecd2e',
    500: '#95b801', // бренд
    600: '#7c9a00',
    700: '#637b00',
    800: '#4d6000',
    900: '#3b4a00',
    950: '#202800'
};

// DANGER / error — винно-красный. Яркий #cb002f как опорный тон,
// глубокие винные #8b1345/#781918 уходят в тёмную часть шкалы.
const solarWine = {
    50: '#fde8ed',
    100: '#fabccb',
    200: '#f48da4',
    300: '#ed5d7c',
    400: '#e3325a',
    500: '#cb002f', // бренд (яркий красный)
    600: '#a8002a',
    700: '#8b1345', // винный
    800: '#781918',
    900: '#5e1414',
    950: '#330a0a'
};

// WARNING — мягкий оранжевый #ff881a / #f0933f. Опорный тон 500.
const solarAmber = {
    50: '#fff6ea',
    100: '#ffe6c4',
    200: '#ffd29a',
    300: '#ffba66',
    400: '#ff9f3a',
    500: '#ff881a', // бренд (мягкий оранж)
    600: '#e6750d',
    700: '#bf5e00',
    800: '#994b00',
    900: '#7a3c00',
    950: '#412000'
};

// --- Поверхности (surface) ---

// DARK surface — фирменный космос: глубокий навигатор #040a28 в самой тёмной части,
// пурпур/плам #311332 в средних тонах. Это даёт тёмный фон с фиолетово-сливовым
// оттенком вместо нейтрально-серого.
const surfaceDark = {
    0: '#ffffff',
    50: '#f6f3f7',
    100: '#e8e0ea',
    200: '#cdbcd1',
    300: '#a98fb0',
    400: '#7d5f85',
    500: '#5a3d63',
    600: '#46294f', // приподнятые карточки/оверлеи
    700: '#3a1f44', // боковая панель / surface-card
    800: '#311332', // основной плам-фон (бренд)
    900: '#1a0c2a', // переход к навигатору
    950: '#040a28' // глубокий навигатор (бренд) — самый тёмный фон
};

// LIGHT surface — спокойный светлый с лёгким тёплым/сливовым подтоном,
// чтобы днём админка читалась, но не выглядела «сваренной» из чистого серого.
const surfaceLight = {
    0: '#ffffff',
    50: '#faf8fb',
    100: '#f3eff5',
    200: '#e7e0ea',
    300: '#d4cad9',
    400: '#a99eb0',
    500: '#7e7385',
    600: '#5d5363',
    700: '#473e4d',
    800: '#322a38',
    900: '#221c27',
    950: '#140f18'
};

// Экспортируем сырые брендовые палитры, чтобы конфигуратор темы (песочница)
// мог переиспользовать их без дублирования hex.
export const solarPalettes = {
    orange: solarOrange,
    lime: solarLime,
    wine: solarWine,
    amber: solarAmber
};

export const solarSurfaces = {
    plum: surfaceDark,
    cloud: surfaceLight
};

export const SolarSystoPreset = definePreset(Aura, {
    primitive: {
        // Регистрируем брендовые шкалы как примитивы — на них ссылается семантика ниже.
        orange: solarOrange,
        lime: solarLime,
        wine: solarWine
    },
    semantic: {
        // PRIMARY → оранжевый бренд.
        primary: solarOrange,

        // Семантические статусы (success/info/warn/danger) переопределяем брендом.
        // Используется компонентами Message, Tag, Badge, Toast и т.п.
        success: solarLime,
        info: solarOrange,
        warn: solarAmber,
        danger: solarWine,
        help: solarWine,

        // Скругления — чуть мягче дефолтных Aura, но без «леденцов».
        borderRadius: {
            none: '0',
            xs: '2px',
            sm: '4px',
            md: '8px',
            lg: '10px',
            xl: '14px'
        },

        colorScheme: {
            light: {
                surface: surfaceLight,
                primary: {
                    color: '{primary.500}',
                    contrastColor: '#ffffff',
                    hoverColor: '{primary.600}',
                    activeColor: '{primary.700}'
                },
                highlight: {
                    background: '{primary.50}',
                    focusBackground: '{primary.100}',
                    color: '{primary.700}',
                    focusColor: '{primary.800}'
                },
                // Текст/фон для читаемой светлой админки.
                text: {
                    color: '{surface.800}',
                    mutedColor: '{surface.500}'
                },
                content: {
                    background: '{surface.0}',
                    borderColor: '{surface.200}'
                }
            },
            dark: {
                surface: surfaceDark,
                primary: {
                    // На тёмном фоне берём чуть более светлый оранж (400) — лучше контраст.
                    color: '{primary.400}',
                    contrastColor: '{surface.950}',
                    hoverColor: '{primary.300}',
                    activeColor: '{primary.200}'
                },
                highlight: {
                    background: 'color-mix(in srgb, {primary.400}, transparent 84%)',
                    focusBackground: 'color-mix(in srgb, {primary.400}, transparent 76%)',
                    color: 'rgba(255,255,255,.92)',
                    focusColor: 'rgba(255,255,255,.92)'
                },
                text: {
                    color: 'rgba(255,255,255,.90)',
                    mutedColor: 'rgba(255,255,255,.62)'
                },
                content: {
                    background: '{surface.800}',
                    borderColor: 'rgba(255,255,255,.10)'
                }
            }
        }
    }
});

export default SolarSystoPreset;
