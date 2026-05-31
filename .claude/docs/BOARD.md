# 📋 Доска задач (Project Board)

Визуализация текущих проблем, задач и прогресса проекта Systo.

**Подробный roadmap версий 2.5.0 → 3.0.0-alpha** — в `.claude/docs/process/RELEASES.md`
**Технический долг** — в `.claude/docs/TECH_DEBT.md`

---

## 🗺️ Roadmap версий

| Версия | Срок | Главное | Статус |
|--------|------|---------|--------|
| **2.5.0** | 2026-05-29 | Старт версионирования, базовый CI, новые агенты, healthcheck воркера, очистка от Friendly-приложения | ✅ Выпущена |
| **2.5.1** | 2026-05-29 | Baza сидеры/factory, миграции синхронизированы со схемой прода, чистый PHPUnit Baza, spec истории билета | ✅ Выпущена |
| **2.6.0** | 🔥 **до 2026-06-12** | XL-релиз: опции к билетам + новый формат заказа (BREAKING) + миграция + промокоды-агрегатор + qr.spaceofjoy.ru SSO (Passport) | 🔥 В работе |
| **2.7.0** | начало сентября 2026 | SSL для ноутбука-сканера, offline docker-compose, CD staging | ⏳ Запланировано |
| **2.8.0** | сентябрь–октябрь 2026 | Loki + Grafana, audit + воронка покупки, CD prod tag-based | ⏳ Запланировано |
| **3.0.0** | TBD | Laravel 11 + OrderKind VO + 3 Application-сервиса | 🟡 Открытый |

**🔥 Дедлайн 2026-06-12** — старт продаж осеннего фестиваля. **Всё одним релизом v2.6.0** (XL) — интеграционная ветка `feat/v2.6.0-fall-festival` + sub-ветки. Источник scope: `.claude/meetings/2026-05-30/RESULTS.md`.

---

## 🔄 Текущая работа

**🔥 Спринт v2.6.0 (XL) — дедлайн 2026-06-12 (старт продаж осеннего фестиваля).**

Интеграционная ветка `feat/v2.6.0-fall-festival` + sub-ветки. Внутри 6 блоков работ:

### Блок 1 — Опции к билетам
- Новая сущность `Option` (модуль `Backend/src/Option/`)
- Привязка к ticket_type, своя цена, активность
- Админ-CRUD + UI

### Блок 2 — Новый формат заказа (BREAKING)
- `ticket_type_id` и `promo_code` переезжают с уровня заказа на уровень гостя
- Новое поле `guests[].options[]` — массив UUID опций
- Перестройка домена `OrderTicket` + расчёта цены
- Перестройка фронта формы покупки
- Миграция существующих данных в БД

### Блок 3 — Промокоды-агрегатор
- История использования (новая таблица `promo_code_usage`)
- Привязка к создателю (`created_by_user_id`)
- Расширение прав: все роли кроме `guest`
- UI «мои промокоды» для не-админских ролей

### Блок 4 — qr.spaceofjoy.ru SSO
- **Laravel Passport** (зафиксировано на встрече) как OAuth2 Provider
- qr.spaceofjoy.ru как OAuth2 Client (наш внутренний сервис партнёра)
- Выделенная роль `qr_service` в `AccountRoleHelper`
- SSO flow: бесшовная авторизация Systo → qr.spaceofjoy.ru

### Блок 5 — Интеграция с qr.spaceofjoy.ru API
- Отправка заказов с auto-payment (уже частично готово)
- Создание промокодов (уже частично готово)
- Получение типов билетов с опциями (новое)

### Блок 6 — Тесты + миграция данных
- PHPUnit для новой логики расчёта цены
- Миграция всех существующих заказов в новый формат (бэкап обязателен)
- Регресс на staging

**Следующий шаг:** запустить business-analyst для детальной спеки опций к билетам + новый формат заказа.

---

## 🔴 Критично (High Priority)

| Задача | Ответственный | Куда |
|--------|---------------|------|
| **Race Condition воркера** — внедрить Healthcheck в docker-compose | devops-engineer | v2.5.0 ✅ |
| **Починка Unit-тестов** (PDO/Connection ошибки) | auto-tester | v2.5.0 + v2.5.1 ✅ |
| **Очистка документации от Friendly-приложения** | technical-writer | v2.5.0 |
| **Обновление Laravel 9 → 11** (заблокировано до 1 июня) | tech-lead | v2.8.0 + v2.9.0 |

## 🟡 Важно (Medium Priority)

| Задача | Ответственный | Куда |
|--------|---------------|------|
| **Sentry filter** — фильтрация шума («Промокод пустой», «DB disconnect») | devops-engineer | TECH_DEBT |
| **Альтернатива Telegram-каналу** — SMTP + чат-виджет | business-analyst | TECH_DEBT |
| **152-ФЗ compliance** — Политика+согласие уже есть, ждём помощь с РКН | security-engineer | TECH_DEBT |
| **Очистка места на VPS** — Docker логи + PDF билетов | devops-engineer | TECH_DEBT |
| **Рефакторинг Shared в Baza** — бардак в коде | tech-lead | TECH_DEBT |
| **Логирование всех действий + воронка покупки** (единый поток) | devops-engineer | v2.7.0 |

## 🟢 Low Priority

| Задача | Ответственный | Куда |
|--------|---------------|------|
| **Mobile UX — отдельная вёрстка** (Android+iPhone) | ux-ui-designer | TECH_DEBT |
| **Автоматизация бэкапов** (сейчас ручные mysqldump → к себе) | devops-engineer | TECH_DEBT |

---

## ✅ Сделано

### 2026-05-29 — v2.5.1 «Baza-сидеры и чистый PHPUnit»

**Патч-релиз вечером того же дня. TD-2 закрыт.**

- 🏷️ Тег: `v2.5.1`
- 📜 [CHANGELOG.md §2.5.1](../../CHANGELOG.md)
- 🌿 Ветки: `feat/v2.5.1-baza-seeders` (#36 — код), `chore/backport-v2.5.0-changelog` (#37 — backport CHANGELOG v2.5.0), `chore/release-v2.5.1-docs` (#38 — финальная документация v2.5.1)
- 📦 Релиз: GitHub Release (см. https://github.com/ShaevMV/systo/releases/tag/v2.5.1)

**Что вошло:**
- Чистый PHPUnit Baza: было 2 skipped + 1 risky → стало **8 тестов, 10 ассертов, 0 проблем**
- `ChangesFactory` + `ChangesTestDataSeeder` + `RefreshDatabase` в `ChangesTest`
- Отдельная тестовая БД `baza_test` (фиксация в `phpunit.xml`)
- Шаг `php artisan migrate --force` в CI job `test-baza`
- 2 миграции синхронизации со схемой прода: `festival_id` в `el_tickets`, `festival_id` + `count_auto_tickets` в `changes` (с `Schema::hasColumn()` — на проде безопасно)
- Спецификация фичи «История билета» (`.claude/specs/ticket-history.md`, 465 строк) — кандидат в v2.7.0/v2.8.0

**Заметки по релизу:**
- Из-за неправильно смерженного PR #36 (вместо PR #37 backport) в истории `master` остался WIP-коммит `29ea36e4`. Решение: backport-PR #37 + catch-up PR #38 с финальной документацией.

### 2026-05-29 — v2.5.0 «Старт версионирования» 🎉

**Первая официальная версия проекта. Фундамент процесса релизов и инфраструктуры.**

- 🏷️ Тег: `v2.5.0`
- 📜 [CHANGELOG.md §2.5.0](../../CHANGELOG.md)
- 🌿 PR: feat/v2.5.0-foundation → master (#35), 16 коммитов
- 📦 Релиз: GitHub Release (см. https://github.com/ShaevMV/systo/releases/tag/v2.5.0)

**Что вошло:**
- Процесс релизов: `RELEASES.md` (SemVer + branching + DoD + roadmap), `CHANGELOG.md`
- CI на GitHub Actions: 7 параллельных job (lint Backend/Baza/Frontend, build, audit, PHPUnit Backend/Baza), MySQL service, кэш, триггеры push + PR
- husky + commitlint (мягкий warning, conventional commits)
- Makefile: 30+ целей с группами + `make help`
- Агенты команды: `scrum-master`, `security-engineer`
- Материалы к встрече с организаторами 2026-05-30
- Очистка документации от Friendly-приложения (и косвенно от List)
- TD-1 закрыт (Race Condition воркера на проде через healthcheck)
- TD-2 закрыт частично (Backend 55 тестов 0 ошибок, Baza 7 тестов 0 ошибок 2 skipped)
- TD-3 закрыт (cleanup от Friendly + List)
- Починка docker-compose.prod.yml (удалены мёртвые phpFriendly, phpList, database)
- Починка Baza/composer.lock (endroid/qr-code добавлен)

### 2026-05-15 — Авто-одобрение заказа

**Заголовок `AutoPayment` на `POST /api/v1/order/create`.**
- Конфиг: `AUTO_PAYMENT_TOKEN` в `.env.example` + `config/services.php → auto_payment.token`
- `ActorType::AUTO_PAYMENT` (`auto_payment`) добавлен в `Backend/src/History/Domain/ActorType.php`
- `TypesOfPaymentDto::isBilling()` — геттер для проверки биллингового способа оплаты
- `OrderTickets::create()` — проверка заголовка `AutoPayment` до создания: невалидный токен → `403`, валидный + не-биллинговый способ оплаты → после `createAndSave` вызывается `ChangeStatus` с `Status::PAID`
- Сравнение токенов через `hash_equals` (защита от timing-attack)
- Документация: `API.md`, `BUSINESS_RULES.md`, `DOMAIN.md`
- 🌿 ветка `feat/auto-payment-token`

### 2026-05-10 — CRUD для волн цен типа билета

**`ticket_type_price`.**
- Backend модуль `Backend/src/TicketTypePrice/` (DTO, Repository + Interface, Application, Create/Edit/Delete/GetList/GetItem handlers)
- Контроллер `TicketTypePriceController` с FormRequest-валидацией
- Роуты `/api/v1/ticketTypePrice/*` — read публичный, write только `auth:api + admin`
- SoftDeletes в `TicketTypesPriceModel` + кастинг `price`/`before_date`
- Защита от дурака: `price > 0` и `< 1 000 000`, `before_date` не в прошлом, `ticket_type_id` exists
- Frontend: Vuex `TicketTypePriceModule` + компонент `TicketTypePriceList.vue`, встроен в форму редактирования типа билета
- 🌿 ветка `feat/ticket-type-price-crud`

### 2026-05-04 — Заказы-списки + сущность Локации

**Третий тип заказа (для куратора).**
- 3 миграции (`locations` table, `location_id`/`curator_id` в `order_tickets`, nullable полей)
- 4 новых статуса + матрица переходов в `Status.php`, роль `curator` в `AccountRoleHelper`
- Модуль `Backend/src/Location/` (DTO, Repository, Application, CRUD контроллер, роуты)
- Расширение OrderTicket: `createList`/`toApproveList`/`toCancelList`/`toDifficultiesAroseList`, `OrderTicketDto::fromStateForList`, фильтры репозитория
- 3 новых Mailable + blade: `OrderListApproved`, `OrderListCancel`, `OrderListDifficultiesArose`
- Frontend: Vuex `LocationModule`, компоненты CRUD локаций, форма `BuyTicketLists`, `OrderLists/OrderList+FilterOrder`, views для куратора и admin/manager, роуты + меню, флаг `isCurator`

### Более ранние работы

- ✅ Настройка команды агентов (10 агентов, политики, память)
- ✅ Анкета нового пользователя (тип по коду `new_user`)
- ✅ Исправление опечаток (Friendly, ChangeStatus/Role)
- ✅ Создание Project Memory (контекст и предпочтения)
- ✅ Создание Project Board (визуализация задач)
- ✅ История заказов (модуль History, Event Sourcing, UI для admin)
- ✅ **Friendly-приложение удалено** — осталось 3 приложения в монорепо

---

## 📝 Заметки

- Пользователь признался что не читает BOARD.md напрямую, но соглашается что доска нужна для агентов и истории. **Главное — поддерживать актуальность.**
- Подробные правила версионирования и план каждой версии — в `RELEASES.md` (этот файл — только верхнеуровневая сводка).
- Технический долг с детализацией — в `TECH_DEBT.md`.
- **Новая спецификация-черновик**: `.claude/specs/ticket-history.md` — «История билета» + смена владельца + sync Baza↔Backend событий сканирования. Кандидат в v2.7.0–v2.8.0. Подготовлен business-analyst, ждёт ответов пользователя на 10 открытых вопросов перед стартом разработки.
- **Аудит документации**: `.claude/specs/docs-audit-2026-05-29.md` — обнаружено ~52 расхождения между `.claude/docs/` и реальным кодом. Главное: модуль `Backend/src/Auto/` не описан, роль `pusher_curator` не упомянута, 5 эндпоинтов и 6 history-событий без документации. Привязано к v2.6.0 (TD-23).
