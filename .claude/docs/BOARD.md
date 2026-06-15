# 📋 Доска задач (Project Board)

Визуализация текущих проблем, задач и прогресса проекта Systo.

**Подробный roadmap версий 2.5.0 → 3.0.0** — в `.claude/docs/process/RELEASES.md`
**Технический долг** — в `.claude/docs/TECH_DEBT.md`

---

## 🗺️ Roadmap версий

| Версия | Срок | Главное | Статус |
|--------|------|---------|--------|
| **2.5.0** | 2026-05-29 | Старт версионирования, базовый CI, новые агенты, healthcheck воркера, очистка от Friendly-приложения | ✅ Выпущена |
| **2.5.1** | 2026-05-29 | Baza сидеры/factory, миграции синхронизированы со схемой прода, чистый PHPUnit Baza, spec истории билета | ✅ Выпущена |
| **2.6.0** | 2026-06-12 (дедлайн) | Опции к билетам + новый формат заказа (BREAKING) + миграция + фронт + тесты. **Код в master, тег НЕ поставлен (B-0)** | 🟠 Не затеган |
| **2.7.0-alpha.1** | 2026-06-14 (готов к тегу, ждём go) | Staging-preview: разворот org → admin-only, qr-заказы (read-only API + UI), новая админка Vite+Sakai (PoC) | 🟡 Готов к тегу |
| **2.7.0** | июль 2026 | Промокоды-агрегатор + qr.spaceofjoy.ru SSO (Passport hybrid с JWT) + продолжение переезда админки (фазы 0–3) | ⏳ Запланировано |
| **2.8.0** | начало сентября 2026 | SSL для ноутбука-сканера, offline docker-compose, CD staging + новая роль org (доставка в baza, реестр билетов, дашборды) | ⏳ Запланировано |
| **2.9.0** | сентябрь–октябрь 2026 | Loki + Grafana, audit + воронка покупки, CD prod tag-based + cutover на новую админку | ⏳ Запланировано |
| **3.0.0** | TBD | Laravel 11 + OrderKind VO + 3 Application-сервиса (Laravel 9→11 апгрейд в работе на `feature/laravel-11`) | 🟡 Открытый |

**⚠️ Главное по статусу (2026-06-14):** v2.6.0 функционально готов и в master, но **тег не поставлен** (B-0). Готовится `v2.7.0-alpha.1` (staging-preview) — ждёт **go владельца**. Что нужно для go и блокеры B-0..B-6 — см. `RELEASES.md §4 (recap планов)` и `§5.1 (v2.7.0-alpha.1)`.

---

## 🔄 Текущая работа

**Спринт: разворот org → admin-only + новая админка (Vite + Sakai). Готовится `v2.7.0-alpha.1` (staging-preview).**

Контекст разворота: org становится внутренней admin-only системой (создание билетов + контроль доставки в baza), публичная витрина/продажи уезжают на `qr.spaceofjoy.ru` (отдельный репозиторий/Claude). Источники: память `project_qr_pivot_2026_06_13`, `project_admin_ui_primevue`; спека `.claude/specs/admin-frontend-vite-sakai.md`.

### Ветка в работе

`feat/admin-qr-orders` (на staging, +24 коммита к master). Содержит: backend-эндпоинт qr-заказов, экран в старом фронте, PoC новой админки `AdminFront/`, инфра staging для `/new-admin/`.

### Новые планируемые фичи (направление расширения админки)

| ID | Фича | Оценка | Фаза спеки AdminFront | Целевая версия | Зависимости |
|----|------|--------|------------------------|----------------|-------------|
| **AF-1** | **Продолжение переезда админки на Vite+Sakai** — каркас AdminLayout, UI-фундамент (`DataTablePage`/`useCrud`), перенос существующих CRUD-экранов | L (дробится по экранам) | Фазы 0–3 | v2.7.0 → v2.9.0 | — |
| **AF-2** | **Дашборды со сводными графиками** в админке — продажи + количество билетов (НЕ замена Grafana, сводные графики внутри админки) | M | Фаза 4 (дашборд) | v2.8.0 | данные продаж (qr-заказы + заказы org) |
| **AF-3** | **Универсальный генератор шаблонов** — единый редактор шаблонов для письма и PDF-билета. **Фазы 1–4 done** (на стенде, e2e доказан): модуль `Backend/src/Template/` (Mustache + сущность `Template` + `template_versions`), рендер писем (11 Mailable через трейт `RendersDbTemplate`) и PDF из БД с fallback на blade, импорт blade (`templates:import-blade`), admin-CRUD API `/api/v1/template/*` (превью/версии/откат/палитра), экран редактора в AdminFront. **Фаза 5 в работе:** 28 шаблонов (базовый `pdf` + 7 PDF-билетов + все 20 order/list писем) конвертированы в Mustache и **активны на стенде** (глубокий рендер-тест 133 assertions + e2e); остаётся MJML/кэш `compiled_html` + редкие auth-шаблоны. Спека: `.claude/specs/template-system.md` | L | новый модуль | v2.8.0–v2.9.0 | ✅ модель шаблонов согласована |
| **AF-4** | **Подтверждение доставки билета в baza** — экран/статус «билет доставлен в baza» + ручной retry застрявших | M | Фаза 4 | v2.8.0 | ⚠️ **бэкенд-эндпоинт** статуса sync + retry (открытый вопрос спеки) |
| **AF-5** | **S3-хранилище билетов/PDF** — хранить билеты/PDF в S3-совместимом хранилище отдельно от приложения | M | вне фаз фронта (инфра+backend) | v2.8.0–v2.9.0 | ⚙️ **devops** (выбор S3, ресурсы) + backend (драйвер storage) |
| **AF-6** | **Подтверждение доставки билета на email** — сейчас «слепой SMTP» (лог `sent` = принято SMTP, не доказывает доставку). Переход на транзакционный провайдер с вебхуками (`delivered`/`bounced`/`opened`) + таблица статусов писем + экран «Доставка на почту» + повторная отправка. Парная к AF-4 («билет реально дошёл» = baza + email). | M | вне базовых фаз (backend + админ-экран) | v2.8.0 | ⚙️ аккаунт транзакц. провайдера (под РФ/152-ФЗ: UniOne/Unisender, SendPulse, Mailopost) + backend (драйвер + вебхук-эндпоинт + таблица статусов) |

**Оценка S/M/L:** S ≈ до 1–2 дней, M ≈ 3–5 дней, L ≈ неделя+ (дробится на под-PR).

### Открытые вопросы / блокеры новых фич

- **AF-4** — нужен бэкенд-эндпоинт статуса доставки билет→baza + ручной retry (есть ли уже / проектировать). Блокер фазы 4.
- **AF-5** — выбор S3-совместимого хранилища и ресурсы оцениваются параллельно с devops.
- **AF-6** — сейчас отправка билетов = слепой SMTP без подтверждения доставки. Нужен транзакционный провайдер с вебхуками (RU — под 152-ФЗ) + аккаунт (биллинг владельца). Пара к AF-4.
- **AF-2** — источник данных для графиков: qr-заказы + заказы org (агрегаты по периодам). Не дублировать Grafana (та — для логов/инфры).
- Всё дерево фаз AdminFront (PoC → 0..6) — в `.claude/specs/admin-frontend-vite-sakai.md §6`.

### Что нужно от владельца для go по `v2.7.0-alpha.1`

См. recap в `RELEASES.md §5.1`. Кратко: решение по B-0 (тег v2.6.0), формат версии pre-release, и подтверждение состава staging-preview.

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

### 2026-06-15 — Система шаблонов писем и PDF (AF-3, фазы 1–4)

**Единый редактор писем и PDF-билетов: admin меняет оформление без правки кода и деплоя. На стенде, e2e доказан. Фаза 5 (конвертация blade) — итеративно.**

- 🌿 Ветка: `feat/admin-qr-orders`
- 📜 Спека: `.claude/specs/template-system.md`

**Что вошло (фазы 1–4):**
- **Backend `Backend/src/Template/`** — движок **Mustache** (logic-less, RCE-безопасен), сущность `Template` + таблицы `templates`/`template_versions`. Рендер писем И PDF из БД с **fallback на blade** (неактивный шаблон → рендерится blade). `slug` = имени blade → нулевая миграция привязки.
- **Импорт** текущих blade в БД — artisan `templates:import-blade` (неактивные системные черновики).
- **Письма** — трейт `App\Mail\Concerns\RendersDbTemplate` на **11** order/list-Mailable (оба канала: legacy `order_tickets` + qr-пайплайн).
- **PDF** — `CreatingQrCodeService::createPdf()` рендерит из БД с fallback.
- **API** (все `auth:api` + `admin`): `/api/v1/template/getList`, `getItem`, `create`, `edit`, `activate`, `saveDraft`, `publish`, `versions`, `rollback`, `variables/{slug}`, `preview` (throttle 20/мин). Превью: email → `{html}`, pdf → `application/pdf` через DomPDF, ошибка синтаксиса → 422.
- **Версии/откат** — `publish` снапшотит `body` в `template_versions` (append-only), `rollback` восстанавливает.
- **Палитра переменных** — `PlaceholderCatalog` (sample-фикстуры для превью без ПДн).
- **AdminFront** — Vuex `appTemplate` + `TemplateListView` + `TemplateEditorView` (код + вставка плейсхолдеров кликом + iframe-превью + черновик/публикация + версии).

**Фаза 5 (в работе, 2026-06-15):** все order/list **письма (20)** + **PDF-билеты (7)** + базовый `pdf` конвертированы в Mustache и **активны на стенде** (`templates:sync-converted`). Проверка: полный PHPUnit 313 зелёных + глубокий рендер-тест `TemplateConversionRenderTest` (4 теста / 133 assertions: секции раскрываются с данными и схлопываются без, экранирование, raw QR-data-uri, `year`) + e2e-рендер из БД на стенде. Остаётся: MJML/кэш `compiled_html` + редкие auth-шаблоны (`activate`/`newUser`/`passwordResets` — их Mailable пока без трейта `RendersDbTemplate`). blade — safety net ≥1 фестиваль.

### 2026-06-14 — Разворот org → admin-only + qr-заказы + PoC новой админки

**Сессия в рамках подготовки `v2.7.0-alpha.1` (staging-preview). Тег НЕ поставлен — ждём go владельца.**

- 🌿 Ветка: `feat/admin-qr-orders` (на staging, +24 коммита к master)
- 📜 Спека: `.claude/specs/admin-frontend-vite-sakai.md` (переезд фронта на Vite + PrimeVue Sakai)
- 📜 Спека первого экрана: `.claude/specs/admin-qr-orders-prompt.md`
- 🔗 Превью новой админки: `https://staging.spaceofjoy.ru/new-admin/`

**Что вошло:**
- **Backend `POST /api/v1/qrOrder/getList`** (admin, read-only) — фильтры + пагинация + total. Покрытие: 12 PHPUnit.
- **Старый FrontEnd:** экран `/admin/qr-orders` (PrimeVue 4: server-side DataTable + фильтры + Dialog + Timeline) + пункт меню в админ-навигации.
- **Новая админка AdminFront (greenfield):** Vite + PrimeVue Sakai, фирменный стиль Solar Systo, светлая тема по умолчанию. PoC — 1 рабочий экран (qr-заказы) против живого API.
- **Инфра staging:** сервис `node-admin-staging` + nginx-route `/new-admin/` + шаг Vite-сборки в `deploy-staging.yml` (сборка под root, `--base=/new-admin/`).

**Контекст разворота:** org → внутренняя admin-only система (создание билетов + контроль доставки в baza); публичная витрина/продажи → `qr.spaceofjoy.ru`. Память: `project_qr_pivot_2026_06_13`, `project_admin_ui_primevue`.

**Всплывшие техдолги (см. TECH_DEBT.md):** B-0 (v2.6.0 без тега → TD-31), B-5 (CI не собирает AdminFront → TD-32), пред-существующий баг staging `auth:api`→500 → TD-33, два фронта в репо до cutover → TD-34.

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
- **Спецификации v2.6.0** (готовы 2026-05-30 после встречи):
  - `.claude/specs/ticket-options.md` — опции к билетам (business-analyst, 579 строк)
  - `.claude/specs/order-format-architecture.md` — архитектура нового формата заказа (tech-lead, 719 строк)
  - `.claude/specs/qr-sso-security.md` — security-обзор Passport SSO (security-engineer, 562 строки, использовать в v2.7.0)
  - **Итоги встречи и финальный scope:** `.claude/meetings/2026-05-30/RESULTS.md`
