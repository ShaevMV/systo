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

## 3. Заказы

Префикс: **`/api/v1/order`**

### POST `/api/v1/order/create`
**Middleware:** публичный

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
| **IsAdmin** | `admin` | Проверка `is_admin = true` или `role = 'admin'` |
| **CheckRole** | `role:role1,role2` | Проверка роли пользователя |
| **Bot** | `bot` | Заголовок `auth-token` == `PCf4yeeM8prVGee3zbArQGQP2eGpPHsV` |
| **Throttle:api** | global | 60 запросов/мин на IP/user |

## Сводная таблица доступа

| Категория | Маршруты |
|-----------|----------|
| **Публичные** | login, register, forgot-password, resetPassword, festival/*, order/create, order/succes, ticket/live, questionnaireType/*, ticketType/*, typesOfPayment/*, location/getList, location/getItem, ticketTypePrice/getList, ticketTypePrice/getItem, invite/isCorrectInviteLink, questionnaire/send, questionnaire/sendNewUser, questionnaire/getQuestionnaireTypeByOrderTicket, questionnaire/getByOrderTicket |
| **Только auth** | user, logout, refresh, isCorrectRole, editProfile, editPassword, order/getUserList, order/getItem, order/getTicketPdf, invite/getInviteLink |
| **admin** | festival/getTicketTypeList, account/*, promoCode/*, questionnaire/load, questionnaire/notification, questionnaire/approve, questionnaire/get, order/getHistory, location/{create,edit,delete}, ticketTypePrice/{create,edit,delete} |
| **role: seller,admin** | order/getList |
| **role: pusher,admin** | order/getListForFriendly, order/createFriendly |
| **role: curator** | order/createList |
| **role: curator,admin** | order/getCuratorList |
| **role: admin,manager** | order/getListsList |
| **role: seller,admin,pusher,manager** | order/toChangeStatus (для list-статусов внутри проверка admin/manager) |
| **bot** | promoCode/savePromoCodeForBot |
