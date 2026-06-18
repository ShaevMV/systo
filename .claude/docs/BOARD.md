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
| **AF-3** | **Универсальный генератор шаблонов** — единый редактор шаблонов для письма и PDF-билета. **Фазы 1–4 done** (на стенде, e2e доказан): модуль `Backend/src/Template/` (Mustache + сущность `Template` + `template_versions`), рендер писем (11 Mailable через трейт `RendersDbTemplate`) и PDF из БД с fallback на blade, импорт blade (`templates:import-blade`), admin-CRUD API `/api/v1/template/*` (превью/версии/откат/палитра), экран редактора в AdminFront. **Фаза 5 почти готова:** **32 шаблона** (базовый `pdf` + 7 PDF-билетов + 20 order/list писем + 4 auth/анкетных) конвертированы в Mustache и **активны на стенде** (глубокий рендер-тест 154 assertions + e2e); остаётся только MJML/кэш `compiled_html`. **Привязки по событию done (2026-06-17, PR #77):** ось `event` в `template_bindings` (15 событий, каталог `EmailEvent`), резолвер учитывает событие (`event` > `ticket` > `order` > `festival`), эндпоинт `templateBinding/events`. Спеки: `.claude/specs/template-system.md`, `.claude/specs/email-delivery-system.md` (Часть 1) | L | новый модуль | v2.8.0–v2.9.0 | ✅ модель шаблонов согласована |
| **AF-4** | **Подтверждение доставки билета в baza** — экран/статус «билет доставлен в baza» + ручной retry застрявших | M | Фаза 4 | v2.8.0 | ⚠️ **бэкенд-эндпоинт** статуса sync + retry (открытый вопрос спеки) |
| **AF-5** | **S3-хранилище билетов/PDF** — хранить билеты/PDF в S3-совместимом хранилище отдельно от приложения | M | вне фаз фронта (инфра+backend) | v2.8.0–v2.9.0 | ⚙️ **devops** (выбор S3, ресурсы) + backend (драйвер storage) |
| **AF-6** | **Подтверждение доставки билета на email** — **частично закрыт (2026-06-17, PR #77):** есть таблица статусов писем (`email_messages`), внутренние статусы пути (`queued→sending→sent`/`failed` + `error` = «где застряло»), ретрай из админки (`emailDelivery/resend`), экран «Доставка писем» и пиксель прочтения (`opened`, за флагом `MAIL_OPEN_TRACKING`, 152-ФЗ). **Остаётся:** транзакционный провайдер с вебхуками для `delivered`/`bounced` (драйвер + вебхук-эндпоинт). Парная к AF-4 («билет реально дошёл» = baza + email). | M | вне базовых фаз (backend + админ-экран) | v2.8.0 | ⚙️ аккаунт транзакц. провайдера (под РФ/152-ФЗ: UniOne/Unisender, SendPulse, Mailopost) + backend (драйвер + вебхук-эндпоинт) |
| **AF-7** | **Festival → AggregateRoot + история** — ✅ **сделано (2026-06-18):** агрегат `Festival` в новом модуле `Backend/src/Festival/Domain/` + `HasHistory`, события `festival_created/edited/deleted` в `domain_history` (`actor_type=user`), `GET /api/v1/festival/getHistory/{id}` (admin). 405 тестов зелёных. **Остаётся** (отдельный рефакторинг): перенос CRUD/репозитория/DTO из `Order/OrderTicket/` в модуль `Festival/`. | M | новый backend-модуль | ✅ done | CRUD фестиваля (✅ done) |
| **AF-8** | **Перенос CRUD-экранов в AdminFront** — festival / ticketType / option / typesOfPayment по образцу `DataTablePage`/`useCrud`. Преимущественно фронт; бэкенд CRUD по этим сущностям есть. | L (по экранам) | AF-1 (фазы 0–3) | v2.7.0–v2.9.0 | — |
| **AF-9** | **Привязка шаблонов писем/PDF по типу оплаты** — ✅ **backend сделан (2026-06-18):** ось `types_of_payment_id` в `template_bindings` (миграция `2026_06_18_130000`), резолвер учитывает payment-type (вес 16 — сильнейший override), проброс в выдачу билета (email/PDF), легаси `types_of_payment.email` оставлен fallback. 410 тестов зелёных. **Остаётся:** UI-селектор в экране «Привязки шаблонов» (AF-8). | M | расширение AF-3 | ✅ backend done | AF-3 (✅ template_bindings) |
| **AF-10** | **Спец-письмо внешних продавцов с адресом магазина** — ✅ **закрыт через AF-9 (2026-06-18):** структурные поля/плейсхолдеры НЕ нужны. Под каждый тип оплаты админ создаёт отдельный email-шаблон (редактор AF-3) и пишет адрес магазина в теле; привязка через ось `types_of_payment_id` (AF-9). Остаётся только UI-селектор в экране привязок (AF-8). | S | через AF-9 + редактор AF-3 | ✅ done (кода не требует) | AF-9 |

> **Детальный план AF-7..AF-10 + открытые вопросы:** `.claude/specs/festival-aggregate-payment-templates-plan.md` (черновик, ждёт ответов владельца по 5 вопросам: адрес магазина — поля/URL; привязка к типу оплаты vs продавцу; festival-модуль; вес оси payment-type; судьба легаси `types_of_payment.email`).

**Оценка S/M/L:** S ≈ до 1–2 дней, M ≈ 3–5 дней, L ≈ неделя+ (дробится на под-PR).

### Открытые вопросы / блокеры новых фич

- **AF-4** — нужен бэкенд-эндпоинт статуса доставки билет→baza + ручной retry (есть ли уже / проектировать). Блокер фазы 4.
- **AF-5** — выбор S3-совместимого хранилища и ресурсы оцениваются параллельно с devops.
- **AF-6** — частично закрыт (PR #77): внутренние статусы письма (`queued→sending→sent`/`failed`), ретрай из админки и пиксель прочтения уже есть. Остаётся транзакционный провайдер с вебхуками (RU — под 152-ФЗ) для `delivered`/`bounced` + аккаунт (биллинг владельца). Пара к AF-4.
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

### 2026-06-17 — Система отправки писем по шаблонам (AF-3 событийные привязки + AF-6 частично)

**Привязка шаблона по событию + контроль пути письма «дошло / где застряло» + весь путь qr-заказа (приём → билеты → письма → история). PHPUnit 381 зелёный, на стенде.**

- 🌿 Ветка: `feat/qr-order-pipeline-view` (несёт все 5 фаз)
- 🔀 PR: #77 (`feat(email): система писем по шаблонам — привязки по событию, трекинг доставки, весь путь qr (Ф1–Ф5)`)
- 📜 Спека: `.claude/specs/email-delivery-system.md`

**Что вошло (Ф1–Ф5):**
- **Ф1 — привязки шаблонов по событию.** Каталог из **15 событий** `Backend/src/EmailDelivery/Domain/EmailEvent.php` (`order_paid`, `order_cancel`, `list_approved`, `user_registered`, `password_reset`, `invite`, `questionnaire` и т.д. → `defaultSlug` + label). Поле `event` в `template_bindings` (миграция `2026_06_17_120000`, `NULL` = wildcard). Резолвер `TemplateBinding` учитывает ось `event` (вес специфичности 8; `event` > `ticket` > `order` > `festival`). Эндпоинт `GET /api/v1/templateBinding/events` (каталог для селектора), `create/edit` принимают `event` (валидация `EmailEvent::isValid`). Дефолт остаётся на kind (email/pdf).
- **Ф2 — трекинг доставки.** Модуль `Backend/src/EmailDelivery/` (пассивная сущность, как `QrOrder`/`Location`, БД только в репозитории). Таблица `email_messages` (миграция `2026_06_17_130000`: `event`, `recipient`, `status`, `attempts`, `error`, `source` [`qr_pipeline`/`qr_intake`/`org_event`], `aggregate_type`/`aggregate_id`, `festival_id`, `tracking_token` UNIQUE, `provider_message_id`, `meta` json, `mailable` longtext, `sent_at`/`opened_at`). VO `EmailStatus` — машина `queued→sending→sent→delivered→opened`, `→failed`, `sent/delivered→bounced`, ретрай `failed/bounced→queued`. `MailDispatcher::send(event, EmailContext, Mailable): Uuid` → `email_messages(queued)` + history(`email_queued`) + `SendEmailJob::dispatch`. `SendEmailJob` (`tries=3`, `backoff [30,120,600]`): `queued→sending→sent/failed`, читает Mailable из БД (колонка `mailable`, base64+serialize) → повтор = re-dispatch по id. Канал логов `mail_delivery`. Админ-API `POST /api/v1/emailDelivery/getList` (whitelist `recipient`/`status`/`event`/`source`/`festival_id`/`aggregate_id` + пагинация), `getItem/{id}` (+ история), `resend/{id}` — все `auth:api` + admin (ПДн).
- **Ф3 — пиксель прочтения.** `GET /api/v1/mail/open/{token}.gif` (публичный, `throttle:120,1`) → 200 image/gif, помечает `opened` (идемпотентно, только из `sent`/`delivered`). За флагом `config mail_delivery.open_tracking` (env `MAIL_OPEN_TRACKING`, default `false` — 152-ФЗ, ревью security).
- **Ф4 — приём писем от витрины qr (S2S).** `POST /api/v1/emailNotification/send` (middleware `qr.ingest`, заголовок `X-QR-Token`) для не-заказных писем (регистрация/сброс пароля), инициированных на qr. Контракт `{event, email, vars{}, festival_id?, order_type?, ticket_type_id?, subject?, aggregate_id?, external_id?}`, идемпотентность по `external_id`. Mailable `App\Mail\GenericTemplatedMail` (slug + subject + vars, трейт `RendersDbTemplate`).
- **Ф5 — весь путь qr-заказа.** `GET /api/v1/qrOrder/getTicketPdf/{id}` (ссылки на PDF билетов), `GET /api/v1/qrOrder/getPipeline/{id}` (заказ + билеты с pdf_url + история шагов + письма) — оба admin. Шаги пайплайна выдачи пишутся в `domain_history` как `step_<имя шага>` (`step_create_tickets`/`step_send_order_email`/`step_push_to_baza`/`step_send_telegram`/`step_create_live_tickets`/`step_link_live`/`step_send_list_email`/`step_send_live_email`).

**История/акторы:** новый `aggregate_type='email'` в `domain_history` (`email_queued`/`email_sending`/`email_sent`/`email_failed`/`email_opened` через `EmailLifecycleEvent`). Письма от qr пишутся `actor_type=qr`, системные — `system`.

**Проверка:** PHPUnit Backend **381 зелёный** (unit резолвера/`EmailStatus`/`EmailEvent`, feature dispatcher/job/resend, пиксель, S2S-идемпотентность, `getTicketPdf`/`getPipeline`).

**Отложено:** старые org-письма (отмена/изменение через `ProcessUserNotification*`) пока идут **мимо** диспетчера — будут подключены отдельно. `delivered`/`bounced` требуют транзакционного провайдера с вебхуками (AF-6, остаётся).

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

**Фаза 5 (2026-06-15):** **32 шаблона** конвертированы в Mustache и **активны на стенде** (`templates:sync-converted`): 20 order/list писем + 7 PDF-билетов + базовый `pdf` + 4 auth/анкетных (`passwordResets`/`newUser`/`invate`/`questionnaire` — их Mailable подключены к трейту `RendersDbTemplate`). Проверка: полный PHPUnit **318 зелёных** + глубокий рендер-тест `TemplateConversionRenderTest` (5 тестов / 154 assertions: секции раскрываются с данными и схлопываются без, экранирование, raw QR-data-uri, `year`, auth-подстановки) + e2e-рендер из БД на стенде. Остаётся: MJML/кэш `compiled_html` (мёртвые blade `activate`/`notification`/`testingAnswers` не трогали — их ни один Mailable не рендерит). blade — safety net ≥1 фестиваль.

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
