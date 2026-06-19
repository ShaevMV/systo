# Промпт для новой сессии: асинхронная доставка билета в Baza + трекинг + админ-экран (AF-4)

> **Как использовать:** открой новую сессию Claude Code в этом репозитории и дай ей этот файл как задачу (например: «Выполни задачу из `.claude/specs/baza-delivery-async-prompt.md`»). Промпт самодостаточный.

---

## 0. Цель (одним абзацем)

Сделать доставку билета в **Baza** («система входа» — приложение, которое сканирует билеты на входе фестиваля) **асинхронной и отслеживаемой**: каждая попытка записи билета в Baza фиксируется в отдельной таблице со статусом (`в очереди → отправляется → доставлен / ошибка`), с авто-ретраем и **ручным повтором из админки**. Контроль доставки виден в **новой админке** (`AdminFront`, раздел «Билеты → Доставка в baza») — это закрывает **AF-4** («подтверждение доставки билета в baza»). Архитектурный образец — **уже готовый модуль системы писем `Backend/src/EmailDelivery/`** (та же задача «дошло / где застряло»); зеркалируем его.

## 1. Обязательные правила проекта (прочитай и соблюдай)

- `CLAUDE.md` + `.claude/docs/RULES.md` + `.claude/docs/CONVENTIONS.md` + `.claude/docs/DOMAIN.md`.
- **Чистая архитектура:** обращение к БД — ТОЛЬКО в репозитории. CommandHandler/QueryHandler/Job/контроллер к БД напрямую не ходят.
- **CQRS + SOLID + DDD.** Чтение списка — через QueryBus (whitelist фильтров).
- **Единый формат данных** (RULES §11): JSON-поля — массивом (Eloquent cast `array`), timestamps с DB DEFAULT — не задавать в PHP, `datetime`-каст не оборачивать в `Carbon::parse`.
- **Коммит — только после одобрения владельца.** Тесты — ТОЛЬКО через Docker: `docker exec php-solarSysto ./vendor/bin/phpunit`.
- **Главное правило:** смотри, как сделано раньше. Зеркаль `EmailDelivery`, не изобретай.
- Ответы и комментарии — на русском.

## 2. Что есть сейчас (факты по коду — проверь сам перед стартом)

**Запись в Baza** (репозиторий `Backend/src/Ticket/CreateTickets/Repositories/InMemoryMySqlTicketsRepository.php`):
- `setInBaza(TicketResponse): bool` — upsert в `el_tickets` (обычный билет) по `uuid`. Идемпотентен. На ошибке логирует и возвращает `false` (НЕ кидает).
- `setInBazaList(TicketResponse): bool` — upsert в `spisok_tickets` (заказ-список) по `ticket_uuid`. Идемпотентен.
- `setInBazaLive(int $number, ?Uuid $ticketId): bool` — связка `live_tickets.el_ticket_id` по номеру. Кидает `DomainException`, если номера нет.
- Соединение — `DB::connection('mysqlBaza')` (отдельная БД Baza).

**Кто вызывает (два пути):**
- **qr-заказы — УЖЕ асинхронно:** `Backend/src/QrOrder/Application/Step/PushToBazaStep.php` → диспатчит `Backend/src/QrOrder/Application/Job/PushTicketToBazaJob.php` (`ShouldQueue`, `tries=3`, `backoff=30`, идемпотентно). Пишет шаг `step_push_to_baza` (ok/fail) в `domain_history` (`aggregate_type=qr_order`) + лог `PipelineLog`. Живой билет — отдельный `LinkLiveTicketJob` (`setInBazaLive`).
- **legacy/order_tickets — СИНХРОННО:** `Backend/src/Ticket/CreateTickets/Application/PushTicket/PushTicketsCommandHandler.php` → `setInBaza`/`setInBazaList` напрямую, на `false` кидает `DomainException` (рвёт flow). **Это и есть то, что нужно сделать асинхронным + трекаемым.**

**Образец для зеркалирования — `Backend/src/EmailDelivery/`** (прочитай ВСЁ):
- `Domain/ValueObject/EmailStatus.php` — VO статуса + машина переходов.
- `Domain/EmailLifecycleEvent.php` — `HistoryEventInterface`, `aggregate_type='email'`, `event_name='email_'.status`.
- `Application/MailDispatcher.php` — единая точка: создаёт запись (`queued`) + история + `SendEmailJob::dispatch`.
- `Application/Job/SendEmailJob.php` — `ShouldQueue`, `tries=3`, `backoff=[30,120,600]`, `queued→sending→sent/failed`, идемпотентность, `failed()`.
- `Application/EmailDeliveryApplication.php` — `getList`/`getItem`/`resend`.
- `Application/GetList/EmailMessageGetListQuery(+Handler).php` — whitelist фильтров через QueryBus.
- `Repositories/{EmailMessageRepositoryInterface, InMemoryMySqlEmailMessageRepository}.php`.
- `Responses/{EmailMessageItemForListResponse, EmailMessageGetListResponse}.php`.
- `Dto/EmailMessageDto.php`.
- Модель `App\Models\EmailDelivery\EmailMessageModel`, миграция `2026_06_17_130000_create_email_messages_table.php`.
- Роуты `Backend/routes/emailDelivery.php`, контроллер `App\Http\Controllers\EmailDelivery\EmailDeliveryController`.
- Фронт-образец: `AdminFront/src/views/admin/EmailDeliveryListView.vue` + `AdminFront/src/store/modules/EmailDeliveryModule/`.
- Документация: `.claude/specs/email-delivery-system.md`, `.claude/docs/DOMAIN.md` (§EmailDelivery), `.claude/docs/API.md` (§3.4).

## 3. Целевая архитектура — модуль `Backend/src/BazaDelivery/` (зеркало EmailDelivery)

### 3.1 Домен
- **`Domain/ValueObject/BazaDeliveryStatus.php`** — VO + машина переходов (проще, чем у писем):
  ```
  queued ──► sending ──► delivered
     │          │
     └► failed ◄┘          (failed ──► queued при ретрае/ручном повторе)
  ```
  Переходы: `queued→{sending,failed}`; `sending→{delivered,failed}`; `delivered→{}` (финал); `failed→{queued}`. Методы как у `EmailStatus`: `value()`, `equals()`, `canTransitionTo()`, `isUnresolved()`, `all()`.
- **`Domain/BazaDeliveryLifecycleEvent.php`** — `HistoryEventInterface`: `aggregate_type='baza_delivery'`, `event_name='baza_'.<status>` (`baza_queued`/`baza_sending`/`baza_delivered`/`baza_failed`). Payload без ПДн (статус/ошибка/target, не ФИО).

### 3.2 Таблица `baza_deliveries` (миграция `2026_06_19_..._create_baza_deliveries_table.php`)
Поля (зеркало `email_messages`, но под билет):
- `id` (uuid PK), `ticket_id` (uuid, index) — билет нашей системы (== `el_tickets.uuid`),
- `order_id` (uuid, nullable, index) — заказ (для фильтра/связи),
- `target` (string: `el_tickets` / `spisok_tickets` / `live_tickets`, index) — куда пишем,
- `status` (string, index), `attempts` (int default 0), `error` (text nullable — «где застряло»),
- `name` (string nullable) / `email` (string nullable) — для отображения в админке (ПДн → admin-only),
- `kilter`/`number` (int nullable) — номер билета (для live/поиска),
- `festival_id` (uuid nullable, index),
- `source` (string: `qr_pipeline` / `org_event`) — как у писем,
- `delivered_at` (timestamp nullable), timestamps.
- Индексы: `(status, created_at)`, `(festival_id, status)`, `ticket_id` (можно UNIQUE по `(ticket_id, target)` для идемпотентности — обсуди; setInBaza и так идемпотентен по uuid, но дубль строк трекинга нежелателен → upsert по `(ticket_id,target)`).
- `hasColumn`-гарды в миграции (как `2026_06_18_140000`), идемпотентный прогон.

### 3.3 Application
- **`Application/BazaDeliveryDispatcher.php`** — единая точка (как `MailDispatcher`): `dispatch(TicketResponse $ticket, string $target, BazaDeliveryContext $ctx): Uuid`. Создаёт/обновляет запись `baza_deliveries` (`queued`, upsert по `(ticket_id,target)`) + пишет историю `baza_queued` + `DeliverTicketToBazaJob::dispatch($id)`.
- **`Application/BazaDeliveryContext.php`** — контекст: `orderId?`, `festivalId?`, `name?`, `email?`, `kilter?`, `source` (default `org_event`), `actorType` (default `system`/`qr`).
- **`Application/Job/DeliverTicketToBazaJob.php`** (`ShouldQueue`, `backoff=[30,120,600,...]`) — по `id`: `markSending` (attempts++) → читает нужный `TicketResponse` (НЕ сериализуй Mailable-стиль: храни `ticket_id`+`target`, в job заново `getTicket($ticketId)` через репозиторий) → вызывает `setInBaza`/`setInBazaList`/`setInBazaLive`/`setInBazaAuto` → `markDelivered`/`markFailed(error)` + **пишет историю на КАЖДУЮ попытку** (`baza_sending` → `baza_delivered`/`baza_failed` с текстом ошибки) в `domain_history`. Идемпотентность: из `delivered` повторно не доставляет.
  - **Кап = 10 попыток (решение владельца §6.4):** общее число попыток (`attempts`) на запись `baza_deliveries` не превышает **10**. Auto-ретрай продолжается, пока `attempts < 10` и статус `failed`; на 10-й неуспешной — **терминальный `failed`, авто-ретрай прекращается** (job больше не пере-диспатчится). Реализуй кап на уровне приложения (проверка `attempts` перед/после попытки), а не только через Laravel `tries` (т.к. ретрай складывается из авто-бэкоффа + ручного resend). Ручной `resend` по умолчанию **НЕ сбрасывает** счётчик (после 10 — только разовая попытка по явному действию админа; задокументируй выбранное поведение). **ОБЯЗАТЕЛЕН тест:** 10 подряд неуспешных → 11-я не выполняется, статус терминальный `failed`, `attempts==10`.
  - **Важно:** этот job ЗАМЕНЯЕТ/ОБОРАЧИВАЕТ `PushTicketToBazaJob` (qr) — qr-пайплайн начинает диспатчить через `BazaDeliveryDispatcher`, чтобы у qr-билетов тоже был трекинг (а не только шаг `step_push_to_baza`). Старый `PushTicketToBazaJob` — депрекейтнуть/удалить после переезда qr на новый путь.
- **`Application/BazaDeliveryApplication.php`** — `getList(Query)` (через QueryBus), `getItem(Uuid)` (+ история через `HistoryRepositoryInterface::getByAggregateId`), `getByAggregate(type,id)` (для экрана «весь путь» qr — добавить в `getPipeline`), `resend(Uuid, ?actorId)` (`failed→queued` + history + dispatch), `getStats()`/`countStuck()` (число `failed` — для дашборда «застрявшие билеты»).
- **`Application/GetList/BazaDeliveryGetListQuery(+Handler).php`** — whitelist фильтров: `status` (EQUAL), `ticket_id` (EQUAL), `order_id` (EQUAL), `festival_id` (EQUAL), `target` (EQUAL), `name`/`email` (LIKE). Пагинация `page`/`perPage` (1..100). Сортировка `Order::none()` на кривом orderBy.

### 3.4 Repository / Model / Responses
- **`Repositories/BazaDeliveryRepositoryInterface.php`** + **`InMemoryMySqlBazaDeliveryRepository.php`** — методы зеркалят `EmailMessageRepository`: `create(Dto): bool` (upsert по `(ticket_id,target)`), `findById`, `markSending`, `markDelivered`, `markFailed(error)`, `requeue`, `getList(Filters,Order,page,perPage): Collection` (проекции для списка), `countList(Filters): int`, `getByAggregate`, `countStuck(festivalId?)`.
- **Модель** `App\Models\BazaDelivery\BazaDeliveryModel` (таблица `baza_deliveries`, касты: `attempts`→int, `delivered_at`→datetime).
- **DTO** `Dto/BazaDeliveryDto.php` (+ фабрики `queued(...)`, `fromState($row)`).
- **Responses** `Responses/BazaDeliveryItemForListResponse.php` (snake_case, для списка) + `BazaDeliveryGetListResponse.php` (collection + totalCount).
- DI-бинды — в `Tickets/TicketsProvider` (как `EmailMessageRepository`).

### 3.5 Точки интеграции (заменить синхронный setInBaza на трекаемый async)
1. **legacy/order:** `PushTicketsCommandHandler` — вместо синхронного `setInBaza`/`setInBazaList` → `BazaDeliveryDispatcher::dispatch($ticket, target, ctx)`. БОЛЬШЕ НЕ кидать `DomainException` на сбой Baza (сбой не должен рвать выдачу билета/письма — Baza доедет ретраем).
2. **qr:** `PushToBazaStep` → вместо `PushTicketToBazaJob` диспатчить через `BazaDeliveryDispatcher` (`source='qr_pipeline'`, `actorType='qr'`). Шаг `step_push_to_baza` в `domain_history(qr_order)` оставить (это «поставлено в очередь»), а реальный статус доставки — в `baza_deliveries`. Удалить/депрекейтнуть `PushTicketToBazaJob`.
3. **live-билет:** `setInBazaLive` (связка номера) — **трекать** как `target=live_tickets` (§6.1). Номер приходит в составе запроса (`guests[].number`), связка в выдаче — провести через тот же диспатч/трекинг, не сломав `LinkLiveTicketJob`.
4. **Auto-модуль:** `Backend/src/Auto/Application/AutoApplication.php` (`setInBazaAuto`, строки ~52/120) + `Backend/src/Auto/Repositories/InMemoryMySqlAutoRepository.php::setInBazaAuto` — перевести на `BazaDeliveryDispatcher` (target=`auto`), async + трекинг (§6.2). Прочитай эти файлы.
5. `setInBaza*`/`setInBazaAuto` в репозиториях — НЕ удалять (это реальная запись в Baza; job их вызывает).

### 3.6 Admin API — `Backend/routes/bazaDelivery.php` (все `auth:api`+`admin`, ПДн)
- `POST /api/v1/bazaDelivery/getList` — фильтры (whitelist) + пагинация + total.
- `GET /api/v1/bazaDelivery/getItem/{id}` — запись + история (таймлайн `baza_*`).
- `POST /api/v1/bazaDelivery/resend/{id}` — `failed`→`queued` + re-dispatch.
- (опц.) `POST /api/v1/bazaDelivery/getStats` — число доставленных/в очереди/застрявших по фестивалю (для дашборда).
- Контроллер `App\Http\Controllers\BazaDelivery\BazaDeliveryController` (по образцу `EmailDeliveryController`).
- Зарегистрировать файл роутов в `RouteServiceProvider`/`api.php` (как `emailDelivery.php`).
- **Расширить `qrOrder/getPipeline/{id}`**: добавить секцию `baza` (как `emails`) — статусы доставки билетов заказа в baza (через `getByAggregate('baza_delivery'...)` или по `order_id`).

### 3.7 Frontend — AdminFront (зеркало EmailDeliveryListView)
- **`AdminFront/src/views/admin/BazaDeliveryListView.vue`** — список + фильтры (статус/target/фестиваль/№/email) + деталь (Dialog с таймлайном) + кнопка «Повторить» (resend) + Toast. Снять с `EmailDeliveryListView.vue` 1:1 по структуре.
- **`AdminFront/src/store/modules/BazaDeliveryModule/`** (index/actions/getters/mutations) — по образцу `EmailDeliveryModule`.
- **Роут** `/admin/baza-delivery` (`meta:{requiresAuth:true, role:['admin']}`) + **новый раздел меню «Билеты» → «Доставка в baza»** в `AdminFront/src/layout/AppMenu.vue` (раздела «Билеты» сейчас нет — создать).
- **Экран qr-заказа** (`QrOrderListView.vue`, деталь): добавить блок «Доставка в baza» (статусы по билетам) — данные из расширенного `getPipeline` (`pipeline.baza`).
- **Дашборд** (`DashboardView.vue`): виджет **«застрявшие билеты»** (число `failed` из `bazaDelivery/getStats`) — **в составе этой задачи** (§6.3).

### 3.8 Тесты (Docker, обязательно зелёные)
- Unit: `BazaDeliveryStatus` (переходы), `BazaDeliveryDto`.
- Feature: dispatcher создаёт запись `queued` + history; job → `delivered` при `setInBaza=true`; → `failed` при `false`/исключении + ретрай; идемпотентность (повтор не плодит строки/доставку); `resend` (`failed→queued`); `getList` whitelist + пагинация; `getPipeline` отдаёт `baza`.
- Замокать соединение `mysqlBaza` (или использовать тестовую baza, как делают существующие baza-тесты — посмотри `tests/Feature/QrOrder/PushTicketToBazaJobTest.php`).
- Прогон: `docker exec php-solarSysto ./vendor/bin/phpunit` — весь сьют зелёный.

## 4. Порядок работ (фазы, каждая — отдельный коммит после одобрения)
1. **Домен + таблица + модель + DTO + репозиторий** (без интеграции) + unit-тесты статуса.
2. **Dispatcher + Job + Application + history** + feature-тесты (delivered/failed/retry/resend).
3. **Admin API** (getList/getItem/resend + getPipeline.baza) + контроллер + роуты + feature-тесты HTTP.
4. **Интеграция:** перевести на dispatcher все 4 пути записи в Baza — legacy `PushTicketsCommandHandler`, qr `PushToBazaStep` (депрекейт `PushTicketToBazaJob`), live `setInBazaLive`/`LinkLiveTicketJob`, Auto `AutoApplication::setInBazaAuto`; убрать кидание исключения в legacy (сбой Baza не рвёт выдачу). Реализовать кап 10 попыток + тест. Регресс — полный сьют.
5. **Frontend:** `BazaDeliveryListView` + Vuex-модуль + роут + меню «Билеты» + блок в детали qr-заказа (+ дашборд-виджет). Build `vite --base=/admin/` + ESLint зелёные.
6. **Доки:** `.claude/docs/API.md` (§ bazaDelivery), `DOMAIN.md` (модуль BazaDelivery + таблица + история `aggregate_type=baza_delivery`), `BUSINESS_RULES.md` (если меняется поведение выдачи), `BOARD.md`/`TECH_DEBT.md` (AF-4/TD-35 закрыты в части доставки в baza), спека `.claude/specs/` при необходимости.

## 5. Критерии приёмки
- [ ] Выдача билета (qr И legacy) больше **не блокируется** сбоем Baza — билет/письмо создаются, доставка в baza ставится в очередь и доезжает ретраем.
- [ ] Каждая запись билета в Baza видна в админке `/admin/baza-delivery` со статусом (`в очереди/отправляется/доставлен/ошибка`) + текстом ошибки + числом попыток.
- [ ] Ручной «Повторить» из админки переотправляет застрявший билет (`failed→queued`).
- [ ] В детали qr-заказа виден статус доставки его билетов в baza.
- [ ] Таймлайн в `domain_history` (`aggregate_type=baza_delivery`) — **история КАЖДОЙ попытки** видна.
- [ ] **Кап 10 попыток** работает: после 10 неуспешных — терминальный `failed`, авто-ретрай прекращён (есть тест).
- [ ] **live-билеты и Auto** (`setInBazaLive`/`setInBazaAuto`) тоже идут через трекинг (target `live_tickets`/`auto`).
- [ ] **Дашборд-виджет «застрявшие билеты»** показывает число `failed`.
- [ ] Полный PHPUnit зелёный; build/ESLint AdminFront зелёные.
- [ ] Идемпотентность: повторный прогон не плодит дубли строк трекинга и не пишет билет в Baza повторно с побочными эффектами.

## 6. Решения владельца (2026-06-19) — зафиксированы, НЕ переспрашивать

1. **Live-билеты** (`setInBazaLive`) — **трекаем** как `target=live_tickets`. Номер живого билета приходит **в составе запроса** (qr-контракт `guests[].number`), связка идёт в выдаче — трекать вместе со всеми (не выносить наружу). Не сломать `LinkLiveTicketJob`.
2. **Auto-модуль** (`setInBazaAuto` в `Backend/src/Auto/Application/AutoApplication.php` + `InMemoryMySqlAutoRepository.php`) — **тоже в трекер** (target напр. `auto`). Перевести `setInBazaAuto` на тот же `BazaDeliveryDispatcher` (async + трекинг).
3. **Дашборд-виджет «застрявшие билеты»** — **делаем в этой задаче** (`bazaDelivery/getStats` → `failed`-счётчик → виджет в `DashboardView.vue`).
4. **История попыток, но не более 10:** хранить **историю ВСЕХ попыток** (каждая попытка видна — таймлайн в `domain_history` `aggregate_type=baza_delivery`: `baza_sending`/`baza_delivered`/`baza_failed` с ошибкой на каждую попытку), при этом **жёсткий кап = 10 попыток**: после 10 неуспешных — терминальный `failed`, авто-ретрай прекращается (ручной resend из админки тоже не превышает 10; либо resend сбрасывает счётчик — реши и задокументируй, по умолчанию НЕ сбрасывает). **Обязателен тест на кап 10 попыток** (11-я не происходит, статус терминальный `failed`).

> Состояние доставки билета — одна строка `baza_deliveries` на `(ticket_id, target)` (текущий статус + `attempts`), а **история всех попыток** — в `domain_history` (как у писем: snapshot в таблице + таймлайн в истории). Это и есть «история попыток» без дублей строк трекинга.

## 7. Файлы-референсы (быстрый старт)
- Зеркало: весь `Backend/src/EmailDelivery/` + `App\Models\EmailDelivery\EmailMessageModel` + `Backend/routes/emailDelivery.php` + `App\Http\Controllers\EmailDelivery\EmailDeliveryController` + `AdminFront/src/views/admin/EmailDeliveryListView.vue` + `AdminFront/src/store/modules/EmailDeliveryModule/`.
- Текущая доставка: `Backend/src/Ticket/CreateTickets/Repositories/InMemoryMySqlTicketsRepository.php` (`setInBaza*`), `Backend/src/Ticket/CreateTickets/Application/PushTicket/PushTicketsCommandHandler.php`, `Backend/src/QrOrder/Application/Step/PushToBazaStep.php`, `Backend/src/QrOrder/Application/Job/PushTicketToBazaJob.php`, `Backend/tests/Feature/QrOrder/PushTicketToBazaJobTest.php`.
- Пайплайн qr: `Backend/src/QrOrder/Application/Pipeline/QrOrderPipelineReader.php` + контроллер `getPipeline`.
- DI: `app/Providers/TicketsProvider.php`.
- Миграция-образец проекции: `Backend/database/migrations/2026_06_18_140000_add_projection_fields_to_qr_orders.php` (hasColumn-гарды).
