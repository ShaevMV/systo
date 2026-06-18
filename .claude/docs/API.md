# API спецификация Systo Backend

Базовый URL: `http://api.tickets.loc/` (dev) / `https://api.spaceofjoy.ru/` (prod)
Все маршруты имеют префикс `/api` (настраивается в `RouteServiceProvider`).

---

## 1. Аутентификация

### POST `/api/login`
**Middleware:** публичный

**Request:**
```json
{
  "email": "string (required, email)",
  "password": "string (required)"
}
```

**Response 200:**
```json
{
  "status": "success",
  "user": { "id": "...", "email": "...", "name": "...", "role": "...", "is_admin": true },
  "authorisation": { "token": "Bearer ...", "type": "bearer", "lifetime": 1234567890 }
}
```

**Response 401:**
```json
{ "message": "Твой пароль неверный, попробуй еще раз!" }
```

---

### POST `/api/register`
**Middleware:** публичный
**Описание:** регистрация нового аккаунта с ролью `pusher`. Поля `is_admin` и `role` из тела запроса игнорируются (защита от privilege escalation).

**Request:**
```json
{
  "name": "string (required, max:255)",
  "email": "string (required, email, max:255, unique)",
  "phone": "string (required, max:255)",
  "city": "string (required, max:255)",
  "password": "string (required, confirmed, min:6)"
}
```

**Response 200:** аналогично `/login`
**Response 401:** `{ "message": "Логин и пароль указан не верно" }`
**Response 422:** `{ "errors": { "email": ["..."] } }`

---

### POST `/api/registerCurator`
**Middleware:** публичный
**Описание:** регистрация нового аккаунта с ролью `curator` (куратор может создавать заказы-списки). Поля `is_admin` и `role` из тела запроса игнорируются.

**Request / Response:** идентично `/api/register`.

**Frontend route:** `/regCuratorTgdtr64` → после успеха редирект на `/curatorOrders/create` (или на `?nextUrl=`, если это относительный same-origin путь).

---

### POST `/api/logout`
**Middleware:** `auth:api`

**Response:** `{ "message": "Successfully logged out" }`

---

### POST `/api/refresh`
**Middleware:** `auth:api`

**Response:** аналогично `/login` (новый токен)
**Response 401:** вызывает `logOut` на фронтенде

---

### POST `/api/forgot-password`
**Middleware:** публичный

**Request:** `{ "email": "string (required, email)" }`

**Response 200:** `{ "message": "На указанный е-мейл отправлена ссылка для восстановления пароля" }`
**Response 422:** `{ "errors": { "email": "Такой e-mail не зарегистрирован в системе!" } }`

---

### POST `/api/resetPassword`
**Middleware:** публичный

**Request:**
```json
{
  "password": "string (required, confirmed, min:6)",
  "token": "string (required)"
}
```

**Response 200:** аналогично `/login`
**Response 422:** `{ "errors": { "email": ["Не верная ссылка"] } }`

---

### GET `/api/user`
**Middleware:** `auth:api`

**Response:** объект `User` (Eloquent модель)

---

### POST `/api/isCorrectRole`
**Middleware:** `auth:api`

**Request:** `{ "role": ["admin", "seller"] }` — массив ролей

**Response 200:** `{ "status": "success" }`
**Response 403:** `{ "error": "Forbidden" }`

---

### POST `/api/editProfile`
**Middleware:** `auth:api`

**Request:** `{ "name": "string?", "phone": "string?", "city": "string?" }` — любые опциональны

**Response:** `{ "message": "Данные пользователя изменены" }`

---

### POST `/api/editPassword`
**Middleware:** `auth:api`

**Request:** `{ "password": "string (required, confirmed, min:6)" }`

**Response:** `{ "message": "Пароль сменён" }`

---

### GET `/api/findUserByEmail/:email`
**Middleware:** публичный

**Response:** `{ "success": true/false }`

---

## 2. Фестивали

Префикс: **`/api/v1/festival`**

### GET `/api/v1/festival/load`
**Middleware:** публичный

**Query params:** `festival_id` (UUID, required), `is_admin` (bool, optional)

**Response:** массив типов билетов, цен, способов оплаты для оформления заказа.

Каждый элемент `ticketType[]` содержит, среди прочего: `id`, `name`, `price`, `groupLimit`, `isLiveTicket` (bool), **`isParking`** (bool), `description`, `questionnaireTypeId`. Фронт использует `isParking` для переключения формы покупки на ввод данных автомобиля.

---

### GET `/api/v1/festival/loadByTicketType/{ticketTypeId}`
**Middleware:** публичный

**Response:** массив способов оплаты для данного типа билета

---

### GET `/api/v1/festival/getListPrice`
**Middleware:** публичный

**Query params:** `festival_id` (UUID, required)

**Response:** массив цен (все волны)

---

### GET `/api/v1/festival/getTicketTypeList`
**Middleware:** `auth:api` + `admin`

**Response:** список всех типов билетов

---

### GET `/api/v1/festival/getFestivalList`
**Middleware:** публичный

**Response:** список всех фестивалей

---

### POST `/api/v1/festival/create`
**Middleware:** `auth:api` + `admin`

**Описание:** создать фестиваль (каталог фестивалей — мастер на org). CQRS: `FestivalController::create` → `FestivalApplication::create` → `CreateFestivalCommand`/`Handler` → `FestivalRepositoryInterface::create` (БД только в репозитории).

**Request:**
```json
{
  "data": {
    "id": "UUID? (опционально — можно задать с клиента; не передан → генерируется на сервере)",
    "name": "string (required, max:255)",
    "year": "int (required, 2000..2100)",
    "active": "bool (default false)"
  }
}
```

**Response 200:** `{ "success": true, "item": { "id": "UUID", "name": "...", "year": 2026, "active": true }, "message": "Фестиваль создан" }`
**Response 422:** `{ "errors": { "data.name": [...], "data.year": [...] } }` (нет name/year или год вне диапазона)

---

### POST `/api/v1/festival/getList`
**Middleware:** публичный

**Описание:** список фестивалей с фильтрами + сортировкой для админ-CRUD (по образцу `location/getList`). CQRS: `FestivalController::getList` → `FestivalApplication::getList` → `FestivalGetListQuery`/`Handler` → `FestivalRepositoryInterface::getList` (БД только в репозитории, `FilterBuilder` + `Order`). Не путать с `getFestivalList` (старый формат `{festivalDto:[…]}` для витрины, без фильтров). Soft-deleted фестивали в список не попадают.

**Request (фильтр — whitelist):**
```json
{
  "filter": {
    "name": "string? (LIKE)",
    "year": "int? (EQUAL)",
    "active": "bool? (EQUAL)"
  },
  "orderBy": { "name": "asc|desc" }
}
```
- Фильтр — **whitelist** (`name`/`year`/`active`)
- `orderBy.*` допускает только `asc`/`desc`; кривое значение → `Order::none()` (запрос не падает)

**Response 200:** `{ "success": true, "list": [ { "id": "UUID", "name": "...", "year": 2026, "active": true } ] }`

---

### GET `/api/v1/festival/getItem/{id}`
**Middleware:** публичный

**Response 200:** `{ "success": true, "item": { "id": "UUID", "name": "...", "year": 2026, "active": true } }`
**Response 200 (не найден):** `{ "success": false, "message": "Фестиваль не найден" }`

---

### POST `/api/v1/festival/edit/{id}`
**Middleware:** `auth:api` + `admin`

**Описание:** редактировать фестиваль (admin). CQRS: `FestivalController::edit` → `FestivalApplication::edit` → `FestivalEditCommand`/`Handler` → `FestivalRepositoryInterface::editItem`.

**Request:** как у `create` (`data.name` required, `data.year` 2000..2100, `data.active` bool).

**Response 200:** `{ "success": true, "item": { ... }, "message": "Фестиваль отредактирован" }`
**Response 200 (не найден):** `{ "success": false, "message": "Фестиваль не найден" }`
**Response 422:** `{ "errors": { "data.name": [...], "data.year": [...] } }`

---

### DELETE `/api/v1/festival/delete/{id}`
**Middleware:** `auth:api` + `admin`

**Описание:** **soft delete** фестиваля (помечается `deleted_at`, данные не теряются — фестиваль связан с заказами/билетами/типами билетов/промокодами/локациями). CQRS: `FestivalController::delete` → `FestivalApplication::delete` → `FestivalDeleteCommand`/`Handler` → `FestivalRepositoryInterface::remove`. Пишет событие `festival_deleted` в `domain_history`.

**Response 200:** `{ "success": true }`
**Response 200 (не найден):** `{ "success": false, "message": "Фестиваль не найден" }`

---

### GET `/api/v1/festival/getHistory/{id}`
**Middleware:** `auth:api` + `admin`

**Описание:** журнал изменений фестиваля (AF-7). `Festival` — **AggregateRoot** (`Backend/src/Festival/Domain/Festival.php`) с trait `HasHistory` (как `Template`/`OrderTicket`): действия `create`/`edit`/`delete` пишут события в `domain_history` (`aggregate_type = 'festival'`, `actor_type = user`, `actor_id = Auth::id()`). События: `festival_created` (payload `{name, year, active}`), `festival_edited` (payload `{changed: [...]}` — список изменившихся полей), `festival_deleted`.

**Response 200:**
```json
{
  "success": true,
  "history": [
    {
      "event_name": "festival_created",
      "aggregate_type": "festival",
      "payload": { "name": "...", "year": 2026, "active": true },
      "actor_id": "uuid",
      "actor_type": "user",
      "actor_name": "...",
      "actor_email": "...",
      "occurred_at": "ISO8601"
    }
  ]
}
```

---

## 3. Заказы

Префикс: **`/api/v1/order`**

### POST `/api/v1/order/create`
**Middleware:** публичный

**Заголовки (опциональные):**
- `AutoPayment: <token>` — авто-одобрение заказа. Токен сравнивается с `AUTO_PAYMENT_TOKEN` (см. `config/services.php → auto_payment.token`).
  - Заголовок **не передан** → заказ создаётся в `NEW` (штатный flow).
  - Заголовок передан, токен **совпал** и `types_of_payment.is_billing = false` → после создания заказ сразу переводится в `PAID` через `ChangeStatus` (запускается `ProcessCreateTicket` + email с PDF). В `domain_history.actor_type` пишется `auto_payment`, `actor_id = null`.
  - Заголовок передан, токен совпал, но `types_of_payment.is_billing = true` → заголовок **игнорируется**, заказ остаётся в `NEW` (биллинговый flow управляет статусом сам через webhook `/api/v1/order/succes`).
  - Заголовок передан, токен **не совпал** (или `AUTO_PAYMENT_TOKEN` пуст) → `403 { "success": false, "message": "Неверный токен авто-одобрения" }`, заказ **не** создаётся.

**Request:**
```json
{
  "email": "string (required, email)",
  "phone": "string (required)",
  "city": "string (required)",
  "ticket_type_id": "UUID (required, exists)",
  "types_of_payment_id": "UUID (required, exists)",
  "guests": [{ "value": "string", "email": "string?" }],
  "name": "string?",
  "invite": "UUID?",
  "promo_code": "string?",
  "comment": "string?",
  "festival_id": "UUID",
  "price": "float"
}
```

**Особенность для парковочных билетов (`ticket_type.is_parking = true`):**
- На бэке формат запроса **не отличается** — приходит обычный `guests[]` с полями `value` и `email`
- Фронт склеивает три поля автомобиля в одну строку `value` форматом `"{ГосНомер} / {Марка} / {ФИО водителя}"`. Email водителя кладётся в `guests[].email` без изменений
- `name` (ФИО владельца) для парковки **не передаётся** — пустая строка
- Несколько машин = несколько элементов в `guests[]`

**Response 200:**
```json
{
  "success": true,
  "message": "Мы удачно зарегистрировали ваш заказ..."
}
```

**Response 403 (битый токен авто-одобрения):**
```json
{
  "success": false,
  "message": "Неверный токен авто-одобрения"
}
```

**Response (error):**
```json
{
  "success": false,
  "message": "...",
  "link": "...",
  "file": "..."
}
```

---

### POST `/api/v1/order/createFriendly`
**Middleware:** `auth:api` + `role:pusher`

**Request:** аналогично `create`, но `price` — required, `guests` — обязателен с полем `number`

---

### POST `/api/v1/order/getList`
**Middleware:** `auth:api` + `role:seller,admin`

**Request (body — фильтр):**
```json
{
  "email": "string?", "price": "float?", "typesOfPayment": "UUID?",
  "status": "string?", "promoCode": "string?", "typePrice": "UUID?",
  "festivalId": "UUID?", "city": "string?", "questionnaire": "string?",
  "friendlyId": "UUID?", "filter": [...], "orderBy": {...}
}
```

**Response:** `{ "list": {...}, "totalNumber": {...} }`

---

### POST `/api/v1/order/getListForFriendly`
**Middleware:** `auth:api` + `role:pusher,admin`

**Response:** аналогично `getList`

---

### POST `/api/v1/order/createList`
**Middleware:** `auth:api` + `role:curator`

Создание заказа-списка куратором. **Не имеет цены и типа билета.**

**Request:**
```json
{
  "email": "string (required, email — получатель билетов)",
  "phone": "string?",
  "festival_id": "UUID (required)",
  "location_id": "UUID (required, exists:locations)",
  "guests": [{ "value": "string (required)", "email": "string?" }],
  "name": "string?",
  "comment": "string?"
}
```

**Response 200:** `{ "success": true, "message": "Список зарегистрирован..." }`
**Response (error):** `{ "success": false, "message": "...", "link": "...", "file": "..." }`

---

### POST `/api/v1/order/getListsList`
**Middleware:** `auth:api` + `role:admin,manager`

Список всех заказов-списков (для admin/manager). Фильтрация: `WHERE curator_id IS NOT NULL`.

**Request (фильтр):**
```json
{
  "festivalId": "UUID (required)",
  "email": "string?", "name": "string?",
  "locationId": "UUID?", "status": "string?"
}
```

**Response:** `{ "list": [...], "totalNumber": {...} }`

---

### POST `/api/v1/order/getCuratorList`
**Middleware:** `auth:api` + `role:curator,admin`

Список заказов-списков куратора (admin видит все). Для куратора — `WHERE curator_id = Auth::id()`.

**Request/Response:** аналогично `getListsList`.

---

### POST `/api/v1/order/toChangeStatus/{id}`
**Middleware:** `auth:api` + `role:seller,admin,pusher,manager`

Дополнительно для list-статусов (`APPROVE_LIST`, `CANCEL_LIST`, `DIFFICULTIES_AROSE_LIST`): внутри метода проверяется, что `Auth::user()->role` ∈ `{admin, manager}`. Иначе → 403.

**Request:**
```json
{
  "status": "string (required)",
  "comment": "string? (required если DIFFICULTIES_AROSE или DIFFICULTIES_AROSE_LIST)",
  "liveList": ["int"]? (required если LIVE_TICKET_ISSUED)
}
```

**Response 200:**
```json
{
  "success": true,
  "status": { "name": "...", "humanStatus": "...", "listCorrectNextStatus": [...] }
}
```

**Response 422:** `{ "success": false, "errors": {...} }`

---

### GET `/api/v1/order/getUserList`
**Middleware:** `auth:api`

**Response:** `{ "list": [...] }`

---

### GET `/api/v1/order/getItem/{id}`
**Middleware:** `auth:api`

**Response 200:** `{ "order": {...} }`
**Response 404:** `{ "errors": { "error": "Заказ не найден" } }`

---

### GET `/api/v1/order/getTicketPdf/{id}`
**Middleware:** `auth:api`

**Response 200:** `{ "success": true, "listUrl": ["..."] }`
**Response 422:** `{ "success": false, "message": "..." }`

---

### GET `/api/v1/order/getHistory/{id}`
**Middleware:** `auth:api` + `admin`

**Response 200:**
```json
{
  "success": true,
  "history": [
    {
      "event_name": "order.status.changed",
      "aggregate_type": "order_ticket",
      "payload": { "fromStatus": "new", "toStatus": "paid" },
      "actor_id": "uuid или null",
      "actor_type": "USER|SYSTEM|ARTISAN",
      "occurred_at": "ISO8601"
    }
  ]
}
```

---

### ANY `/api/v1/order/succes`
**Middleware:** публичный (webhook платёжной системы)

**Request:**
```json
{
  "data": {
    "metadata": { "order_id": "UUID" },
    "receipts": [{ "link_to_receipt": "..." }]
  },
  "type": "payment.completed | payment.refund"
}
```

**Response:** `{ "status": 0 }`

---

## 3.1. QR-заказы (приём от витрины qr.spaceofjoy.ru)

Префикс: **`/api/v1/qrOrder`**

Канал приёма заказов от внешней витрины **qr.spaceofjoy.ru** (отдельный сервис/репозиторий). Заказ сохраняется в таблицу `qr_orders` (payload as-is + проекция для фильтров). **`id` заказа qr == `id` заказа org** (идемпотентность приёма).

- **`type_order`** ∈ `regular` / `friendly` / `list` / `live`
- **`status`** — строки от qr (например `создан` / `оплачен` / `отменён`), не enum
- `total_price` — целые рубли (int)
- В `domain_history.actor_type` для всех событий qr-заказа пишется `qr` (S2S-канал, не человек)

### POST `/api/v1/qrOrder/create`
**Middleware:** `qr.ingest` (S2S, заголовок `X-QR-Token`)

**Описание:** приём заказа от витрины (API №1). Идемпотентно: повторный приём заказа с тем же `id` не создаёт дубль. Сервисный ключ qr предъявляется в заголовке `X-QR-Token` и сверяется со списком валидных ключей (`config services.qr_ingest.tokens`).

**Request:** расширенный JSON-контракт qr (`order_id`, `user`, `price`, `order_data`, `guests[]`, см. `.claude/specs/admin-qr-orders-prompt.md §2`).

**Response 200:** `{ "success": true, "order_id": "UUID", "message": "Заказ принят" }`
**Response 422:** `{ "success": false, "message": "..." }` (невалидный контракт)

---

### ~~POST `/api/v1/qrOrder/changeStatus/{id}`~~ (удалён)

**Удалён из `routes/qrOrder.php`.** Заказ от витрины приходит **уже в статусе «оплачен»**, и `qrOrder/create` сразу запускает выдачу билетов (PDF/письма) — **один раз** (защита по `issued_at`, повторный приём того же `id` не выдаёт билеты снова). Отдельной смены статуса (API №2) больше нет.

---

### POST `/api/v1/qrOrder/getList`
**Middleware:** `auth:api` + `admin` (read-only, содержит ПДн)

**Описание:** список принятых qr-заказов для админки org. Только просмотр — заказы не создаются/не меняются отсюда (это S2S-канал витрины). Паттерн `Location`: `QrOrderGetListQuery` → `QrOrderGetListQueryHandler` через QueryBus, БД только в репозитории (`getList` + `countList` через Shared `FilterBuilder`/`Order`).

**Request (фильтр + пагинация):**
```json
{
  "filter": {
    "email": "string? (LIKE)",
    "city": "string? (LIKE)",
    "status": "string? (EQUAL)",
    "festival_id": "UUID? (EQUAL)",
    "type_order": "string? (EQUAL: regular/friendly/list/live)"
  },
  "orderBy": { "created_at": "asc|desc" },
  "page": 1,
  "perPage": 20
}
```
- Фильтр — **whitelist** (только перечисленные поля попадают в `WHERE`)
- `page` зажат снизу к 1; `perPage` — диапазон 1..100, иначе fallback на 20
- `orderBy.*` допускает только `asc`/`desc`; кривое значение → `Order::none()` (запрос не падает)

**Response 200:**
```json
{
  "success": true,
  "list": [
    {
      "id": "UUID", "email": "...", "status": "оплачен",
      "festival_id": "UUID", "type_order": "regular", "city": "...",
      "phone": "...", "total_price": 4200,
      "issued_at": "ISO8601|null", "created_at": "ISO8601"
    }
  ],
  "totalNumber": { "totalCount": 42 }
}
```
- Проекция списка — `QrOrderItemForListResponse` (snake_case, **без `payload`** — он тяжёлый, отдаётся только в `getItem`)

---

### GET `/api/v1/qrOrder/getItem/{id}`
**Middleware:** `auth:api` + `admin`

**Response 200:** `{ "success": true, "item": {...} }` — полный заказ, включая `payload` (гости/цены/локация)
**Response 404:** `{ "success": false, "message": "Заказ не найден" }`

---

### GET `/api/v1/qrOrder/getHistory/{id}`
**Middleware:** `auth:api` + `admin`

**Описание:** таймлайн истории qr-заказа (`created` → `status_changed` → `issued`). `actor_type` для всех событий — `qr`.

**Response 200:**
```json
{
  "success": true,
  "history": [
    {
      "event_name": "created|status_changed|step_create_tickets|step_send_order_email|step_push_to_baza|step_send_telegram|step_create_live_tickets|step_link_live|step_send_list_email|step_send_live_email|issued",
      "aggregate_type": "qr_order",
      "payload": { "from": "...", "to": "..." },
      "actor_type": "qr",
      "occurred_at": "ISO8601"
    }
  ]
}
```
- В таймлайн добавлены события шагов выдачи (`step_*`) — детальный путь заказа от приёма до выдачи (см. §3.4 «Система писем», `domain_history`)

---

### GET `/api/v1/qrOrder/getTicketPdf/{id}`
**Middleware:** `auth:api` + `admin`

**Описание:** ссылки на PDF билетов заказа (скачивание из админки). Файлы — `storage/tickets/{ticketId}.pdf`. Пустой список — PDF ещё генерируется или билетов нет.

**Response 200:** `{ "success": true, "listUrl": ["https://.../storage/tickets/{ticketId}.pdf"] }`

---

### GET `/api/v1/qrOrder/getPipeline/{id}`
**Middleware:** `auth:api` + `admin`

**Описание:** весь путь заказа (Ф5 системы писем) в одном ответе для экрана «видеть весь путь»: приём → билеты (PDF) → письма (статусы доставки) → история шагов. Читается через `QrOrderPipelineReader` (БД только в репозиториях).

**Response 200:**
```json
{
  "success": true,
  "order": { "...полный заказ (как getItem)..." },
  "tickets": [{ "ticket_id": "UUID", "pdf_url": "https://.../storage/tickets/{ticketId}.pdf" }],
  "history": [{ "event_name": "...", "payload": {...}, "actor_type": "qr", "occurred_at": "ISO8601" }],
  "emails": [{ "id": "UUID", "event": "...", "recipient": "...", "status": "...", "...": "..." }]
}
```
**Response 404:** `{ "success": false, "message": "Заказ не найден" }`

---

## 3.4. Доставка писем по шаблонам

Префикс: **`/api/v1/emailDelivery`** (+ публичный пиксель `/api/v1/mail/open/...` + S2S `/api/v1/emailNotification/send`)

Контроль пути письма «дошло / где застряло» (модуль `Backend/src/EmailDelivery/` — пассивная сущность, как `QrOrder`/`Location`, БД только в репозитории). Спека: `.claude/specs/email-delivery-system.md`.

- **Статусы письма** (`EmailStatus`): `queued` → `sending` → `sent` (передано на SMTP) → `[delivered → opened]` / `failed` (+ текст ошибки = «где застряло»). `bounced` — отскок. Из `failed`/`bounced` — повтор в `queued`.
- `delivered`/`bounced` требуют транзакционного провайдера с вебхуками (**AF-6**, ещё не подключён); `opened` ставится пикселем прочтения (Ф3).
- **`source`** письма ∈ `qr_pipeline` (выдача билетов qr) / `qr_intake` (S2S-приём от витрины) / `org_event` (события org).
- **Событие письма** (`EmailEvent`, 15 кодов) → дефолтный slug шаблона: `order_created`→`orderToCreate`, `order_paid`→`orderToPaid`, `order_paid_friendly`→`TypeTicketMailOrderToPaidFriendly1`, `order_paid_live`→`orderToPaidLiveTicket`, `order_cancel`→`orderToCancel`, `order_changed`→`orderToChangeTicket`, `order_difficulties`→`orderToDifficultiesArose`, `order_live_issued`→`orderToLiveTicketIssued`, `list_approved`→`orderListApproved`, `list_cancel`→`orderListCancel`, `list_difficulties`→`orderListDifficultiesArose`, `user_registered`→`newUser`, `password_reset`→`passwordResets`, `invite`→`invate`, `questionnaire`→`questionnaire`.
- Slug выбирается привязкой шаблона по событию (`templateBinding`, см. §8.3) с fallback на дефолтный slug события.
- В `domain_history` для писем — `aggregate_type='email'`, события `email_queued`/`email_sending`/`email_sent`/`email_failed`/`email_opened`. Письма от qr пишутся `actor_type=qr`, системные — `system`.

### POST `/api/v1/emailDelivery/getList`
**Middleware:** `auth:api` + `admin` (read-only, содержит ПДн)

**Описание:** список писем для админки. Фильтр — **whitelist** (только перечисленные поля попадают в `WHERE`).

**Request (фильтр + пагинация):**
```json
{
  "filter": {
    "recipient": "string? (LIKE)",
    "status": "string? (EQUAL)",
    "event": "string? (EQUAL)",
    "source": "string? (EQUAL: qr_pipeline/qr_intake/org_event)",
    "festival_id": "UUID? (EQUAL)",
    "aggregate_id": "UUID? (EQUAL)"
  },
  "orderBy": { "created_at": "asc|desc" },
  "page": 1,
  "perPage": 20
}
```
- `page` зажат снизу к 1; `perPage` — диапазон 1..100, иначе fallback на 20
- `orderBy.*` допускает только `asc`/`desc`; кривое значение → `Order::none()`

**Response 200:**
```json
{
  "success": true,
  "list": [
    {
      "id": "UUID", "event": "order_paid", "recipient": "...",
      "subject": "...", "status": "sent", "attempts": 1, "error": null,
      "source": "qr_pipeline", "festival_id": "UUID",
      "aggregate_type": "qr_order", "aggregate_id": "UUID",
      "sent_at": "ISO8601|null", "opened_at": "ISO8601|null", "created_at": "ISO8601"
    }
  ],
  "totalNumber": { "totalCount": 42 }
}
```
- Проекция списка — `EmailMessageItemForListResponse` (snake_case, **без `meta`/`mailable`** — тяжёлые; `error` включён намеренно, чтобы сразу видеть «где застряло»)

---

### GET `/api/v1/emailDelivery/getItem/{id}`
**Middleware:** `auth:api` + `admin`

**Response 200:** `{ "success": true, "item": {...}, "history": [ { "event_name": "email_sent", "payload": {...}, "actor_type": "qr|system", "occurred_at": "ISO8601" } ] }`
**Response 404:** `{ "success": false, "message": "Письмо не найдено" }`

---

### POST `/api/v1/emailDelivery/resend/{id}`
**Middleware:** `auth:api` + `admin`

**Описание:** повторная отправка письма (re-dispatch `SendEmailJob` по `id`, Mailable читается из колонки `mailable`). Переводит письмо обратно в `queued`.

**Response 200:** `{ "success": true, "message": "Письмо поставлено на повторную отправку" }`
**Response 404:** `{ "success": false, "message": "Письмо не найдено" }`

---

### GET `/api/v1/mail/open/{token}.gif`
**Middleware:** публичный + `throttle:120,1`

**Описание:** пиксель прочтения письма (Ф3). Помечает письмо `opened` (идемпотентно, только из `sent`/`delivered`) и отдаёт прозрачный 1×1 GIF. `token` — случайный (`tracking_token`, ≠ `id`, паттерн `[A-Za-z0-9]+`) — заказы не перебрать. За флагом `config('mail_delivery.open_tracking')` (env `MAIL_OPEN_TRACKING`, default `false` — факт прочтения это ПДн, 152-ФЗ): при выключенном флаге `<img>`-пиксель в письмо не дописывается.

**Response 200:** прозрачный 1×1 GIF (`Content-Type: image/gif`, `Cache-Control: no-store`)

---

### POST `/api/v1/emailNotification/send`
**Middleware:** `qr.ingest` (S2S, заголовок `X-QR-Token`)

**Описание:** S2S-приём писем от витрины qr (Ф4) — для не-заказных писем (регистрация, сброс пароля и т.п.), инициированных на витрине. Slug выбирается привязкой по событию (см. §8.3) с fallback на дефолтный slug события; письмо отслеживается через `MailDispatcher` (`source=qr_intake`). Идемпотентность по `external_id`.

**Request:**
```json
{
  "event": "string (required, EmailEvent::isValid)",
  "email": "string (required, email получателя)",
  "vars": { "...": "..." },
  "festival_id": "UUID?",
  "order_type": "string? (regular/friendly/list/live)",
  "ticket_type_id": "UUID?",
  "subject": "string?",
  "aggregate_id": "UUID?",
  "external_id": "string? (ключ идемпотентности)"
}
```

**Response 200:** `{ "success": true, "email_id": "UUID", "message": "Письмо принято" }`
**Response 200 (повтор по `external_id`):** `{ "success": true, "message": "Уже принято ранее (идемпотентно)" }`
**Response 422:** `{ "success": false, "message": "Неизвестное событие письма" }` / `{ "success": false, "message": "Не передан email получателя" }`
**Response 401:** без/с невалидным `X-QR-Token` (middleware `qr.ingest`)

---

## 4. Билеты

Префикс: **`/api/v1/ticket`**

### GET `/api/v1/ticket/live/{cash?}`
**Middleware:** публичный

**Response:** `{ "success": true, "number": 1234 }` — расшифрованный номер живого билета

---

## 5. Анкеты

Префикс: **`/api/v1/questionnaire`**

### POST `/api/v1/questionnaire/load`
**Middleware:** `auth:api` + `admin`

**Request (фильтр):** `{ "email": "...", "telegram": "...", "vk": "...", "is_have_in_club": "...", "status": "..." }`

**Response:** `{ "success": true, "questionnaireList": [...] }`

---

### POST `/api/v1/questionnaire/send/{orderId}/{ticketId}`
**Middleware:** публичный

**Описание:** Заполнение анкеты гостя. Тип анкеты определяется автоматически по `order_ticket.ticket_type_id → questionnaire_type_id`. Если тип не найден — fallback на гостевую анкету (`guest`).

**Request:** `{ "questionnaire": { "email": "...", "telegram": "...", ...поля из questionnaire_type.questions... } }`
- `ticket_id` и `order_id` проставляются автоматически
- `status` = `NEW` (ожидает одобрения)
- Валидация динамическая из `questionnaire_type.questions` (см. `QuestionnaireValidationService`)

**Response 200:** `{ "success": true, "message": "Спасибо большие, ваши анкетные данные зарегистрированы..." }`
**Response 422:** `{ "success": false, "errors": {...}, "message": "Ошибка валидации" }`

---

### POST `/api/v1/questionnaire/sendNewUser`
**Middleware:** публичный

**Описание:** Заполнение анкеты нового пользователя. Используется тип анкеты с кодом `new_user`.

**Request:** `{ "questionnaire": { "telegram": "string (5-32, regex ^[a-zA-Z0-9_]+$)", "agy": "int?", ...поля из questionnaire_type.questions... } }`

**Response 200:** `{ "success": true, "message": "Анкета нового пользователя сохранена" }`
**Response 422:** `{ "success": false, "errors": {...}, "message": "Ошибка валидации" }`

---

### POST `/api/v1/questionnaire/notification/{id}`
**Middleware:** `auth:api` + `admin`

**Request:** `{ "email": "string (required, email)" }`

**Response:** `{ "success": true, "message": "Ссылка на анкету отправлена" }`

---

### POST `/api/v1/questionnaire/approve/{id}`
**Middleware:** `auth:api` + `admin`

**Response:** `{ "success": true, "message": "Анкета одобрена" }`

---

### GET `/api/v1/questionnaire/get/{id}`
**Middleware:** `auth:api` + `admin`

**Response:** `{ "success": true, "questionnaire": {...} }`

---

### GET `/api/v1/questionnaire/getQuestionnaireTypeByOrderTicket/{orderId}/{ticketId}`
**Middleware:** публичный

**Response 200:** `{ "success": true, "questionnaire_type": {...} }`
**Response 404:** `{ "success": false, "message": "Тип анкеты не найден" }`

---

### GET `/api/v1/questionnaire/getByOrderTicket/{orderId}/{ticketId}`
**Middleware:** публичный

**Описание:** Получить заполненную анкету по заказу и билету.

**Response 200:** `{ "success": true, "questionnaire": {...} }`
**Response 404:** `{ "success": false, "message": "Анкета не найдена" }`

---

## 6. Типы анкет

Префикс: **`/api/v1/questionnaireType`**

| Метод | Маршрут | Middleware | Описание |
|-------|---------|------------|----------|
| POST | `/getList` | публичный | Список с фильтрацией |
| GET | `/getItem/{id}` | публичный | Один тип анкеты |
| GET | `/getByCode/{code}` | публичный | Найти тип анкеты по коду |
| POST | `/create` | публичный | Создать (UUID в `data.id`) |
| POST | `/edit/{id}` | публичный | Редактировать |
| DELETE | `/delete/{id}` | публичный | Удалить |

---

## 7. Промокоды

Префикс: **`/api/v1/promoCode`** | **Middleware:** `auth:api` + `admin`

| Метод | Маршрут | Описание |
|-------|---------|----------|
| GET | `/getListPromoCode` | Список всех |
| GET | `/getItemPromoCode/{idPromoCode?}` | Один промокод |
| POST | `/savePromoCode/{idPromoCode?}` | Создать/обновить |
| POST | `/find/{promoCode?}` | Применить промокод (расчёт скидки) |
| POST | `/savePromoCodeForBot/{idPromoCode?}` | Создать для бота (UPPERCASE + random) |

**savePromoCode Request:**
```json
{
  "name": "string (required, unique)",
  "discount": "float (required, numeric, > 0, <= 100 если is_percent)",
  "is_percent": "bool (required)",
  "active": "bool (required)",
  "limit": "int? (nullable)",
  "type_ticket_id": "UUID?"
}
```

---

## 8. Типы билетов

Префикс: **`/api/v1/ticketType`**

| Метод | Маршрут | Middleware | Описание |
|-------|---------|------------|----------|
| POST | `/getList` | публичный | Список с фильтрацией |
| GET | `/getItem/{id}` | публичный | Один тип билета |
| POST | `/create` | публичный | Создать (UUID в `data.id`) |
| POST | `/edit/{id}` | публичный | Редактировать |
| DELETE | `/delete/{id}` | публичный | Удалить |
| GET | `/getBlade` | публичный | Список доступных шаблонов email/pdf |

---

## 8.0. Волны цен типа билета

Префикс: **`/api/v1/ticketTypePrice`**

Управление таблицей `ticket_type_price` (волны цен — см. BUSINESS_RULES §4). Каждая волна — это запись `{ price, before_date }` для конкретного `ticket_type_id`. Активной считается ближайшая волна, у которой `before_date >= CURDATE()`.

| Метод | Маршрут | Middleware | Описание |
|-------|---------|------------|----------|
| POST | `/getList` | публичный | Список волн **с обязательным** `filter.ticket_type_id` |
| GET | `/getItem/{id}` | публичный | Одна волна |
| POST | `/create` | `auth:api` + `admin` | Создать волну (UUID в `data.id` опционально) |
| POST | `/edit/{id}` | `auth:api` + `admin` | Редактировать |
| DELETE | `/delete/{id}` | `auth:api` + `admin` | Soft delete (запись помечается как удалённая) |

**getList Request:**
```json
{
  "filter": { "ticket_type_id": "UUID (required, exists:ticket_type,id)" },
  "orderBy": { "before_date": "asc" }
}
```
- `filter.ticket_type_id` — обязателен, иначе вернётся 422 (нет «получи всё»)
- `orderBy.*` допускает только `asc` / `desc`; некорректное значение игнорируется (fallback на `Order::none()`)

**create / edit Request:**
```json
{
  "data": {
    "ticket_type_id": "UUID (required, exists:ticket_type,id)",
    "price": "float (required, > 0, < 1 000 000)",
    "before_date": "date (required, after_or_equal:today)"
  }
}
```

**Защита от дурака (валидация):**
- `price` — обязательно > 0 и < 1 000 000
- `before_date` — валидная дата, не в прошлом
- `ticket_type_id` — должен существовать в `ticket_type`
- На фронте — кнопка `Сохранить` неактивна при невалидной форме, удаление через `confirm()`

**Response 200 (create/edit):**
```json
{
  "success": true,
  "item": { "id": "...", "ticket_type_id": "...", "price": 4200, "before_date": "..." },
  "message": "Волна цены создана"
}
```

**Response 422 (валидация):**
```json
{
  "errors": {
    "data.price": ["Цена должна быть больше 0"],
    "data.before_date": ["Дата не может быть в прошлом"]
  }
}
```

---

## 8.1. Локации (для заказов-списков)

Префикс: **`/api/v1/location`**

| Метод | Маршрут | Middleware | Описание |
|-------|---------|------------|----------|
| POST | `/getList` | публичный | Список локаций (фильтр: `name`, `festival_id`, `active`) |
| GET | `/getItem/{id}` | публичный | Одна локация |
| POST | `/create` | `auth:api` + `admin` | Создать (UUID в `data.id`) |
| POST | `/edit/{id}` | `auth:api` + `admin` | Редактировать |
| DELETE | `/delete/{id}` | `auth:api` + `admin` | Удалить |

**Поля Location DTO:**
```json
{
  "id": "UUID",
  "name": "string (required)",
  "description": "string?",
  "festival_id": "UUID (required, exists:festivals)",
  "questionnaire_type_id": "UUID? (exists:questionnaire_type)",
  "email_template": "string?",
  "pdf_template": "string?",
  "active": "bool (default true)"
}
```

---

## 8.2. Шаблоны писем и PDF-билетов (AF-3)

Префикс: **`/api/v1/template`** | **Middleware всех роутов:** `auth:api` + `admin`

Редактируемые из админки шаблоны писем и PDF-билетов. Рендер из БД через **Mustache** (logic-less, RCE-безопасен) с **fallback на blade-файл** (пока активной записи нет — рендерится старый blade). `slug` = имени blade-файла → нулевая миграция привязки. Чтение списка — через QueryBus (whitelist фильтров), БД только в репозитории. Спека: `.claude/specs/template-system.md`.

| Метод | Маршрут | Описание |
|-------|---------|----------|
| POST | `/getList` | Список шаблонов (фильтр: `kind`, `slug`, `active`; `orderBy`) |
| GET | `/getItem/{id}` | Один шаблон (включая `body`/`draft_body`) |
| POST | `/create` | Создать (UUID в `data.id` опционально) |
| POST | `/edit/{id}` | Редактировать метаданные/тело |
| POST | `/activate/{id}` | Включить/выключить шаблон (`{ "active": bool }`) — деактивация = откат на blade |
| POST | `/saveDraft/{id}` | Сохранить черновик (`draft_body`) — прод (`body`) не затрагивается |
| POST | `/publish/{id}` | Опубликовать `body` + снапшот в `template_versions` |
| GET | `/versions/{id}` | История версий тела (снапшоты, новые сверху) |
| POST | `/rollback/{id}/{versionId}` | Откат `body` к версии (создаёт новую версию-«откат») |
| GET | `/history/{id}` | Журнал изменений шаблона (`domain_history`, `aggregate_type=template`): кто/что/когда — `template_created`/`edited`/`activated`/`published`/`rolled_back` |
| GET | `/variables/{slug}?kind=email\|pdf` | Палитра плейсхолдеров для редактора |
| POST | `/preview` | Предпросмотр на тестовых данных (`throttle:20,1`) |

**Тело шаблона (`data` в create/edit):**
```json
{
  "id": "UUID?",
  "slug": "string (= имени blade: pdf / orderToPaid / ...)",
  "kind": "string (email | pdf)",
  "engine": "string (html | mjml — mjml только для email, default html)",
  "title": "string",
  "body": "string (исходник Mustache)",
  "draft_body": "string?",
  "active": "bool (default true)",
  "is_system": "bool (default false — импортирован из blade)"
}
```

**preview Request:** `{ "kind": "email|pdf", "slug": "string?", "body": "string" }`
- `kind=email` → `200 { "success": true, "html": "..." }` (фронт в `<iframe>`)
- `kind=pdf` → `200` поток `application/pdf` (через тот же DomPDF, что в проде)
- Ошибка синтаксиса Mustache → `422 { "success": false, "message": "Ошибка рендера шаблона: ..." }`
- Рендерит **только фикстуры** (`PlaceholderCatalog::sample()`) — без ПДн реальных заказов

**publish Request:** `{ "body": "string", "comment": "string?" }` (автор — из `Auth::id()`)

**Response 200 (create/edit/activate/publish/rollback):**
```json
{ "success": true, "item": { ... }, "message": "Шаблон опубликован" }
```
**Response 404:** `{ "success": false, "message": "..." }` (шаблон не найден)

---

## 8.3. Привязки шаблонов (AF-3, Часть B)

Префикс: **`/api/v1/templateBinding`** | **Middleware всех роутов:** `auth:api` + `admin`

Маппинг `(event, festival_id, order_type, ticket_type_id, types_of_payment_id)` → `email_template_id`/`pdf_template_id` + `is_default`. `NULL`-поля = wildcard. Резолвер выбирает самую специфичную привязку (`types_of_payment` > `event` > `ticket_type` > `order_type` > `festival`); нет совпадения → `is_default`; нет дефолта → старый slug (обратная совместимость, в т.ч. легаси `types_of_payment.email`). Ось `types_of_payment` (AF-9) даёт «под каждого продавца своё письмо/PDF». Применяется при выдаче билетов (`InMemoryMySqlTicketsRepository::getTicket`) и при выборе slug в системе писем (см. §3.4). Спека: `.claude/specs/template-aggregate-and-bindings.md`.

| Метод | Маршрут | Описание |
|-------|---------|----------|
| POST | `/getList` | Список всех привязок |
| GET | `/events` | Каталог событий писем для селектора: `{ success, list: [{ value, label }] }` (15 событий из `EmailEvent`) |
| GET | `/getItem/{id}` | Одна привязка (404 если нет) |
| POST | `/create` | Создать (UUID в `data.id` опционально) |
| POST | `/edit/{id}` | Редактировать |
| DELETE | `/delete/{id}` | Удалить |

**Тело привязки (`data` в create/edit):**
```json
{
  "id": "UUID?",
  "event": "string? (nullable=wildcard; валидируется EmailEvent::isValid)",
  "festival_id": "UUID? (nullable=wildcard)",
  "order_type": "string? (nullable=wildcard: regular/friendly/list/live)",
  "ticket_type_id": "UUID? (nullable=wildcard)",
  "types_of_payment_id": "UUID? (nullable=wildcard; AF-9 — тип оплаты = внешний продавец/магазин)",
  "email_template_id": "UUID?",
  "pdf_template_id": "UUID?",
  "is_default": "bool (default false)",
  "active": "bool (default true)"
}
```

**Валидация (422):**
- `event` (если передан) — должен быть валидным событием → `{ "success": false, "message": "Неизвестное событие письма: ..." }`
- Нужен хотя бы один шаблон (`email_template_id` или `pdf_template_id`) → `«Укажите хотя бы один шаблон (письма или PDF)»`
- В слот письма — только email-шаблон, в слот PDF — только pdf-шаблон
- Не больше одной активной дефолт-привязки → `«Активная привязка по умолчанию уже существует»`

**Response 200 (create/edit):** `{ "success": true, "item": {...}, "message": "Привязка создана|сохранена" }`
**Response 404 (edit/getItem):** `{ "success": false, "message": "..." }`

---

## 9. Способы оплаты

Префикс: **`/api/v1/typesOfPayment`**

| Метод | Маршрут | Middleware | Описание |
|-------|---------|------------|----------|
| POST | `/getList` | публичный | Список с фильтрацией |
| GET | `/getItem/{id}` | публичный | Один способ оплаты |
| POST | `/create` | публичный | Создать |
| POST | `/edit/{id}` | публичный | Редактировать |
| DELETE | `/delete/{id}` | публичный | Удалить |

---

## 10. Пользователи (админка)

Префикс: **`/api/v1/account`** | **Middleware:** `auth:api` + `admin`

| Метод | Маршрут | Описание |
|-------|---------|----------|
| POST | `/getList` | Список с фильтрацией |
| GET | `/getItem/{email}` | Один пользователь |
| POST | `/edit/{id}` | Редактировать |
| POST | `/changeRole/{id}` | Сменить роль |

**changeRole Request:** `{ "role": "string (admin/seller/pusher/manager)" }`

---

## 11. Приглашения

Префикс: **`/api/v1/invite`**

### GET `/api/v1/invite/getInviteLink`
**Middleware:** `auth:api`

**Response (есть):** `{ "message": "...", "link": "https://..." }`
**Response (нет):** `{ "message": "Формирование ссылки-приглашения будет доступно после одобрения...", "link": null }`

---

### GET `/api/v1/invite/isCorrectInviteLink/{userId}`
**Middleware:** публичный

**Response:** `{ "success": true/false }`

---

## Middleware — описание

| Middleware | Alias | Описание |
|------------|-------|----------|
| **CORS** | global | Проверяет Origin против белого списка |
| **Authenticate** | `auth:api` | JWT через `php-open-source-saver/jwt-auth` |
| **QrIngestAuth** | `qr.ingest` | S2S-канал qr→org: заголовок `X-QR-Token` сверяется со списком валидных ключей (`config services.qr_ingest.tokens`, env `QR_INGEST_TOKENS`). Закрывает `qrOrder/create` и `emailNotification/send`. Без/невалидный токен → 401 |
| **IsAdmin** | `admin` | Проверка `is_admin = true` или `role = 'admin'` |
| **CheckRole** | `role:role1,role2` | Проверка роли пользователя |
| **Bot** | `bot` | Заголовок `auth-token` == `PCf4yeeM8prVGee3zbArQGQP2eGpPHsV` |
| **Throttle:api** | global | 60 запросов/мин на IP/user |

## Сводная таблица доступа

| Категория | Маршруты |
|-----------|----------|
| **Публичные** | login, register, forgot-password, resetPassword, festival/* (кроме getTicketTypeList/create/edit/delete), festival/getList, festival/getItem, order/create, order/succes, ticket/live, questionnaireType/*, ticketType/*, typesOfPayment/*, location/getList, location/getItem, ticketTypePrice/getList, ticketTypePrice/getItem, invite/isCorrectInviteLink, questionnaire/send, questionnaire/sendNewUser, questionnaire/getQuestionnaireTypeByOrderTicket, questionnaire/getByOrderTicket, mail/open/{token}.gif (throttle:120,1) |
| **Только auth** | user, logout, refresh, isCorrectRole, editProfile, editPassword, order/getUserList, order/getItem, order/getTicketPdf, invite/getInviteLink |
| **admin** | festival/getTicketTypeList, festival/create, festival/edit, festival/delete, festival/getHistory, account/*, promoCode/*, questionnaire/load, questionnaire/notification, questionnaire/approve, questionnaire/get, order/getHistory, location/{create,edit,delete}, ticketTypePrice/{create,edit,delete}, template/* (getList, getItem, create, edit, activate, saveDraft, publish, versions, rollback, history, variables, preview), templateBinding/* (getList, events, getItem, create, edit, delete), emailDelivery/* (getList, getItem, resend) |
| **role: seller,admin** | order/getList |
| **role: pusher,admin** | order/getListForFriendly, order/createFriendly |
| **role: curator** | order/createList |
| **role: curator,admin** | order/getCuratorList |
| **role: admin,manager** | order/getListsList |
| **role: seller,admin,pusher,manager** | order/toChangeStatus (для list-статусов внутри проверка admin/manager) |
| **bot** | promoCode/savePromoCodeForBot |
| **qr.ingest** (S2S, `X-QR-Token`) | qrOrder/create, emailNotification/send (от витрины qr) |
| **admin** (qr) | qrOrder/getList, qrOrder/getStats, qrOrder/getItem, qrOrder/getHistory, qrOrder/getTicketPdf, qrOrder/getPipeline |

---

## ⚠️ Кандидаты на вынос (org → admin-only, переезд на qr.spaceofjoy.ru)

> **Контекст:** org становится **внутренней admin-only системой** (создание билетов + контроль доставки в Baza). Публичная часть — продажа билетов, публичные формы/витрина — переезжает на **qr.spaceofjoy.ru**. Эндпоинты ниже **НЕ удалять сейчас** — пометить и удалить **в релизе после cutover** на qr. Если для эндпоинта остаётся внутреннее (admin) применение — он сохраняется.

| Эндпоинт | Статус | Примечание |
|----------|--------|------------|
| `POST /api/v1/order/create` (публичный) | ⚠️ DEPRECATED → qr.spaceofjoy.ru | Публичное оформление заказа уезжает на витрину qr. На org остаётся приём через `qrOrder/create` (S2S). Удалить в релизе после cutover. |
| `ANY /api/v1/order/succes` (webhook платёжки) | ⚠️ Кандидат на вынос (уточнить) | Биллинг/оплата — ценность витрины qr. Webhook, вероятно, уезжает на qr. Уточнить, не остаётся ли часть оплаты на org. |
| `GET /api/v1/festival/load`, `loadByTicketType`, `getListPrice`, `getFestivalList` (публичные) | ⚠️ Кандидат на вынос (уточнить) | Питают **публичную витрину покупки**. Каталог фестивалей/типов/цен остаётся мастером на org (см. pivot), но публичные read-эндпоинты витрины могут переехать на qr или стать read-каналом для qr. Уточнить перед удалением. |
| `POST /api/v1/questionnaire/send`, `sendNewUser`, `getByOrderTicket`, `getQuestionnaireTypeByOrderTicket` (публичные) | ⚠️ Кандидат на вынос (уточнить) | Публичное заполнение анкет гостями. Может уехать на qr вместе с публичным flow заказа. Уточнить. |

**НЕ кандидаты на вынос** (остаются на org как admin-only): весь `qrOrder/*`, admin-CRUD (`ticketType`, `promoCode`, `location`, `ticketTypePrice`, `account`, `questionnaire/load|approve|get`), `order/getList|getListsList|getCuratorList|toChangeStatus|getHistory`, `qrOrder/getList|getItem|getHistory`.
