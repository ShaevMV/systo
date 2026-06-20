# BazaFront — offline-first PWA контроля входа на КПП (Ф5)

Greenfield рядом со старым Blade (Strangler). Раздаётся под `/baza/` на домене Baza (`vhod.*`).
Auth — **session-куки Baza + CSRF** (онлайн), офлайн-вход по PIN (PR-6). Не JWT.

- Стек: Vite + Vue 3 + PrimeVue (Aura).
- Главный экран = сразу сканер (решение владельца).
- План: `.claude/specs/baza-f5-pwa.md` (карта PR-1…PR-10).

## Скрипты
- `npm run dev` — дев-сервер.
- `npm run build` — прод-сборка (на staging: `npm run build -- --base=/baza/`).
