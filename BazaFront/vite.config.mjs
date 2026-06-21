import { fileURLToPath, URL } from 'node:url';

import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vite';
import { VitePWA } from 'vite-plugin-pwa';

// Версия сборки в шапке: из CI (VITE_BUILD_VERSION), иначе метка времени сборки.
// Нужна, чтобы билетёр на КПП видел, не застрял ли он на устаревшей оболочке.
const buildVersion =
    process.env.VITE_BUILD_VERSION || new Date().toISOString().slice(0, 16).replace('T', ' ');

// PWA контроля входа КПП. base переопределяется при сборке: `--base=/baza/` (staging).
// https://vitejs.dev/config/
export default defineConfig({
    define: {
        'import.meta.env.VITE_BUILD_VERSION': JSON.stringify(buildVersion)
    },
    plugins: [
        vue(),
        VitePWA({
            // 'prompt' — НЕ autoUpdate: оболочку нельзя молча перезагружать посреди
            // потока гостей на КПП (решение архитектора/PM). Показываем кнопку «обновить».
            registerType: 'prompt',
            // dev-режим без SW (чтобы не мешал разработке), включается только в проде.
            devOptions: { enabled: false },
            includeAssets: ['favicon.ico'],
            manifest: {
                name: 'Вход · Solar Systo',
                short_name: 'Вход КПП',
                description: 'Контроль входа на КПП — офлайн-first',
                start_url: '.',
                scope: '.',
                display: 'standalone',
                orientation: 'portrait',
                background_color: '#f5f6f8',
                theme_color: '#ff7900',
                icons: []
            },
            workbox: {
                // Precache оболочки приложения (хэшированная статика Vite — immutable).
                globPatterns: ['**/*.{js,css,html,svg,woff,woff2,ttf,eot}'],
                navigateFallback: 'index.html',
                runtimeCaching: [
                    {
                        // Ответы впуска/скана/поиска НИКОГДА не кэшируем — иначе покажет
                        // устаревший статус билета. Офлайн-сверка идёт по IndexedDB-снимку (PR-3).
                        urlPattern: ({ url }) => url.pathname.startsWith('/api/'),
                        handler: 'NetworkOnly'
                    }
                ]
            }
        })
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./src', import.meta.url))
        }
    },
    server: {
        host: true,
        watch: { usePolling: true }
    }
});
