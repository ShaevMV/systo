# Технический долг (Tech Debt)

Список улучшений, которые не критичны сейчас, но важны для будущего.

---

| Приоритет | Описание | Кто предложил | Дата | Срок |
|-----------|----------|---------------|------|------|
| 🔴 High | Обновление Laravel 9 → 11 | Tech Lead | 2026-04-04 | **После 1 июня** |
| 🔴 High | Реальная фикс Race Condition воркера (Healthchecks) | Tech Lead | 2026-04-04 | **До фестиваля** |
| 🔴 High | Восстановление и починка Unit-тестов (PDO/Connection) | Auto-Tester | 2026-04-04 | ASAP |
| 🔴 High | Исправить фильтрацию `festivalId` в Vuex store OrderModule | Frontend Helper | 2026-04-12 | Сделано |
| Medium | Рефакторинг использования Shared кода в Baza | Tech Lead | 2026-04-04 | Когда будет время |
| Medium | Покрыть тестами контроллеры (HTTP слой) | Auto-Tester | 2026-04-04 | — |
| Medium | Логирование действий админов (смена статусов, удаление) | DevOps | 2026-04-04 | — |
| Medium | Настроить CI/CD пайплайн (GitHub Actions) | DevOps | 2026-04-04 | — |
| Medium | Миграция Bootstrap 4 → 5 (модалки через vanilla JS) | Frontend Helper | 2026-04-12 | — |
| Medium | Единый паттерн для `Order::none()` в фильтрах списков | Tech Lead | 2026-05-10 | — |
| Low | Миграция на Vue Router lazy loading | Frontend Helper | 2026-04-04 | — |
| Low | Заменить jQuery модалки на нативные Vue | Tester | 2026-04-04 | — |
| Low | Единый Response Interceptor для Axios | Frontend Helper | 2026-04-04 | — |
| Low | Тёмная тема (хотят пользователи) | UX/UI Designer | 2026-04-04 | — |
| Low | TypeScript для фронтенда (отложено) | Tech Lead | 2026-04-04 | Когда-нибудь |

---

**Правило:** PM напоминает об этом файле раз в 5-10 коммитов.

**Критично до 1 июня:** Только фиксы багов, никаких Laravel-апдейтов.

---

## Заметки по задачам

### Единый паттерн `Order::none()` в фильтрах списков (2026-05-10)
**Проблема:** `Order::fromState($data)` создаёт `new OrderType($data[$key])`, который кидает `InvalidArgumentException` на чужих значениях. Контроллеры (`LocationController`, `TicketTypeController`, новые `TicketTypePriceController`) обрабатывают это по-разному: где-то нет защиты вовсе → 500, где-то локальный try/catch с fallback на `Order::none()`, где-то FormRequest валидирует `orderBy.*`.

**Что обдумать:**
- Сделать `Order::fromStateSafe()` в Shared (или утилиту/трейт) — один источник truth, возвращает `Order::none()` на невалидных данных.
- Либо ввести базовый `ListRequest` (FormRequest) с общими правилами `orderBy.*` ∈ `{asc, desc}` и наследовать от него.
- Либо изменить `OrderType` так, чтобы он не кидал, а помечал значение как `none()` (опаснее — поведение Shared).

**Где сейчас плодится дубль:**
- `TicketTypePriceController::getList()` — try/catch + Order::none()
- `LocationController::getList()` — без защиты
- `TicketTypeController::getList()` — без защиты
- много других `*Controller::getList()` в проекте

**Решение принять до:** следующего модуля с публичным `getList`-эндпоинтом, чтобы не дублировать рецепт.
