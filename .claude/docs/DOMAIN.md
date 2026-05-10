# Доменная модель Systo

## Aggregate Roots

### OrderTicket

**Путь:** `Backend/src/Order/OrderTicket/Domain/OrderTicket.php`

```php
class OrderTicket extends AggregateRoot {
    use HasHistory; // запись истории изменений агрегата

    public const CHILD_TICKET_TYPE_ID = 'c3d4e5f6-a7b8-9012-cdef-345678901235';

    Uuid  $festival_id;
    Uuid  $user_id;          // получатель билетов
    ?Uuid $types_of_payment_id;  // null для заказов-списков
    PriceDto $price;          // priceItem, count, discount, totalPrice
    Status $status;            // из Shared/Domain/ValueObject/Status
    array $ticket;             // GuestsDto[]
    Uuid $id;
    ?string $promo_code;
    ?Uuid $location_id;       // только для заказов-списков
    ?Uuid $curator_id;        // только для заказов-списков
}
```

**Фабричные методы (команды):**

| Метод | Статус | Domain Events | Описание |
|-------|--------|---------------|----------|
| `create(dto, kilter)` | NEW | `ProcessUserNotificationNewOrderTicket` | Создание обычного заказа |
| `toPaid(dto, comment?, externalPromoCode?)` | PAID | `ProcessCreateTicket`, `ProcessUserNotificationOrderPaid`, `ProcessGuestNotificationQuestionnaire[]`, `ProcessTelegramByQuestionnaireSend` | Подтверждение |
| `toPaidFriendly(dto, comment?, externalPromoCode?)` | PAID | `ProcessCreateTicket`, `ProcessUserNotificationOrderPaidFriendly`, `ProcessGuestNotificationQuestionnaire[]`, `ProcessTelegramByQuestionnaireSend` | Подтверждение Friendly |
| `toPaidInLiveTicket(dto, kilter)` | PAID_FOR_LIVE | `ProcessCreateTicket`, `ProcessUserNotificationOrderPaidLiveTicket`, `ProcessGuestNotificationQuestionnaire[]` | Подтверждение live |
| `toCancel(dto)` | CANCEL | `ProcessCancelTicket`, `ProcessUserNotificationOrderCancel` | Отмена |
| `toCancelLive(dto)` | CANCEL_FOR_LIVE | `ProcessCancelTicket`, `ProcessCancelLiveTicket` | Отмена live |
| `toLiveIssued(dto, liveNumber[])` | LIVE_TICKET_ISSUED | `ProcessCreateTicket`, `ProcessGuestNotificationQuestionnaire[]`, `ProcessPushLiveTicket[]` | Выдача живых |
| `toDifficultiesArose(dto, comment)` | DIFFICULTIES_AROSE | `ProcessCancelTicket`, `ProcessUserNotificationOrderDifficultiesArose` | Трудности |
| `toChangeTicket(changes[])` | — | — | Изменение данных билета (записывает историю если `!empty($changes)`) |
| `toProcessGuestNotificationQuestionnaire(dto)` | — | `ProcessGuestNotificationQuestionnaire[]` | Только рассылка гостям |
| **`createList(dto, kilter)`** | **NEW_LIST** | — (только история, никаких писем) | Создание заказа-списка куратором |
| **`toApproveList(dto)`** | **APPROVE_LIST** | `ProcessCreateTicket`, `ProcessUserNotificationListApproved`, `ProcessGuestNotificationQuestionnaire[]` | Одобрение списка → PDF получателю + анкеты гостям |
| **`toCancelList(dto)`** | **CANCEL_LIST** | `ProcessCancelTicket`, `ProcessUserNotificationListCancel` | Отмена списка |
| **`toDifficultiesAroseList(dto, comment)`** | **DIFFICULTIES_AROSE_LIST** | `ProcessCancelTicket`, `ProcessUserNotificationListDifficultiesArose` | Трудности по списку (комментарий обязателен) |

`OrderTicketDto` имеет два фабричных метода:
- `fromState($data, $userId, $priceDto, $isLiveTicket, ?$pusherId)` — обычный/Friendly заказ
- `fromStateForList($data, $userId, $curatorId, $locationId)` — заказ-список (без `ticket_type_id`/`types_of_payment_id`/цены)

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

### Location

**Путь:** `Backend/src/Location/` (модуль без AggregateRoot — пассивная сущность, как PromoCode)

```php
class LocationDto extends AbstractionEntity {
    Uuid    $id;
    string  $name;
    ?string $description;
    ?Uuid   $questionnaire_type_id;  // FK → questionnaire_type
    Uuid    $festival_id;             // FK → festivals
    ?string $email_template;          // имя blade-шаблона письма (по умолчанию orderListApproved)
    ?string $pdf_template;            // имя blade-шаблона PDF
    bool    $active;
}
```

**Application API (`LocationApplication`):**
- `getList(LocationGetListQuery)` — фильтрация по name/festival_id/active
- `getItem(Uuid)` — один
- `create(LocationDto)`, `edit(Uuid, LocationDto)`, `delete(Uuid)` — CRUD

**Repository:** `LocationRepositoryInterface` → `InMemoryMySqlLocationRepository`. Bind в `TicketsProvider`.

**Используется в:** `OrderTicket` (поле `location_id`), `OrderListApproved` Mailable (берёт `email_template` для письма-одобрения списка).

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
| **ProcessUserNotificationOrderPaidFriendly** | `email`, `tickets[]`, `ticketTypeId`, `comment?`, `promocode?` | Email Friendly (без ссылки `/myOrders`) |
| **ProcessUserNotificationOrderPaidLiveTicket** | `email`, `ticketTypeId`, `typeOrPaymentId`, `kilter` | Email для live |
| **ProcessUserNotificationOrderCancel** | `email`, `ticketTypeId` | Email об отмене |
| **ProcessUserNotificationOrderDifficultiesArose** | `orderId`, `email`, `comment`, `ticketTypeId` | Email о трудностях |
| **ProcessUserNotificationOrderLiveTicketIssued** | `email`, `ticketTypeId` | Email о выдаче live |
| **ProcessUserNotificationListApproved** | `email`, `tickets[]`, `festivalId`, `?locationId` | Email при одобрении заказа-списка → `OrderListApproved` (использует `Location.email_template`) |
| **ProcessUserNotificationListCancel** | `email`, `festivalId` | Email об отмене заказа-списка → `OrderListCancel` |
| **ProcessUserNotificationListDifficultiesArose** | `email`, `comment`, `festivalId` | Email о трудностях по списку → `OrderListDifficultiesArose` |

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
| `getList(Filters): OrderTicketItemForListResponse[]` | Админ-список с фильтрами (`WHERE friendly_id IS NULL AND curator_id IS NULL`) |
| `getFriendlyList(Filters): OrderTicketItemForFriendlyListResponse[]` | Friendly-список (`WHERE friendly_id IS NOT NULL AND curator_id IS NULL`) |
| `getListsList(Filters): OrderTicketItemForListsResponse[]` | Заказы-списки (`WHERE curator_id IS NOT NULL`) — для admin/manager |
| `getCuratorList(Filters): OrderTicketItemForListsResponse[]` | Заказы-списки куратора (фильтр `curator_id` идёт через `Filters`) |
| `changeStatus(Uuid, Status, array): bool` | Смена статуса |
| `updateGuests(Uuid, array): bool` | Обновить список гостей |
| `changePrice(Uuid, float): bool` | Изменить цену (admin) |

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

### LocationRepositoryInterface

**Путь:** `Backend/src/Location/Repositories/LocationRepositoryInterface.php`
**Реализация:** `InMemoryMySqlLocationRepository`

| Метод | Описание |
|-------|----------|
| `getList(Filters, Order): Collection` | Список локаций с фильтрами |
| `getItem(Uuid): LocationDto` | По ID |
| `create(LocationDto): bool` | Создать |
| `editItem(Uuid, LocationDto): bool` | Редактировать |
| `remove(Uuid): bool` | Удалить |

### TicketTypePriceRepositoryInterface

**Путь:** `Backend/src/TicketTypePrice/Repositories/TicketTypePriceRepositoryInterface.php`
**Реализация:** `InMemoryMySqlTicketTypePriceRepository` (таблица `ticket_type_price`, soft delete)

| Метод | Описание |
|-------|----------|
| `getList(Filters, Order): Collection` | Список волн (фильтр `ticket_type_id`) |
| `getItem(Uuid): TicketTypePriceDto` | Одна волна |
| `create(TicketTypePriceDto): bool` | Создать |
| `editItem(Uuid, TicketTypePriceDto): bool` | Редактировать |
| `remove(Uuid): bool` | Soft delete (помечает запись как удалённую) |

**DTO `TicketTypePriceDto`:** `id` (Uuid), `ticket_type_id` (Uuid), `price` (float), `before_date` (Carbon).

**Связь с TicketType:** `Tickets\TicketType\Repository\InMemoryTicketTypeRepository::buildBuilder()` берёт текущую цену билета подзапросом к `ticket_type_price` (`before_date >= CURDATE()`, ORDER BY `before_date` ASC LIMIT 1) — поведение не изменено. Новый CRUD управляет содержимым этой таблицы через API.

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
 Account ←── user_id ── OrderTicket ←── friendly_id ── Account (pusher)
     |                    |       ↖── curator_id ────── Account (curator)
     |                    |       ↖── location_id ───── Location (для списков)
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
