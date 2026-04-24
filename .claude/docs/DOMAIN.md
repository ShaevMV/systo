# Доменная модель Systo

## Aggregate Roots

### OrderTicket

**Путь:** `Backend/src/Order/OrderTicket/Domain/OrderTicket.php`

```php
class OrderTicket extends AggregateRoot {
    use HasHistory; // запись истории изменений агрегата

    public const CHILD_TICKET_TYPE_ID = 'c3d4e5f6-a7b8-9012-cdef-345678901235';

    Uuid $festival_id;
    Uuid $user_id;
    Uuid $types_of_payment_id;
    PriceDto $price;          // priceItem, count, discount, totalPrice
    Status $status;            // из Shared/Domain/ValueObject/Status
    array $ticket;             // GuestsDto[]
    Uuid $id;
    ?string $promo_code;
}
```

**Фабричные методы (команды):**

| Метод | Статус | Domain Events | Описание |
|-------|--------|---------------|----------|
| `create(dto, kilter)` | NEW | `ProcessUserNotificationNewOrderTicket` | Создание заказа |
| `toPaid(dto, comment?, externalPromoCode?)` | PAID | `ProcessCreateTicket`, `ProcessUserNotificationOrderPaid`, `ProcessGuestNotificationQuestionnaire[]`, `ProcessTelegramByQuestionnaireSend` | Подтверждение |
| `toPaidInLiveTicket(dto, kilter)` | PAID_FOR_LIVE | `ProcessCreateTicket`, `ProcessUserNotificationOrderPaidLiveTicket`, `ProcessGuestNotificationQuestionnaire[]` | Подтверждение live |
| `toCancel(dto)` | CANCEL | `ProcessCancelTicket`, `ProcessUserNotificationOrderCancel` | Отмена |
| `toCancelLive(dto)` | CANCEL_FOR_LIVE | `ProcessCancelTicket`, `ProcessCancelLiveTicket` | Отмена live |
| `toLiveIssued(dto, liveNumber[])` | LIVE_TICKET_ISSUED | `ProcessCreateTicket`, `ProcessGuestNotificationQuestionnaire[]`, `ProcessPushLiveTicket[]` | Выдача живых |
| `toDifficultiesArose(dto, comment)` | DIFFICULTIES_AROSE | `ProcessCancelTicket`, `ProcessUserNotificationOrderDifficultiesArose` | Трудности |
| `toChangeTicket(changes[])` | — | — | Изменение данных билета (записывает историю если `!empty($changes)`) |
| `toProcessGuestNotificationQuestionnaire(dto)` | — | `ProcessGuestNotificationQuestionnaire[]` | Только рассылка гостям |

Все методы (кроме `toProcessGuestNotificationQuestionnaire`) вызывают `recordHistory()` через trait `HasHistory`.

---

### Ticket

**Путь:** `Backend/src/Ticket/CreateTickets/Domain/Ticket.php`

```php
class Ticket extends AggregateRoot {
    string $name;
    int $kilter;
    Uuid $aggregateId;
    string $email;
}
```

**Методы:**

| Метод | Domain Events | Описание |
|-------|---------------|----------|
| `newTicket(TicketResponse)` | `ProcessCreatingQRCode` | Создание билета + генерация QR |

---

### Account

**Путь:** `Backend/src/User/Account/Domain/Account.php`

```php
class Account extends AggregateRoot {
    Uuid $id;
    string $email;
    string $phone;
    string $city;
    ?string $name;
    string $role;
}
```

**Методы:**

| Метод | Domain Events | Описание |
|-------|---------------|----------|
| `creatingNewAccount(id, dto, password)` | `ProcessAccountNotification` | Создание аккаунта + email с паролем |

---

### PromoCode

**Путь:** `Backend/src/PromoCode/Domain/PromoCode.php`

```php
class PromoCode extends AggregateRoot {
    Uuid $id;
    string $name;
    float $discount;
    bool $is_percent;
    ?int $limit;
}
```

**Пассивный агрегат** — нет фабричных методов и Domain Events. Используется только для чтения/валидации.

---

### Questionnaire

**Путь:** `Backend/src/Questionnaire/Domain/Questionnaire.php`

```php
class Questionnaire extends AggregateRoot {
    // Состояние хранится в QuestionnaireTicketDto
}
```

**Методы:**

| Метод | Domain Events | Описание |
|-------|---------------|----------|
| `toApprove(dto)` | `ProcessInviteLinkQuestionnaire` | Одобрение анкеты → invite link |
| `toSendTelegram(dto)` | `ProcessTelegramSend` | Уведомление в Telegram |

---

## Value Objects

### Shared (базовые)

| VO | Путь | Описание |
|----|------|----------|
| **Uuid** | `Shared/Domain/ValueObject/Uuid.php` | UUID v4 (Ramsey), методы: `random()`, `value()`, `equals()` |
| **Status** | `Shared/Domain/ValueObject/Status.php` | Статусы заказов с матрицей переходов. **См. BUSINESS_RULES.md §1** |
| **Enum** | `Shared/Domain/ValueObject/Enum.php` | Абстрактный Enum: `__callStatic()`, `fromString()`, `randomValue()` |
| **StringValueObject** | `Shared/Domain/ValueObject/StringValueObject.php` | Абстрактная обёртка `?string` |
| **IntValueObject** | `Shared/Domain/ValueObject/IntValueObject.php` | Абстрактная обёртка `int`, метод `isBiggerThan()` |
| **BoolValueObject** | `Shared/Domain/ValueObject/BoolValueObject.php` | Абстрактная обёртка `?bool` |
| **Second** | `Shared/Domain/Second.php` | VO для секунд (extends IntValueObject) |
| **SecondsInterval** | `Shared/Domain/SecondsInterval.php` | Интервал Second от/до с валидацией |

### Модуль-specific

| VO | Путь | Описание |
|----|------|----------|
| **CommentForOrder** | `Order/OrderTicket/ValueObject/` | `id`, `user_id`, `comment`, `is_checkin`, `created_at` |
| **QuestionnaireStatus** | `Questionnaire/Domain/ValueObject/` | Константы: `NEW`, `APPROVE` |
| **StatusForBillingValueObject** | `Billing/ValueObject/` | `payment.completed`, `payment.refund` |
| **DeviceValueObject** | `Billing/ValueObject/` | `android`, `ios`, `desktop` |

---

## DTO

### Order Module

| DTO | Файл | Поля |
|-----|------|------|
| **OrderTicketDto** | `Order/OrderTicket/Dto/OrderTicket/` | `festival_id`, `user_id`, `email`, `phone`, `types_of_payment_id`, `ticket_type_id`, `ticket[]`, `priceDto`, `status`, `promo_code`, `id`, `inviteLink`, `friendly_id` |
| **GuestsDto** | `Order/OrderTicket/Dto/OrderTicket/` | `value`, `email`, `number`, `id`, `festivalId` |
| **PriceDto** | `Order/OrderTicket/Dto/OrderTicket/` | `priceItem`, `count`, `discount`, `totalPrice`, `price` |
| **CommentDto** | `Order/OrderTicket/Dto/` | `user_id`, `order_tickets_id`, `comment` |

### Ticket Module

| DTO | Файл | Поля |
|-----|------|------|
| **TicketDto** | `Ticket/CreateTickets/Dto/` | `order_ticket_id`, `name`, `festival_id`, `id`, `kilter`, `email`, `number` |
| **TicketResponse** | `Ticket/CreateTickets/Responses/` | Ответ для API |

### PromoCode Module

| DTO | Файл | Поля |
|-----|------|------|
| **PromoCodeDto** | `PromoCode/Response/` | `id`, `name`, `discount`, `isPercent`, `limit`, `ticket_type_id`, `festival_id` |
| **LimitPromoCodeDto** | `PromoCode/Dto/` | `count`, `limit` |
| **ExternalPromoCodeDto** | `PromoCode/Response/` | `promocode` |

### User Module

| DTO | Файл | Поля |
|-----|------|------|
| **AccountDto** | `User/Account/Dto/` | `id`, `email`, `phone`, `city`, `name`, `is_admin`, `role` |
| **UserInfoDto** | `User/Account/Dto/` | `id`, `email`, `city`, `role`, `phone`, `name` |

### Questionnaire Module

| DTO | Файл | Поля |
|-----|------|------|
| **QuestionnaireTicketDto** | `Questionnaire/Dto/` | `id`, `email` (nullable), `phone` (nullable), `telegram` (nullable), `vk` (nullable), `agy` (nullable, ?string), `status`, `userId`, `orderId`, `ticketId`, `questionnaireTypeId` (UUID), `extraData` (JSON — динамические поля из `questionnaire_type.questions`), `link` |

### Festival Module

| DTO | Файл | Поля |
|-----|------|------|
| **FestivalDto** | `Festival/DTO/` | Данные фестиваля |
| **PriceDto** | `Festival/DTO/` | `uuid`, `price`, `beforeDate` |
| **TicketTypeDto** | `Festival/Response/` | `id`, `name`, `price`, `groupLimit`, `festivalList[]`, `priceList[]`, `isLiveTicket` |
| **TypesOfPaymentDto** | `Festival/Response/` | `id`, `name`, `card`, `is_billing` |

### History Module

| DTO | Файл | Поля |
|-----|------|------|
| **SaveHistoryDto** | `History/Dto/` | `aggregateId` (string), `event` (HistoryEventInterface), `actorId` (?string), `actorType` (string) |
| **DomainHistoryDto** | `History/Dto/` | `aggregateId` (string), `aggregateType` (string), `eventName` (string), `payload` (array), `actorId` (?string), `actorType` (string), `occurredAt` (Carbon) |

---

## Domain Events

Все события — queued jobs (`ShouldQueue`), обрабатываются через Laravel Bus chain.

### Order уведомления

| Event | Данные | Что делает |
|-------|--------|-----------|
| **ProcessUserNotificationNewOrderTicket** | `email`, `kilter`, `ticketTypeId`, `festival` | Email "заказ создан" |
| **ProcessUserNotificationOrderPaid** | `email`, `tickets[]`, `ticketTypeId`, `comment?`, `promocode?` | Email с PDF билетами |
| **ProcessUserNotificationOrderPaidLiveTicket** | `email`, `ticketTypeId`, `typeOrPaymentId`, `kilter` | Email для live |
| **ProcessUserNotificationOrderCancel** | `email`, `ticketTypeId` | Email об отмене |
| **ProcessUserNotificationOrderDifficultiesArose** | `orderId`, `email`, `comment`, `ticketTypeId` | Email о трудностях |
| **ProcessUserNotificationOrderLiveTicketIssued** | `email`, `ticketTypeId` | Email о выдаче live |

### Ticket операции

| Event | Данные | Что делает |
|-------|--------|-----------|
| **ProcessCreateTicket** | `orderId`, `quests[]` | Создаёт билеты через `TicketApplication::createList()` |
| **ProcessCancelTicket** | `orderId` | Отменяет билеты через `TicketApplication::cancelTicket()` |
| **ProcessCancelLiveTicket** | `orderId`, `guest[]` | Отменяет + пушит live с номерами |
| **ProcessPushLiveTicket** | `liveNumber`, `ticketId?` | Присваивает номер живому билету |
| **ProcessCreatingQRCode** | `TicketResponse` | Генерирует PDF + QR |

### User операции

| Event | Данные | Что делает |
|-------|--------|-----------|
| **ProcessAccountNotification** | `email`, `password` | Email с данными нового аккаунта |
| **ProcessPasswordResets** | `User` | Генерирует токен, отправляет ссылку сброса |

### Questionnaire операции

| Event | Данные | Что делает |
|-------|--------|-----------|
| **ProcessGuestNotificationQuestionnaire** | `email`, `orderId`, `ticketId` | Ссылка на анкету (если нет существующей + тип активен) |
| **ProcessInviteLinkQuestionnaire** | `email` | Email со ссылкой-приглашением |
| **ProcessTelegramSend** | `username` | POST на Telegram-бот |
| **ProcessTelegramByQuestionnaireSend** | `email` | Ищет анкету по email → Telegram |
| **ProcessReplayNotificationQuestionnaire** | `email`, `id` | Повторная отправка ссылки |

### Публикация событий

```php
// Асинхронно (по умолчанию)
Bus::chain($aggregateRoot->pullDomainEvents())->dispatch();

// Синхронно
Bus::chain($list)->onConnection('sync')->dispatch();

// С задержкой
Bus::chain($list)->delay(now()->addMinutes($delay))->dispatch();
```

---

## Repository

### OrderTicketRepositoryInterface

| Метод | Описание |
|-------|----------|
| `create(OrderTicketDto): bool` | Создание заказа |
| `getUserList(Uuid): OrderTicketItemForListResponse[]` | Заказы пользователя |
| `findOrder(Uuid): ?OrderTicketDto` | Поиск по ID |
| `getItem(Uuid): ?OrderTicketItemResponse` | Детали заказа |
| `getList(Filters): OrderTicketItemForListResponse[]` | Админ-список с фильтрами |
| `getFriendlyList(Filters): OrderTicketItemForFriendlyListResponse[]` | Friendly-список |
| `changeStatus(Uuid, Status, array): bool` | Смена статуса |

### TicketsRepositoryInterface

| Метод | Описание |
|-------|----------|
| `createTickets(TicketDto): bool` | Создание билета |
| `deleteTicketsByOrderId(Uuid): bool` | Удаление по заказу |
| `getListIdByOrderId(Uuid, bool): Uuid[]` | IDs билетов |
| `getTicket(Uuid, bool): TicketResponse` | Получить билет |
| `setInBaza(TicketResponse): bool` | Синхронизация с БД Baza |
| `setInBazaLive(int, ?Uuid): bool` | Привязка live-номера |
| `checkLiveNumber(int): bool` | Проверка уникальности номера |

### PromoCodeInterface

| Метод | Описание |
|-------|----------|
| `find(string, Uuid, Uuid): ?PromoCodeDto` | Поиск с проверкой лимита |
| `getList(): PromoCodeDto[]` | Все промокоды |
| `getItem(Uuid): ?PromoCodeDto` | Один промокод |
| `createOrUpdate(PromoCodeDto): bool` | Создать/обновить |

### UserRepositoriesInterface

| Метод | Описание |
|-------|----------|
| `create(AccountDto, string): bool` | Создание аккаунта |
| `findAccountByEmail(string): ?UserInfoDto` | Поиск по email |
| `findAccountById(Uuid): ?UserInfoDto` | Поиск по ID |
| `getList(AccountGetListFilter, Order): UserInfoDto[]` | Список с фильтрами |
| `edit(Uuid, UserInfoDto): bool` | Редактирование |
| `chanceRole(Uuid, string): bool` | Смена роли |

### QuestionnaireRepositoryInterface

**Изменения:** Поля анкеты перенесены в JSON-колонку `data`. Стандартные поля (`agy`, `phone`, `telegram`, `vk`, `email`) стали nullable. Добавлена связь с типом анкеты через `questionnaireTypeId`.

| Метод | Описание |
|-------|----------|
| `create(QuestionnaireTicketDto): bool` | Создание анкеты (данные в `data` JSON) |
| `getList(Filters): Collection` | Список с фильтрами (по `data->$.fieldName`) |
| `existByEmail(string): bool` | Проверка email (nullable поле) |
| `findByEmail(?string): ?QuestionnaireTicketDto` | Поиск по email (nullable) |
| `get(int): QuestionnaireTicketDto` | По ID (с парсингом `data` в `extraData`) |
| `findByOrderIdAndTicketId(Uuid, Uuid): ?QuestionnaireTicketDto` | Поиск по заказу и билету |
| `findByEmailAndQuestionnaireType(string, Uuid): ?QuestionnaireTicketDto` | Поиск по email + типу анкеты |
| `cacheStatus(int, string): bool` | Обновить статус |

### TicketTypeRepositoryInterface

| Метод | Описание |
|-------|----------|
| `getList(TicketTypeGetListFilter, Order): Collection` | Список типов билетов с фильтрами |
| `getItem(Uuid): TicketTypeDto` | По ID |
| `editItem(Uuid, TicketTypeDto): bool` | Редактировать |
| `create(TicketTypeDto): bool` | Создать |
| `remove(Uuid): bool` | Удалить |

### HistoryRepositoryInterface

**Путь:** `Backend/src/History/Repositories/HistoryRepositoryInterface.php`
**Реализация:** `InMemoryMySqlHistoryRepository` (таблица `domain_history`)

| Метод | Описание |
|-------|----------|
| `save(SaveHistoryDto): void` | Сохранить событие истории |
| `getByAggregateId(string): DomainHistoryDto[]` | Получить всю историю агрегата по ID |

---

## Схема связей агрегатов

```
 Festival (read-only)
     |
     | festival_id
     v
 Account ←── user_id ── OrderTicket ←── friendly_id ── Account (referrer)
     |                    |
     |                    | order_ticket_id
     |                    v
     |               Ticket
     |                    |
     |                    | orderId + ticketId
     |                    v
     └──────────── Questionnaire

 PromoCode ── ticket_type_id ── OrderTicket
     |
     | order_tickets_id
     v
 ExternalPromoCode

 OrderTicket ──── aggregate_id ──── DomainHistory (таблица domain_history)
 Ticket      ────────────────────── DomainHistory
 (любой агрегат с trait HasHistory записывает события в общую таблицу истории)
```

---

## Criteria и Filters

Система построения типобезопасных запросов:

```php
// Создание фильтра
$filter = new Filter(
    new FilterField('email'),
    FilterOperator::CONTAINS(),
    new FilterValue('@example.com')
);
$filters = new Filters([$filter]);

// Применение в репозитории
$criteria = new Criteria($filters, Order::createDesc('created_at'), 0, 50);
$this->filterBuilder->build($this->model, $criteria->filters);
```

**Операторы:** `=`, `!=`, `>`, `<`, `LIKE`, `CONTAINS`, `NOT_CONTAINS`
**OrderType:** `asc`, `desc`, `none`

---

## Изменения в схеме БД (ветка questionnaire_multi)

### Таблица `questionnaire`

**Удалённые колонки** (перенесены в `data` JSON):
`agy`, `howManyTimes`, `questionForSysto`, `is_have_in_club`, `creationOfSisto`, `activeOfEvent`, `whereSysto`, `musicStyles`, `name`, `vk`

**Новая структура:**
```sql
id INT PRIMARY KEY,
email VARCHAR(255) NULL,
phone VARCHAR(50) NULL,
telegram VARCHAR(100) NULL,
vk VARCHAR(255) NULL,
status VARCHAR(50),
questionnaire_type_id INT,
data JSON,  -- динамические поля из questionnaire_type.questions
user_id VARCHAR(36),
order_id VARCHAR(36),
ticket_id VARCHAR(36),
link VARCHAR(255) NULL,
created_at TIMESTAMP,
updated_at TIMESTAMP
```

### FilterBuilder

**Изменение:** проверка значений изменена с `!== null` на `!empty()` для корректной обработки `0` и пустых строк.

```php
// Было
if ($filter->value()->value() !== null) { ... }

// Стало
if (!empty($filter->value()->value())) { ... }
```

### Исправление вывода полей из JSON `data` (2026-04-11)

**Проблема:** В админке анкет все поля отображались как "—" (прочерк), хотя данные присутствовали в JSON-колонке `data`.

**Найденные проблемы:**

1. **`phone` колонка имела NOT NULL ограничение** — при вставке записей где данные только в JSON `data`, Laravel пытался вставить NULL в корневую колонку `phone` что вызывало ошибку.

2. **`InMemoryMySqlQuestionnaireRepository::getList()` использовал `each()` вместо `map()`** — это означало что модели НЕ конвертировались в DTO, и контроллер возвращал сырые данные модели (корневые колонки) вместо данных из JSON `data`.

**Исправления:**

1. Миграция `2026_04_11_140005_fix_questionnaire_phone_nullable.php` — сделала `phone` NULLable.

2. Изменён `each()` на `map()` в `InMemoryMySqlQuestionnaireRepository::getList()`:
```php
// Было
->each(fn (QuestionnaireModel $model) => QuestionnaireTicketDto::fromState($model->toArray()));

// Стало
->map(fn (QuestionnaireModel $model) => QuestionnaireTicketDto::fromState($model->toArray()));
```

**Тесты:**
- Unit: `tests/Unit/Questionnaire/Dto/QuestionnaireTicketDtoTest.php` (4 теста)
- Feature: `tests/Feature/Questionnaire/QuestionnaireDataFieldsApiTest.php` (2 теста)
- Сидер: `QuestionnaireTestDataSeeder` — создаёт 3 тестовые анкеты с данными только в JSON `data`

---

## Модуль Location (Локации) — ветка feat/curator-list-tickets (2026-04-23)

### Новый агрегат / модуль Location

**Путь:** `Backend/src/Location/`

```
Location/
├── Application/ (GetList, GetItem, Create, Edit, Delete + LocationApplication)
├── Dto/LocationDto.php
├── Repository/LocationRepositoryInterface.php + InMemoryMySqlLocationRepository.php
└── Response/LocationGetListResponse.php
```

**Таблица `locations`:**
```sql
id UUID PRIMARY KEY, festival_id UUID, name VARCHAR(255),
active BOOLEAN DEFAULT TRUE, sort INT DEFAULT 0,
questionnaire_type_id UUID NULL,  -- тип анкеты для участников этой локации
created_at TIMESTAMP, updated_at TIMESTAMP
```

**API:** `POST /api/v1/location/getList|create|edit/{id}`, `GET /api/v1/location/getItem/{id}`, `DELETE /api/v1/location/delete/{id}` — только `admin`

**Frontend:** `FrontEnd/src/store/modules/LocationModule/`, `FrontEnd/src/components/Location/`, `FrontEnd/src/views/location/`

---

## Роли куратора (2026-04-23)

Добавлены в `AccountRoleHelper`:

| Роль | Константа | Описание |
|------|-----------|---------|
| `curator` | `AccountRoleHelper::curator` | Куратор — создаёт списочные билеты для участников |
| `curator_pusher` | `AccountRoleHelper::curator_pusher` | Куратор + права pusher |

---

## Статусы заказов куратора (2026-04-23)

Добавлены в `Shared/Domain/ValueObject/Status.php`:

| Константа | Значение | Описание |
|-----------|---------|---------|
| `NEW_FOR_LIST` | `new_for_list` | Куратор создал заказ, ожидает модерации |
| `PENDING_CURATOR` | `pending_curator` | Заказ на модерации у администратора |

**Матрица переходов:**
```
new_for_list → [pending_curator, cancel]
pending_curator → [paid, cancel, difficulties_arose]
```

---

## Тип билета "Список" — is_list_ticket (2026-04-23)

Добавлен флаг `is_list_ticket` (boolean, default false) в таблицу `ticket_type`.

- Обновлён `TicketTypesModel.$fillable`
- Обновлён `TicketTypeDto` — конструктор, `fromState()`, геттер `isListTicket()`
- Обновлён `TicketTypeGetListFilter` — поддержка фильтрации по `is_list_ticket`
- Обновлён `InMemoryTicketTypeRepository` — фильтрация по `is_list_ticket`
- Frontend: чекбокс в `TicketTypeItem.vue`

---

## Заказ куратора — order_tickets (2026-04-23)

Добавлены nullable поля в таблицу `order_tickets`:
- `curator_id UUID NULL` — UUID пользователя-куратора
- `location_id UUID NULL` — UUID локации

Обновлён `OrderTicketDto` — nullable поля `curator_id`, `location_id`, геттеры `getCuratorId()`, `getLocationId()`.

**API:** `POST /api/v1/order/createCurator` — middleware `auth:api, role:curator,curator_pusher`

**Тело запроса:**
```json
{
  "festival_id": "UUID",
  "location_id": "UUID",
  "guests": [{"value": "Имя", "email": "email@example.com"}],
  "price": 0,
  "comment": "string?"
}
```

**Логика:** тип билета с `is_list_ticket=true` находится автоматически по `festival_id`. Создаётся заказ со статусом `new_for_list`. Билеты не генерируются до одобрения администратором.

---

## Тип анкеты curator_participant (2026-04-23)

UUID: `e5f6a7b8-c9d0-1234-ef01-567890123456`, код: `curator_participant`.

Тип анкеты определяется по `location.questionnaire_type_id`, а не по `ticket_type.questionnaire_type_id`.

**Поля:**

| Name | Тип | Required | Описание |
|------|-----|----------|----------|
| `participantName` | string | ✅ | Имя участника |
| `contact` | string | ✅ | Контакт |
| `photo` | file | ❌ | Фото для бейджа (загружается отдельно через `uploadPhoto`) |

**Загрузка фото:** `POST /api/v1/questionnaire/uploadPhoto/{orderId}/{ticketId}` (публичный). Файл сохраняется в `storage/app/public/badges/{festival_id}/{ticketId}.ext`, возвращает `photo_url`, который затем передаётся полем `photo` в `/send/{orderId}/{ticketId}`.
