---
title: Аудит документации vs реальный код (2026-05-29)
status: backlog
created: 2026-05-29
author: technical-writer
target_release: v2.6.0
size_estimate: M (3-4 часа на полный фикс)
related:
  - .claude/docs/API.md
  - .claude/docs/DOMAIN.md
  - .claude/docs/BUSINESS_RULES.md
  - .claude/docs/CONVENTIONS.md
---

# Аудит документации Systo — 2026-05-29

> **Назначение документа:** backlog-item для приведения документации в `.claude/docs/` в соответствие с реальным кодом. Аудит проведён `technical-writer` агентом перед стартом спринта v2.6.0. Связан с TD-23 в `.claude/docs/TECH_DEBT.md`.

## Резюме

- **Всего расхождений:** ~52
  - Тип A (документация описывает то, чего нет в коде): 4
  - Тип B (в коде есть, в документации нет): 30+
  - Тип C (устаревшие/неточные формулировки): 18
- **Критичных** (нужно править срочно): 18
- **Желательных** (можно отложить): 20
- **Косметических**: 14

**Главная причина:** документация описывает состояние v2.4 (примерно до 2026-05-15). После релизов **2026-05-04 (заказы-списки + Location)**, **2026-05-05 (Auto-модуль)**, **2026-05-11 (парковка + festival_id в Baza)**, **2026-05-15 (auto-payment header)**, **2026-05-29 (festival_id + count_auto в Baza)** документация не догнала код.

## Что появилось в коде, но не описано

- Роль `pusher_curator` (мульти-роль pusher + curator) в 8 роутах
- Новый модуль `Backend/src/Auto/` — управление авто заказа-списка (DTO, Repository, Application, sync с Baza)
- Поле `project` в `order_tickets` (обязательное при `createList`)
- Новые эндпоинты: `changePrice`, `changeTicket`, `{id}/auto` (POST/DELETE), `removeTicket/{orderId}/{ticketId}`
- Domain Event `ProcessUserNotificationOrderTicketChanged`
- Фабричный метод агрегата `OrderTicket::toRemoveTicket()`
- Новые History-события: `OrderListCreatedEvent`, `OrderPriceChangedEvent`, `OrderTicketDataChangedEvent`, `OrderTicketDataRemoveEvent`, `OrderCreatedEvent`

---

## API.md

### A. Эндпоинты в документе, но их нет в коде

| Документ | Реальность | Что предложить |
|----------|------------|----------------|
| API.md §1 `GET /api/findUserByEmail/:email` | В `routes/api.php:18` есть `Route::get('findUserByEmail/:email', ...)` с **литеральной** `:email` (синтаксис маршрута битый — Laravel ожидает `{email}`) | Либо считать это багом в коде и завести задачу, либо отразить в API.md что это «нерабочий маршрут» |

### B. Эндпоинты в коде, не описаны в API.md (тип B)

**Order модуль (`/api/v1/order`):**

1. **POST `/api/v1/order/changePrice/{id}`** — `routes/order.php:50`, контроллер `OrderTickets::changePrice` (строка 626). Middleware: `auth:api` + `role:admin`. Body: `{ "price": float (required, gt 0) }`. Возвращает `{ "success": true, "price": float }` или 422. **Документации НЕТ.**

2. **POST `/api/v1/order/changeTicket/{id}`** — `routes/order.php:55`, контроллер `OrderTickets::changeTicket` (строка 664). Middleware: `auth:api` + `role:admin,pusher,pusher_curator,curator`. Body: `{ "email": array<ticketId,email>, "value": array<ticketId,fio> }`. Куратор может менять только свои заказы. **Документации НЕТ.**

3. **POST `/api/v1/order/{id}/auto`** — `routes/order.php:76`, контроллер `OrderTickets::addAuto`. Middleware: `auth:api` + `role:admin,curator,pusher_curator`. Body: `{ "number": string }`. Добавляет авто к заказу-списку. Возвращает `{ "success": true, "auto": {...} }`. **Документации НЕТ.**

4. **DELETE `/api/v1/order/{id}/auto/{autoId}`** — `routes/order.php:84`, контроллер `OrderTickets::removeAuto`. Middleware: `auth:api` + `role:admin,curator,pusher_curator`. **Документации НЕТ.**

5. **DELETE `/api/v1/order/removeTicket/{orderId}/{ticketId}`** — `routes/order.php:80`, контроллер `OrderTickets::removeTicket`. Middleware: `auth:api` + `role:admin,curator,pusher_curator`. **Документации НЕТ.**

**API.md §3 раздел «POST `/api/v1/order/createList`» — устарел:**
- В коде `App\Http\Requests\CreateListOrderRequest` поле `project` **`required|string|max:255`** — обязательное, в API.md не упомянуто.
- Поле `autos: array<string>?` в запросе — в коде есть (контроллер строка 330, добавляет авто через `AutoApplication::addMany`). В API.md отсутствует.
- Поле `phone` в Request-классе **не валидируется**, хотя описано в API.md как опциональное (фактическое поведение совпадает, но требует уточнения).

**API.md §3 раздел «POST `/api/v1/order/toChangeStatus`» — устарел:**
- В роуте middleware теперь `role:seller,admin,pusher,manager,pusher_curator` — нет `pusher_curator` в документации.
- Response 200 теперь содержит дополнительное поле `"order"` (полный обновлённый заказ через `getItemById`) — в API.md этого нет (см. строки 605-615 контроллера).

**API.md §3 раздел `getList` / `getListForFriendly` / `getCuratorList`** — middleware расширены ролью `pusher_curator`:
- `/getListForFriendly` теперь `role:pusher,admin,pusher_curator` (API.md: `role:pusher,admin`).
- `/getCuratorList` теперь `role:curator,admin,pusher_curator` (API.md: `role:curator,admin`).
- `/createFriendly` теперь `role:pusher,pusher_curator` (API.md: `role:pusher`).
- `/createList` теперь `role:curator,pusher_curator` (API.md: `role:curator`).

**API.md §1 «POST `/api/v1/order/create` — особенность для парковки»:**
- В коде есть **второй путь активации auto-payment**: если `ticket_type_id === 'a8af4d68-c3c4-42e7-98b4-f56245033743'`, то auto-payment включается без заголовка (`OrderTickets::create:79`). API.md этого не описывает. Это **критично** для понимания поведения парковочного потока.

### C. Неточности и устаревшие формулировки

- **API.md строка ~36** заголовок «Описание: регистрация нового аккаунта с ролью `pusher`...» — справедливо, но в роли через `is_admin = false` и `role` берётся `AccountRoleHelper::pusher` (см. `AuthController:67`). Документация корректна, но fallback на 401 message «Логин и пароль указан не верно» — пересмотреть (грамматика «указан не верно»).
- **Раздел «Сводная таблица доступа»** — нужно добавить `pusher_curator` ко всем строкам где `role:` встречается, и добавить новые эндпоинты (changePrice, changeTicket, addAuto, removeAuto, removeTicket).
- **API.md строка ~6 (Базовый URL)**: `http://api.tickets.loc/` — оставлено как dev URL, но в `PROJECT_MEMORY.md` упоминается `org.tickets.loc` как локалка. Уточнить.

---

## DOMAIN.md

### A. Описано в DOMAIN.md, но нет в коде

1. **Метод `toChangeTicket(changes[])` агрегата OrderTicket** (раздел Aggregate Roots → OrderTicket) — описан как `toChangeTicket(changes[])` без событий и без описания семантики. **Реальная сигнатура:** `public static function toChangeTicket(OrderTicketDto $orderTicketDto, array $valueMap, array $emailMap): self` (строка 398) — два массива по ticketId. Генерирует **`ProcessCancelTicket`, `ProcessCreateTicket`, `ProcessUserNotificationOrderTicketChanged`, `ProcessGuestNotificationQuestionnaire[]`** + history-событие `OrderTicketDataChangedEvent`. Описание сильно устарело.

2. **Раздел «OrderTicketDto» в DTO** — перечислены поля `festival_id, user_id, email, phone, types_of_payment_id, ticket_type_id, ticket[], priceDto, status, promo_code, id, inviteLink, friendly_id`. Не хватает: **`location_id`, `curator_id`, `project`** (поле добавлено 2026-05-04 миграцией `add_project_to_order_tickets`).

### B. Есть в коде, нет в DOMAIN.md (тип B)

1. **Модуль `Backend/src/Auto/`** (агрегат-сущность для авто заказа-списка) полностью отсутствует в DOMAIN.md. Структура:
   - `AutoDto` (`Backend/src/Auto/Dto/AutoDto.php`) — поля: `id`, `orderTicketId`, `number`, `project`, `curator`, `createdAt`.
   - `AutoRepositoryInterface` + `InMemoryMySqlAutoRepository`.
   - `AutoApplication` — методы `add`, `addMany`, `remove`, `getByOrder`, `pushAllToBasaByOrder`, `removeAllFromBasaByOrder`. Управляет синхронизацией с Baza при APPROVE_LIST.
   - Это полноценный модуль уровня Location, но в DOMAIN.md его нет.

2. **Фабричный метод агрегата `OrderTicket::toRemoveTicket(OrderTicketDto, Uuid $ticketId)`** — `Backend/src/Order/OrderTicket/Domain/OrderTicket.php:342`. Удаляет одного гостя из заказа, генерирует `ProcessCancelTicket` + history `OrderTicketDataRemoveEvent`. В таблице методов агрегата OrderTicket этот метод не упомянут.

3. **History Events** — в DOMAIN.md есть раздел «История заказа», но не перечислены конкретные классы событий:
   - `OrderCreatedEvent` — `Backend/src/History/Domain/Event/OrderCreatedEvent.php`
   - `OrderListCreatedEvent`
   - `OrderStatusChangedEvent`
   - `OrderTicketDataChangedEvent`
   - `OrderTicketDataRemoveEvent`
   - `OrderPriceChangedEvent`

   Каждое событие реализует `HistoryEventInterface`. В DOMAIN.md упоминается «таблица `domain_history`» и `DomainHistoryDto`, но не перечислены сами события. Желательно добавить таблицу с описанием.

4. **Domain Event `ProcessUserNotificationOrderTicketChanged`** — `Backend/src/Order/OrderTicket/Domain/ProcessUserNotificationOrderTicketChanged.php`. Уведомление об изменении ФИО/email гостя. **Отсутствует в таблице «Order уведомления»** DOMAIN.md §Domain Events.

5. **Поле `project` в `OrderTicketDto`** (Backend/src/Order/OrderTicket/Dto/OrderTicket/OrderTicketDto.php:39, гет `getProject()` на строке 279). Не указано в DOMAIN.md.

6. **Метод репозитория `removeAllFromBazaByOrderId`** в `AutoRepositoryInterface` — отсутствует в DOMAIN.md «Repository».

### C. Неточности

- **DOMAIN.md строка ~21** (раздел `OrderTicket` агрегат) — параметр `?Uuid $types_of_payment_id` указан как nullable, но описание поля «null для заказов-списков» — верно, согласовать с миграцией `2026_05_04_100002_make_order_tickets_fields_nullable_for_lists`.

- **DOMAIN.md «Схема связей агрегатов»** — нет узла **Auto** в графе.

- **DOMAIN.md раздел Aggregate Roots → OrderTicket → таблица фабричных методов**:
  - Метод `create(dto, kilter)` — нужно отразить, что добавляется **history-событие `OrderCreatedEvent`** (видно в `OrderTicket.php:83`).
  - Метод `toPaid/toPaidFriendly/toPaidInLiveTicket/toCancel/toCancelLive/toLiveIssued/toDifficultiesArose` — все генерируют `recordHistory(new OrderStatusChangedEvent(...))`. В таблице это не отражено.
  - `createList` — генерирует **`OrderListCreatedEvent`** + получает `?string $locationName`. В DOMAIN.md описание неполное.

- **DOMAIN.md строка про QueueConfiguration**: `QUEUE_CONNECTION=database` — верно для backend. Совпадает с реальностью.

- **DOMAIN.md строка ~ конец «Изменения в схеме БД (ветка questionnaire_multi)»** — раздел очень детальный для одной ветки и сейчас уже неактуален как «изменения», т.к. это давно в master. Можно превратить в раздел «История миграций» или удалить (он занимает место).

---

## BUSINESS_RULES.md

### A. Описано, но не соответствует коду

1. **Матрица переходов для живых билетов (раздел 1)** — таблица гласит, что из `DIFFICULTIES_AROSE` для live-сценария идёт переход в `PAID` (обычный). **В коде (`Status.php:67-70`)** из `DIFFICULTIES_AROSE` доступны только `CANCEL` и `PAID` (одно общее поведение для обычных и live). Уточнение: после `DIFFICULTIES_AROSE` нет возврата к `PAID_FOR_LIVE`, переход только в обычный `PAID`. Документация верная, но формулировка сбивает с толку — в схеме «Живые билеты» написано `DIFFICULTIES_AROSE ──→ PAID (обычный)` — это корректно, но стоит подчеркнуть, что обратно в `PAID_FOR_LIVE` нет возврата.

2. **Раздел 7 «Роли пользователей»** — отсутствует роль `pusher_curator` (мульти-роль: pusher + curator). Из `AccountRoleHelper.php:15-16`. Используется как минимум в 8 роутах (`Backend/routes/order.php`).

### B. Есть в коде, не описано в BUSINESS_RULES.md

1. **Поле `project` в заказе-списке** — обязательное при создании (`CreateListOrderRequest::rules()`). Хранится в `order_tickets.project` (миграция `2026_05_04_120000`). Используется для группировки заказов-списков. Денормализуется в таблицу `auto` (миграция `2026_05_05_150000`). **В разделе 12 «Заказы-списки и Локации» не упомянуто.**

2. **Сущность «Авто заказа-списка» (`auto` таблица)** — миграция `2026_05_05_140000_create_auto_table.php`. Поля: `id, order_ticket_id, number, project, curator, deleted_at, timestamps`. Soft delete. Связь с `order_tickets` через FK с cascade.
   - Бизнес-правило: авто привязываются к заказу-списку (curator или admin). Можно добавлять/удалять до и после `APPROVE_LIST`. При `APPROVE_LIST` все авто заказа пушатся в Baza, при `CANCEL_LIST` / `DIFFICULTIES_AROSE_LIST` — удаляются из Baza.
   - Это **критично** для бизнеса — целый функционал управления парковкой/автомобилями в заказе-списке отсутствует в документации.

3. **Раздел 4 «Фестивали и типы билетов» / парковочные билеты** — описание есть, но не упомянут *альтернативный path к авто-одобрению*: если `ticket_type_id === 'a8af4d68-c3c4-42e7-98b4-f56245033743'` (парковочный?), то auto-payment активируется без заголовка (`OrderTickets::create:79`). Нужно уточнить у tech-lead — это намеренная захардкоженная ссылка на парковочный билет?

4. **Раздел 8 «Приглашения»** — описание актуально, но добавить: при создании заказа через invite-ссылку вызывается `AddOrderInInviteCommand` (`Backend/src/Order/OrderTicket/Application/AddOrderInInvite/`). Это **внутренняя механика**, можно опустить, но `inviteLink` поле в `OrderTicketDto` сохраняется в БД.

5. **Раздел 1.6 «Анкеты гостей» / Telegram-бот** — указан URL `http://77.222.60.58:8000`. Нужно проверить в конфиге, что URL не изменился. Из `PROJECT_MEMORY.md` известно, что бот — чёрный ящик. Документация корректна.

### C. Неточности и устаревшие формулировки

- **BUSINESS_RULES.md §7 «Роли пользователей»** — нужно добавить роль `pusher_curator` (мульти-роль). Описание: «Совмещает pusher + curator: может создавать Friendly-заказы и заказы-списки».

- **BUSINESS_RULES.md §1 «Авто-одобрение»** — корректно описано, но строка про «`actor_type = auto_payment`» дублируется в API.md. Это нормально, но желательно ссылаться.

- **BUSINESS_RULES.md «Заметки по проекту»** — нет раздела про авто (vehicles) в заказе-списке. Нужно отдельную секцию.

- **BUSINESS_RULES.md §4 «Парковочные билеты»** — упоминается флаг `is_parking` на `ticket_type` (соответствует миграции `2026_05_11_120000`). Описание актуальное.

- **BUSINESS_RULES.md §10 «Очереди»** — таблица «Что обрабатывается асинхронно» не содержит `ProcessUserNotificationOrderTicketChanged`.

- **BUSINESS_RULES.md строка «История изменений статусов»** — последняя запись `2026-05-04`, следующих коммитов (2026-05-15 auto-payment, 2026-05-29 baza sync, 2026-05-11 is_parking) — нет.

- **Линки** на `.qwen/docs/` — в BUSINESS_RULES.md строка под таблицей в самом конце «История изменений статусов» содержит `.qwen/docs/BUSINESS_RULES.md` (последняя ячейка таблицы). Должно быть `.claude/docs/BUSINESS_RULES.md` (тип C, косметика).

---

## CONVENTIONS.md

### A. Описано, но не соответствует коду

- **CONVENTIONS.md §2 «Структура модуля Backend»** — стандартная структура указывает на `src/ModuleName/`. Реальный модуль `Backend/src/Auto/` отсутствует подкаталог `Domain/` (нет агрегата, как у PromoCode и Location — это пассивная сущность). Уточнение в CONVENTIONS.md уже есть («Не все модули следуют стандартной структуре»), но желательно добавить `Auto` в список исключений.

- **CONVENTIONS.md §2 «Не все модули...»** — упомянуты: Questionnaire, PromoCode, Ticket/Live, Festival. Нужно дополнить: **History** (не имеет `Domain/AggregateRoot.php`, но имеет `Domain/Event/`), **Auto** (без `Domain/`, как Location).

### B. Есть в коде, не отражено в CONVENTIONS.md

1. **Trait `HasHistory`** (`Backend/src/History/Trait/HasHistory.php`) — используется агрегатом `OrderTicket`. Паттерн «агрегат с историей» можно добавить в §2 как один из паттернов проекта (наряду с AggregateRoot/Repository). Сейчас просто отсутствует упоминание этого trait в CONVENTIONS.

2. **Pattern: история через `recordHistory(HistoryEventInterface)` и `pullHistoryEvents()`** — нигде в CONVENTIONS.md не описан. Появился ~22.04.2026 (миграция `create_domain_history_table`).

3. **TD-22 (Pint warning в CI)** — CONVENTIONS.md §4 «Backend код-стайл» говорит «Laravel Pint — автоформатирование». Но не упомянуто текущее состояние: **Pint в режиме `continue-on-error` в CI** (см. `TECH_DEBT.md` TD-22). Можно добавить заметку «На сегодня (2026-05-29) Pint включён в режиме предупреждения — будет переведён в required после массового применения». Это процессная информация, лучше в `TECH_DEBT.md`, который уже это содержит.

4. **Конвенция о middleware: `role:pusher_curator`** — нигде не объяснено что `pusher_curator` это мульти-роль (одна строка role, не комбинация). Можно добавить заметку в §2 или в BUSINESS_RULES.md.

### C. Неточности

- **CONVENTIONS.md §2 «Регистрация в DI (TicketsProvider)»** — пример показывает `OrderTicketRepositoryInterface`. Корректно. Но нет упоминания, что для нового модуля Auto также нужен binding `AutoRepositoryInterface → InMemoryMySqlAutoRepository`. Можно опустить (это шаблон), но желательно добавить чек-лист «при добавлении модуля».

- **CONVENTIONS.md строка «Источник: Роберт Мартин»** — корректно, ссылка на книгу есть.

- **Линки** — все ссылки в CONVENTIONS.md уже на `.claude/docs/`. Косметических проблем нет.

---

## Рекомендованный приоритет правок

### 1. КРИТИЧНО — править срочно (документация противоречит коду)

1. **API.md: добавить 5 эндпоинтов** — `changePrice`, `changeTicket`, `{id}/auto` (POST), `{id}/auto/{autoId}` (DELETE), `removeTicket/{orderId}/{ticketId}`.
2. **API.md: исправить middleware** в существующих эндпоинтах — добавить `pusher_curator` где нужно (createFriendly, createList, getListForFriendly, getCuratorList, toChangeStatus, changeTicket).
3. **API.md `/createList`**: указать обязательность `project` и опциональный `autos[]`.
4. **API.md `/create`**: описать второй путь auto-payment через захардкоженный `ticket_type_id` (если это намеренно).
5. **BUSINESS_RULES.md §7**: добавить роль `pusher_curator`.
6. **BUSINESS_RULES.md**: добавить новый раздел «Авто заказа-списка» (taблица `auto`, AutoApplication, sync с Baza).
7. **DOMAIN.md**: добавить модуль Auto (DTO + Repository + Application).
8. **DOMAIN.md «OrderTicketDto»**: добавить поля `location_id, curator_id, project`.
9. **DOMAIN.md «История заказа»**: добавить таблицу history-событий.
10. **DOMAIN.md «Order уведомления»**: добавить `ProcessUserNotificationOrderTicketChanged`.
11. **DOMAIN.md `OrderTicket` фабричные методы**: добавить `toRemoveTicket`, исправить сигнатуру `toChangeTicket`.

### 2. ВАЖНО — править в спринте v2.6.0

12. **BUSINESS_RULES.md §12 «Заказы-списки»**: добавить поле `project`.
13. **DOMAIN.md «Схема связей агрегатов»**: добавить Auto в граф.
14. **DOMAIN.md `OrderTicket` фабричные методы**: указать `recordHistory(...)` в каждой строке таблицы.
15. **API.md `/toChangeStatus`**: указать дополнительное поле `order` в Response 200.
16. **API.md «Сводная таблица доступа»**: обновить все строки `role:`.
17. **BUSINESS_RULES.md §10 Очереди**: добавить `ProcessUserNotificationOrderTicketChanged`.
18. **CONVENTIONS.md §2 «Не все модули...»**: добавить History и Auto в исключения.
19. **CLAUDE.md (вне аудита, но связано)**: убрать упоминание RabbitMQ как primary — в config/queue.php стоит `database`.

### 3. КОСМЕТИКА — фоном

20. **BUSINESS_RULES.md строка `.qwen/docs/`** в таблице «История изменений статусов» → заменить на `.claude/docs/`.
21. **BUSINESS_RULES.md «История изменений»** — добавить записи 2026-05-04 (заказы-списки), 2026-05-05 (Auto), 2026-05-11 (is_parking, festival_id в Baza), 2026-05-15 (auto-payment), 2026-05-29 (festival_id + count_auto в changes).
22. **DOMAIN.md раздел «Изменения в схеме БД (ветка questionnaire_multi)»** — переименовать в общий раздел «История миграций» или удалить (один частный случай занимает место).
23. **CONVENTIONS.md §2**: добавить упоминание trait `HasHistory` как одного из паттернов.
24. **DOMAIN.md «История заказа»**: добавить упоминание Trait `HasHistory` и метода `pullHistoryEvents()`.

---

## Дополнительные находки (информативно)

- **`Backend/src/Order/OrderTicket/Dto/OrderTicket/OrderTicketDto::fromState`** имеет аргумент `?Uuid $pusherId` который мапится в `friendly_id`. В DOMAIN.md упомянуто корректно (поле `friendly_id` в DTO), но `pusherId` как алиас может быть запутывающим.
- **`AutoApplication::add`** содержит бизнес-правило: при `APPROVE_LIST` статусе заказа авто сразу пишется в Baza. Это **бизнес-правило**, которое нужно зафиксировать в BUSINESS_RULES.md.
- **Baza миграция 2026_05_29_180000_add_festival_id_and_count_auto_to_changes_table.php** — добавила колонки `festival_id` и `count_auto_tickets` в `changes`. Документация по Baza отсутствует (это известно, см. `PROJECT_MEMORY.md`: «Baza имеет сильный бардак»). Замечание остаётся в TD.
- **Спецификация `.claude/specs/ticket-history.md`** упомянута в `BOARD.md` §Заметки — корректно. Дополнительно её можно упомянуть в DOMAIN.md как «будущая фича».
- **`Backend/src/Festival/Response/TypesOfPaymentDto`** (наряду с `Backend/src/TypesOfPayment/Dto/TypesOfPaymentDto`) — дублирующиеся DTO для одной сущности. Это не нарушение документации, но повод для рефакторинга (можно записать в TECH_DEBT, если ещё нет).
- **TODO в коде**: `OrderTickets::create:79` — `$isAutoPayment = false || (new Uuid(...))->equals(...)` — выражение `false ||` бесполезно. Это **код-смелл**, но не относится к аудиту документации.

---

## Открытые вопросы для уточнения у пользователя / tech-lead

1. **Захардкоженный UUID `a8af4d68-c3c4-42e7-98b4-f56245033743` в OrderTickets::create:79** — это парковочный ticket_type? Намеренно ли он активирует auto-payment без заголовка? Если да — нужно вынести в конфиг и описать в BUSINESS_RULES.md. Если нет — это потенциальный security-issue (любой может создать «парковочный» заказ через подмену ticket_type_id).

2. **Поле `project` в заказе-списке** — что означает? Название мероприятия внутри фестиваля? Сцена? Нужно подсказать business-analyst-у при заполнении BUSINESS_RULES.md.

3. **Роль `pusher_curator`** — описать в `BUSINESS_RULES.md §7`. Кто и когда её получает? Это апгрейд `pusher` после прохождения курса? Или назначается admin?

4. **TODO `false ||` в `OrderTickets::create:79`** — поправить отдельным мелким фиксом (`$isAutoPayment = (new Uuid(...))->equals(...)`).

5. **Маршрут `findUserByEmail/:email` с двоеточием вместо `{email}`** — это баг или нерабочий эндпоинт намеренно? Если баг — исправить и добавить тест.

---

## Файлы документации к правке

- `/home/shaevmv/PhpstormProjects/systo/.claude/docs/API.md`
- `/home/shaevmv/PhpstormProjects/systo/.claude/docs/DOMAIN.md`
- `/home/shaevmv/PhpstormProjects/systo/.claude/docs/BUSINESS_RULES.md`
- `/home/shaevmv/PhpstormProjects/systo/.claude/docs/CONVENTIONS.md`

## Ключевые файлы кода, которые служили опорой для аудита

- `/home/shaevmv/PhpstormProjects/systo/Backend/routes/order.php`
- `/home/shaevmv/PhpstormProjects/systo/Backend/app/Http/Controllers/TicketsOrder/OrderTickets.php`
- `/home/shaevmv/PhpstormProjects/systo/Backend/src/Order/OrderTicket/Domain/OrderTicket.php`
- `/home/shaevmv/PhpstormProjects/systo/Backend/src/Order/OrderTicket/Dto/OrderTicket/OrderTicketDto.php`
- `/home/shaevmv/PhpstormProjects/systo/Backend/src/User/Account/Helpers/AccountRoleHelper.php`
- `/home/shaevmv/PhpstormProjects/systo/Backend/src/Auto/` (весь модуль)
- `/home/shaevmv/PhpstormProjects/systo/Backend/src/Location/Dto/LocationDto.php`
- `/home/shaevmv/PhpstormProjects/systo/Backend/src/History/` (весь модуль)
- `/home/shaevmv/PhpstormProjects/systo/Backend/database/migrations/2026_05_*` (новые миграции)
- `/home/shaevmv/PhpstormProjects/systo/Backend/app/Http/Requests/CreateListOrderRequest.php`
- `/home/shaevmv/PhpstormProjects/systo/Shared/Domain/ValueObject/Status.php`
- `/home/shaevmv/PhpstormProjects/systo/Baza/database/migrations/2026_05_29_180000_add_festival_id_and_count_auto_to_changes_table.php`

---

## История изменений документа

| Дата | Изменение |
|------|-----------|
| 2026-05-29 | Создан. Аудит проведён `technical-writer` агентом перед спринтом v2.6.0 |
