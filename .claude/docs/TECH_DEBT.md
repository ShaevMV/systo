# Технический долг (Tech Debt)

Список улучшений, которые не критичны сейчас, но важны для будущего.

**Связанные документы:**
- Roadmap версий — `.claude/docs/process/RELEASES.md`
- Текущая работа — `.claude/docs/BOARD.md`

---

## 🔴 High Priority

| ID | Описание | Кто ведёт | Куда направлено |
|----|----------|-----------|-----------------|
| ✅ TD-1 | **Race Condition воркера** — закрыт в v2.5.0 (healthcheck mysql + worker `depends_on: service_healthy` в prod-compose). | devops-engineer | ✅ **v2.5.0** |
| ✅ TD-2 | **Починка PHPUnit-тестов** — закрыт в v2.5.0 (Backend 55/0) + v2.5.1 (Baza 8/0/0 ассертов). Чистый прогон через `baza_test` БД + фикстуры. | auto-tester | ✅ **v2.5.0 + v2.5.1** |
| ✅ TD-3 | **Очистка документации от Friendly-приложения** — закрыт в v2.5.0. Friendly как тип заказа (роль `pusher`, поле `friendly_id`) сохранён в Backend. | technical-writer | ✅ **v2.5.0** |
| TD-4 | **Обновление Laravel 9 → 11** на всех 3 приложениях (Backend, Baza, FrontEnd). **В работе** на ветке `feature/laravel-11` инкрементально (Backend 9→10→11, Baza 9→11 — оба на framework 11.54.0; см. память `project_laravel_upgrade_2026_06_14`). Staging-only до фестиваля, прод — после продаж. | tech-lead | **v2.8.0 (staging) + v3.0.0 (прод)** |
| TD-31 | **v2.6.0 не затеган (B-0)** — scope v2.6.0 (опции к билетам + новый формат заказа BREAKING + миграция + фронт) влит в master, но тег `v2.6.0` НЕ поставлен, а `CHANGELOG.md [Unreleased]` помечен пустым. Блокирует постановку тега `v2.7.0-alpha.1` (дыра в истории релизов). Решение: ретро-тег `v2.6.0` на нужный коммит + заполнить `CHANGELOG.md §2.6.0`. Требует **go владельца** (теги/Release ставит только владелец). Детали — `RELEASES.md §5.1 → B-0`. | scrum-master | **Перед тегом v2.7.0-alpha.1** |

---

## 🟡 Medium Priority

| ID | Описание | Кто ведёт | Триггер активации |
|----|----------|-----------|-------------------|
| TD-5 | **Sentry filter: фильтрация шума** — топовые повторяющиеся ошибки: «Промокод пустой» (валидация), «слетает подключение к БД» (связано с TD-1). Экономия лимита 5000/мес | devops-engineer | Когда лимит Sentry упрётся в потолок |
| TD-6 | **Альтернатива Telegram-каналу** — Telegram блокируется в РФ. Расширение SMTP как primary канал уведомлений + чат-виджет на сайте (Tawk.to / Crisp). Telegram-бот анкет — сторонний сервис, доступа сейчас нет, принимаем как чёрный ящик | business-analyst | Когда жалоб на блокировку Telegram станет больше / появится доступ к боту |
| TD-7 | **152-ФЗ compliance** — Политика конфиденциальности на сайте есть, согласие на обработку персданных при оформлении заказа/анкеты есть. Не хватает: регистрация в Роскомнадзоре как оператора персданных (товарищ обещал помочь) | security-engineer | Когда товарищ поможет с РКН / появятся требования от организаторов |
| TD-8 | **Очистка места на VPS** — Логи Docker и PDF билетов съедают место. Настроить ротацию docker-логов, автоматическую чистку старых PDF (после фестиваля? хранить N последних?) | devops-engineer | Когда место упадёт ниже 20% |
| TD-9 | **Рефакторинг Shared в Baza** — бардак в использовании Shared кода в приложении Baza | tech-lead | После Laravel 11 update |
| TD-10 | **Логирование действий пользователей + воронка покупки** (единый поток JSON + читаемый формат) | devops-engineer + security-engineer | **v2.7.0** |
| TD-11 | **Покрыть тестами контроллеры (HTTP слой)** | auto-tester | После TD-2 |
| TD-12 | **Миграция Bootstrap 4 → 5** (модалки через vanilla JS) | frontend-helper | После Laravel 11 |
| TD-13 | **Настройка CI/CD пайплайна (GitHub Actions)** — базовый CI в v2.5.0, полный CD в v2.7.0 | devops-engineer | **v2.5.0 + v2.7.0** |
| TD-14 | **Единый паттерн для `Order::none()` в фильтрах списков** — `Order::fromState($data)` кидает на чужих значениях. Сделать `Order::fromStateSafe()` или базовый `ListRequest` FormRequest | tech-lead | До следующего публичного `getList`-эндпоинта |
| TD-22 | **Первый проход Laravel Pint по базе кода Backend** — `pint --test` нашёл нарушения в ~411 файлах (порядок импортов, пробелы вокруг операторов, трейлинг-запятые). Применить `pint` отдельным коммитом, согласовав окно (чтобы не конфликтовать с feature-ветками в работе). В CI пока `continue-on-error: true` на lint-backend и lint-baza. После применения — снять continue-on-error | code-reviewer + tech-lead | После закрытия активных feature-веток |
| TD-23 | **Аудит документации vs реальный код** — после релизов 2026-05-04 (заказы-списки), 2026-05-05 (Auto-модуль), 2026-05-11 (парковка), 2026-05-15 (auto-payment), 2026-05-29 (Baza sync) документация в `.claude/docs/` отстала от кода. ~52 расхождения (18 критичных). Полный отчёт + план правок: `.claude/specs/docs-audit-2026-05-29.md`. Главное: модуль Auto не описан, роль `pusher_curator` не упомянута, 5 эндпоинтов без документации, 6 history-событий не перечислены. ~3-4 часа работы. | technical-writer + tech-lead | **v2.6.0** |
| TD-25 | **doctrine/dbal перенести из require-dev → require (Backend)** — миграция `2026_01_27_172643_add_fields_in_questionnaire` использует `->change()` который требует `doctrine/dbal`. Сейчас пакет приходит только как transitive из require-dev, поэтому staging изначально пытался ставить с `--no-dev` и падал. Временно сняли `--no-dev` в deploy-staging.yml, но это не решает проблему — на prod нужно явно `composer require doctrine/dbal:^3.5` (требует update lock). Заблокирован сетью у разработчика (packagist timeout). | tech-lead | После восстановления доступа к packagist |
| TD-27 | **Сделать все сидеры идемпотентными** (Backend) — сейчас часть сидеров (`FestivalSeeder`, `PromoCodSeeder`, `TypeTicketsSeeder` и т.д.) использует прямой `DB::table()->insert()` с фиксированными UUID → при повторном прогоне на непустой БД словим `duplicate key`. Это блокирует пересборку тестовых данных на staging без ручного `migrate:fresh`. Решение: заменить `insert()` на `updateOrCreate()` / `firstOrCreate()` везде. По образцу `OptionTestDataSeeder` (уже сделан). Затем — снять предупреждение из workflow staging-deploy и разрешить `seed=true` на непустой БД. | auto-tester | До v2.7.0 |
| TD-26 | **CORS middleware — перенести whitelist в env** — сейчас список разрешённых Origin'ов хардкодом в `Backend/app/Http/Middleware/CORS.php` (массив из ~20 элементов: dev/prod/staging/legacy). При появлении нового домена (например `qr.spaceofjoy.ru` в v2.7.0) — править PHP-код. Нужно: ввести `CORS_ALLOWED_ORIGINS` (comma-separated) в `.env`, поддержать wildcard для dev, оставить legacy дефолты как fallback. Параллельно — рассмотреть `fruitcake/laravel-cors` (стандартный пакет, поддерживается Laravel) вместо собственного middleware. | tech-lead + security-engineer | **v2.7.0** (вместе с qr.spaceofjoy.ru SSO) |
| TD-24 | **Cross-Claude координация с qr.spaceofjoy.ru** — сервис qr.spaceofjoy.ru разрабатывается отдельным репозиторием через свой Claude Code. Нужно выбрать механизм синхронизации спецификаций и API контрактов между двумя проектами, чтобы они не разъехались. 4 подхода: (A) OpenAPI contract-first, (B) shared docs через git submodule, (C) зеркальные `.claude/specs/` в обоих репо, (D) cross-Claude через MCP filesystem. Решение принимать **перед стартом v2.7.0** (Passport SSO + интеграция). Без решения — риск рассинхрона контрактов между Backend и qr.spaceofjoy.ru. | tech-lead + business-analyst | **Перед стартом v2.7.0 (июль 2026)** |
| TD-28 | **IDOR в `removeTicket`** (pre-existing, найдено при security-ревью v2.6.0 aggregate-rewrite) — `OrderTickets::removeTicket` + `RemoveTicketCommandHandler` НЕ проверяют владение заказом, а роут открыт для `curator`/`pusher_curator`. Любой авторизованный куратор может удалить билет из чужого заказа по UUID. Контраст — `changeTicket` ownership-проверку имеет. Фикс: добавить проверку `curator_id == Auth::id()` (или admin) по образцу `changeTicket`. | security-engineer | До старта продаж осеннего фестиваля |
| TD-29 | **Раздельные FormRequest для create / createFriendly** — общий `CreateOrderTicketsRequest` после v2.6.0 валидирует только per-guest контракт `create`, а `createFriendly` читает top-level `ticket_type_id`/`price` без валидации (`new Uuid(null)` при отсутствии, ловится try/catch). Также `types_of_payment_id` не `required` (нельзя сделать required в общем FR — сломает Friendly) → при отсутствии падает позже, в ответе утекают `file`/`line`. Решение: отдельный `CreateFriendlyOrderRequest` + перестать возвращать `file`/`line` в проде. | code-reviewer + security-engineer | v2.6.1 |
| TD-32 | **CI не собирает `AdminFront/` (B-5)** — основной workflow `.github/workflows/ci.yml` не имеет job сборки новой админки; Vite-сборка живёт только в `deploy-staging.yml`. Регрессии новой админки CI не ловит до деплоя на staging. Решение: добавить job `build-admin` (npm ci + vite build) в `ci.yml` по образцу существующего build-фронта. | devops-engineer | Фаза 5 спеки AdminFront (CI + cutover) / до расширения админки |
| TD-33 | **Пред-существующий баг staging: `auth:api` без токена → 500** — все защищённые роуты при отсутствии токена отдают **500** (`Route [login] not defined` в `Authenticate.php`) вместо чистого 401. Не блокирует залогиненного админа, но мешает корректной обработке протухшего токена на фронте. Фикс — отдельной веткой (зарегистрировать именованный роут `login` или переопределить `unauthenticated()`/`redirectTo()`). Известно из спеки AdminFront §8 (открытый вопрос 4). | tech-lead | До cutover на новую админку |
| TD-34 | **Два фронта в репо до cutover (B-6)** — `FrontEnd/` (старый Vue CLI/webpack, держит прод) + `AdminFront/` (новый Vite+Sakai) сосуществуют. Дубль CI/конфигов, риск рассинхрона. Принято как цена greenfield (Strangler, спека §4/§7). Митигация: держать окно параллельной жизни коротким, после паритета экранов — cutover и удаление `FrontEnd/` (fallback ещё 1–2 фестиваля). | frontend-helper + tech-lead | v2.9.0 (cutover) |
| TD-35 | **Бэкенд-эндпоинт «доставка билета в baza» (AF-4) — ✅ ЗАКРЫТ 2026-06-19; остался S3 (AF-5)** — часть **AF-4 закрыта**: модуль `Backend/src/BazaDelivery/` (таблица `baza_deliveries`, машина `queued→sending→delivered/failed` + ретрай, кап 10), async-запись через `BazaDeliveryDispatcher`/`DeliverTicketToBazaJob` (сбой Baza не роняет выдачу), admin-API `bazaDelivery/getList|getItem|resend|getStats` + экран «Доставка в baza» + дашборд-виджет, секция `baza` в `getPipeline`. Все 4 пути записи (el/spisok/live/auto) на трекинге. Спека: `.claude/specs/baza-delivery-async-prompt.md`. **Остаётся (AF-5):** S3-совместимое хранилище для билетов/PDF (сейчас PDF в `storage/app/public/tickets/`): выбор провайдера + драйвер storage + миграция файлов (зависит от devops). | backend ✅ / devops-engineer (S3) | ✅ AF-4 закрыт / S3 → v2.8.0–v2.9.0 |
| TD-36 | **Email-доставка билетов: подтверждение `delivered`/`bounced` от провайдера (AF-6, частично закрыт)** — система отправки писем по шаблонам (Ф1–Ф5, ветка `feat/qr-order-pipeline-view`, спека `.claude/specs/email-delivery-system.md`) **закрыла бо́льшую часть AF-6**: модуль `Backend/src/EmailDelivery/` (таблица `email_messages`, машина статусов `queued→sending→sent`/`failed` + ретрай), путь письма виден в админке (`POST /api/v1/emailDelivery/getList` + `getItem` + история), повторная отправка из админки (`POST /api/v1/emailDelivery/resend/{id}`, читает Mailable из БД), статус `opened` через пиксель прочтения (`GET /api/v1/mail/open/{token}.gif`, за флагом `mail_delivery.open_tracking` / `MAIL_OPEN_TRACKING`, default `false` — 152-ФЗ). **Остаётся:** статусы `delivered` (доставлено серверу получателя) и `bounced` (отскок) **требуют транзакционного провайдера с вебхуками** — этого пока нет (под РФ/152-ФЗ: UniOne/Unisender, SendPulse, Mailopost). Поля под это уже есть (`email_messages.provider_message_id`, статусы `delivered`/`bounced` в `EmailStatus`). Нужен: аккаунт провайдера (биллинг владельца) + вебхук-эндпоинт, проставляющий `delivered`/`bounced`. Пара к AF-4 (baza). | backend + devops-engineer | v2.8.0 (AF-6) |
| ✅ TD-37 | **Legacy org-письма идут мимо `MailDispatcher`** — **закрыт 2026-06-17 (PR #77)**: все 16 событий-уведомлений (`Order/OrderTicket/Domain/ProcessUserNotification*` — отмена/изменение/трудности/оплата/создание/live/list, плюс `ProcessAccountNotification`, `ProcessPasswordResets`, анкетные `ProcessGuestNotificationQuestionnaire`/`ProcessInviteLinkQuestionnaire`/`ProcessReplayNotificationQuestionnaire`) переведены с `Mail::to()->send()` на `app(MailDispatcher::class)->send(EmailEvent::…, new EmailContext(source: 'org_event', …), $mailable)`. Теперь legacy-письма попадают в `email_messages` и видны в админке «Доставка писем» (`source='org_event'`), отправляются асинхронно `SendEmailJob` с ретраем. Passive-listener из спеки не понадобился (прямая конверсия чище). Тест: `LegacyOrgEmailsTrackedTest`. | backend | ✅ Закрыт (PR #77) |
| TD-38 | **Enforced-линтеры + статический анализ в CI/CD** (запрошено владельцем 2026-06-19) — сейчас линтеры в CI **не валят сборку**: Pint (на базе php-cs-fixer) идёт с `continue-on-error: true` (`lint-backend`/`lint-baza`), `composer audit` — с `\|\| true`, PHPStan отсутствует вовсе. Подключить как реальные гейты: **(1) PHPStan** (Backend + Baza; подобрать level + baseline для легаси, чтобы не утонуть) — новый job; **(2) php-cs-fixer / Pint** — снять `continue-on-error` (зависит от **TD-22** — сначала прогнать `pint` по базе, ~411 файлов, отдельным коммитом); **(3) composer audit** — убрать `\|\| true`, чтобы уязвимости в зависимостях роняли сборку (+ `npm audit`). Переводить в required по мере готовности каждого. | devops-engineer + tech-lead | После TD-22 (Pint по базе) |
| TD-39 | **Swagger / OpenAPI-спецификация API** (запрошено владельцем 2026-06-19) — для дальнейшей интеграции (qr↔org S2S, ingest-API Baza, вебхуки, внешние клиенты) нужна машиночитаемая OpenAPI-спека. Сейчас API описан только вручную в `.claude/docs/API.md` (легко разъезжается с кодом). Подключить генератор (`darkaonline/l5-swagger` или `zircote/swagger-php` с атрибутами на контроллерах) → Swagger UI (`/api/documentation`), держать актуальной в CI. Приоритет — публичные/S2S-эндпоинты контракта интеграции. Парная к TD-24 (sync контрактов с qr.spaceofjoy.ru). | tech-lead + backend | Перед/в ходе v2.7.0 (интеграция qr↔org) |
| TD-40 | **Единый стиль/архитектура Backend + Baza + консолидация в `Shared/`** (запрошено владельцем 2026-06-19) — два Laravel-приложения (Backend, Baza) разъехались по стилю и паттернам (Baza: `scr/` вместо `src/`, свой `InMemorySymfony*Bus` на сервис, плоский `is_admin` vs ролевые модели Backend, дубль CQRS-обвязки). Есть общая папка `Shared/` (PSR-4 `Shared\`, подключена в обоих) — недоиспользована. Привести к единому стилю (Pint-пресет, неймспейсы, структура модулей) и вынести общий код (Bus, VO, Criteria/Filters, Entity) в `Shared/`, чтобы не дублировать и снизить затраты на поддержку + сохранить работоспособность. Расширяет **TD-9** (бардак Shared в Baza). Делать аккуратно, аддитивно, после стабилизации Ф2 и (желательно) Laravel 11 (TD-4). | tech-lead | После Laravel 11 (TD-4) / по мере касания общего кода |
| TD-41 | **Понятные идемпотентные сидеры для стенда (Baza)** (запрошено владельцем 2026-06-19) — staging нельзя надёжно перезасеять: `UsersTableSeeder` (`DB::table->insert` с фикс-id 1..36) НЕ идемпотентен → force-seed на непустой БД падает `duplicate key`; auto-seed пропускает непустую БД → новые Ф2-таблицы (`baza_role_permissions` матрица, `change_user`) на стенде остаются пустыми (админы работают через суперроль, но демо-данных нет). Нужно: (1) сделать Baza-сидеры идемпотентными (`updateOrCreate`/`firstOrCreate`, по образцу `BazaRolePermissionsSeeder`/`StaffUsersSeeder`); (2) понятный демо-сидер стенда (несколько сотрудников с РАЗНЫМИ ролями смены, открытая смена, дефолт-матрица) — чтобы на стенде было видно RBAC/роли вживую; (3) разрешить безопасный force-seed на стенде. Baza-зеркало **TD-27** (идемпотентные сидеры Backend). | auto-tester + tech-lead | До демонстрации Ф2 на стенде / v2.7.0 |

---

## 🟢 Low Priority

| ID | Описание | Кто ведёт | Заметки |
|----|----------|-----------|---------|
| TD-15 | **Mobile UX — отдельная вёрстка** для Android+iPhone (отдельный мобильный SPA — Ф2.A). Целевые экраны — те где есть таблицы | ux-ui-designer + frontend-helper | Большая работа, делать когда успеем |
| TD-16 | **Автоматизация бэкапов БД** — сейчас ручные `mysqldump` → пользователь скачивает к себе. Сделать автомат (cron + ротация) + внешнее хранилище | devops-engineer | После v2.7.0 (когда будет CD) |
| TD-17 | **Миграция на Vue Router lazy loading** | frontend-helper | После Laravel 11 |
| TD-18 | **Заменить jQuery модалки на нативные Vue** | frontend-helper + tester | После Bootstrap 5 |
| TD-19 | **Единый Response Interceptor для Axios** | frontend-helper | — |
| TD-20 | **Тёмная тема** (хотят пользователи) | ux-ui-designer | После Mobile UX |
| TD-21 | **TypeScript для фронтенда** | tech-lead | Когда-нибудь |
| TD-30 | **Удалить мёртвый `PriceService`/`PriceDto` (Order)** — после v2.6.0 aggregate-rewrite расчёт цены полностью переехал в `OrderPriceCalculator`. `Order/OrderTicket/Service/PriceService` больше не вызывается извне (только сам себя), `PriceDto` убран из домена/DTO. Оставлены, чтобы не раздувать PR рефакторинга. Удалить вместе с `PriceServiceTest` (или переписать тест, если что-то ещё зависит). | tech-lead | После стабилизации v2.6.0 |

---

## Заметки по задачам

### TD-1 Race Condition (детализация)

**Проблема:** воркер поднимается раньше БД → зацикливание (commit → rollback → disconnect).

**Решение:**
- В `docker-compose.yml` для прода добавить healthcheck на MySQL (`mysqladmin ping`)
- Воркер-контейнер: `depends_on: { mysql: { condition: service_healthy } }`
- Проверить что воркер не стартует до полной готовности БД

**Зависимость:** TD-5 Sentry filter — после фикса TD-1 «слетает подключение к БД» должна пропасть из Sentry.

### TD-2 Починка PHPUnit (план)

1. Запустить `docker exec -it php-solarSysto ./vendor/bin/phpunit --testdox-html ./tests-report.html` — посчитать сколько падает
2. Сгруппировать ошибки по типу (PDO connection, missing tables, etc.)
3. Использовать отдельную БД `systo_test` (см. PROJECT_MEMORY)
4. Чинить по группам, не по одному тесту
5. После починки в CI переключить `continue-on-error: false`

### TD-6 Альтернатива Telegram (опции)

| Опция | Тип | Плюс | Минус |
|-------|-----|------|-------|
| Email (SMTP) | Primary | Уже работает | Медленный отклик |
| Чат на сайте (Tawk.to/Crisp) | Real-time | Бесплатный | Внешний JS |
| VK API | Backup | Не блокируется в РФ | Узкая аудитория |
| SMS-шлюз | Сервисные нотификации | Быстро доходит | Платно |

Telegram-бот анкет (`http://77.222.60.58:8000`) — **сторонний сервис, нет доступа**. Принимаем как чёрный ящик. Когда появится доступ — реализуем backup-канал.

### TD-14 Единый паттерн `Order::none()` (старая заметка)

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

### TD-15 Mobile UX (опции)

| Вариант | Сложность | Срок |
|---------|-----------|------|
| Отдельный мобильный SPA (`m.spaceofjoy.ru`) — выбрано | Высокая | TBD |
| Адаптивная вёрстка с условным рендером | Средняя | — |
| Mobile-First с переделкой desktop | Высокая, риск | — |

---

## Правило

**scrum-master напоминает об этом файле** в рамках backlog grooming раз в спринт.

**Критично до 1 июня:** только фиксы багов, никаких Laravel-апдейтов на проде.

---

## История изменений документа

| Дата | Изменение |
|------|-----------|
| 2026-04-04 | Создан документ. Перечислены 15 задач |
| 2026-04-12 | Закрыт пункт: «Фильтрация festivalId в Vuex store OrderModule» |
| 2026-05-10 | Добавлен пункт: «Единый паттерн для `Order::none()` в фильтрах списков» |
| 2026-05-28 | Полная актуализация: расставлены приоритеты с ID, добавлены TD-3 (очистка Friendly), TD-6 (альтернатива Telegram), TD-7 (152-ФЗ), TD-8 (место на VPS), TD-15 (mobile отдельный SPA). Привязка к версиям v2.5.0 → v2.9.0 |
| 2026-05-29 | Закрыты TD-1 (Race Condition воркера, v2.5.0), TD-3 (очистка Friendly, v2.5.0), TD-2 (PHPUnit Backend+Baza, v2.5.0+v2.5.1). Помечены ✅ |
| 2026-05-29 | Добавлен TD-23 (аудит документации vs код) — 52 расхождения после аудита technical-writer. Полный отчёт в `.claude/specs/docs-audit-2026-05-29.md`. Привязан к v2.6.0 |
| 2026-05-30 | Добавлен TD-24 (cross-Claude координация с qr.spaceofjoy.ru) — оба сервиса разрабатываются через Claude Code, нужен механизм sync контрактов. Решение перед стартом v2.7.0 |
| 2026-06-14 | Добавлен **TD-31** (B-0: v2.6.0 не затеган) в High Priority. Добавлены **TD-32** (CI не собирает AdminFront, B-5), **TD-33** (баг staging `auth:api`→500), **TD-34** (два фронта в репо до cutover, B-6), **TD-35** (бэкенд-эндпоинт доставки в baza + S3, AF-4/AF-5) в Medium Priority. Обновлён TD-4 (Laravel 9→11 в работе на `feature/laravel-11`). Источник: сессия разворота org → admin-only, спека `.claude/specs/admin-frontend-vite-sakai.md`. |
| 2026-06-17 | **TD-36 (AF-6) частично закрыт** системой отправки писем по шаблонам (Ф1–Ф5, ветка `feat/qr-order-pipeline-view`): `email_messages` + машина статусов + ретрай из админки + пиксель `opened` (за флагом, 152-ФЗ). Остаётся только провайдер с вебхуками `delivered`/`bounced`. Добавлен **TD-37** (legacy org-письма отмена/изменение через `ProcessUserNotification*` идут мимо `MailDispatcher` — подключить; passive-listener из спеки не реализован). Источник: спека `.claude/specs/email-delivery-system.md`. |
| 2026-06-19 | Добавлен **TD-38** (enforced-линтеры + статический анализ в CI/CD: PHPStan + php-cs-fixer/Pint без `continue-on-error` + `composer audit` без `\|\| true`) — запрошено владельцем. Также в рамках обновления Baza закрыты Ф0 (локализация ассетов, PR #88) и Ф1 (auth-дыры PR #89 + вынос паролей персонала из git PR #90). |
