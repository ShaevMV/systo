---
title: Новый формат заказа — архитектурный дизайн
status: draft
created: 2026-05-30
author: tech-lead
target_release: v2.6.0
deadline: 2026-06-12
related:
  - .claude/docs/DOMAIN.md
  - .claude/docs/BUSINESS_RULES.md
  - .claude/meetings/2026-05-30/RESULTS.md
  - Backend/src/Order/OrderTicket/Domain/OrderTicket.php
  - Backend/src/Order/OrderTicket/Dto/OrderTicket/OrderTicketDto.php
  - Backend/src/Order/OrderTicket/Dto/OrderTicket/GuestsDto.php
  - Backend/src/Order/OrderTicket/Dto/OrderTicket/PriceDto.php
size_estimate: XL
breaking_change: true
---

# Спецификация: Новый формат заказа (v2.6.0)

> **BREAKING CHANGE** домена `OrderTicket`. Каждый гость в заказе получает свой `ticket_type_id`, свой `promo_code` и набор `options[]`. Расчёт цены становится пер-гостевым.
>
> Документ — единый источник правды для разработки внутри интеграционной ветки `feat/v2.6.0-fall-festival`.

---

## 1. Архитектурное видение

### 1.1. Основная идея

Сейчас агрегат `OrderTicket` моделирует ситуацию **«один тип билета на весь заказ»**: `ticket_type_id`, `promo_code`, `price` — поля заказа. На практике этого недостаточно: в одной покупке нужно смешивать оргвзнос + детский билет + парковку, плюс к каждому билету могут идти опции (саженец, мерч, парковка одной машиной и т.п.).

Перевод модели в формат **«заказ — это контейнер строк»**, где каждая строка — это `OrderGuestLine` (гость + тип билета + опции + промокод). Цена заказа становится **функцией строк**, а не самостоятельным полем.

### 1.2. Новая структура агрегата `OrderTicket`

```
OrderTicket (AggregateRoot)
├── id: Uuid
├── festival_id: Uuid                  // один фестиваль на весь заказ
├── user_id: Uuid                       // получатель
├── types_of_payment_id: ?Uuid          // один способ оплаты на весь заказ
├── status: Status
├── guests: OrderGuestLine[]            // ↓ ядро изменения
├── location_id: ?Uuid                  // для заказов-списков
├── curator_id: ?Uuid                   // для заказов-списков
├── friendly_id: ?Uuid                  // для Friendly
└── project: ?string

OrderGuestLine (Value Object)
├── id: Uuid                            // = id билета (для совместимости)
├── value: string                       // ФИО / "номер машины / марка / водитель"
├── email: ?string
├── number: ?int                        // для live-билетов
├── ticket_type_id: Uuid                // ↓ NEW: тип билета на каждого
├── options: OrderGuestOption[]         // ↓ NEW: список опций
├── promo_code: ?string                 // ↓ NEW: промокод на каждого
└── price_snapshot: MoneySnapshot       // ↓ NEW: денормализованный итог (для отчётов + защиты от смены волны цен)

OrderGuestOption (Value Object)
├── option_id: Uuid                     // FK на ticket_options
├── name_snapshot: string               // фиксируем имя на момент покупки
└── price_snapshot: Money               // фиксируем цену на момент покупки

MoneySnapshot (Value Object)
├── base_price: Money                   // цена билета без опций и скидки
├── options_sum: Money
├── discount: Money
└── total: Money
```

**Что осталось на уровне заказа:** `festival_id`, `user_id`, `types_of_payment_id`, `status`. Это правильно — фестиваль и способ оплаты выбираются один раз в чек-ауте.

**Что переехало на уровень гостя:** `ticket_type_id`, `promo_code`, опции, цена.

### 1.3. Принципы и источники

- **Dependency Rule (Чистая архитектура, гл. «Зависимости»)** — Domain не знает про сервисы цен и БД. Расчёт цены делает Application-сервис, передавая в `OrderGuestLine` уже посчитанные `Money`-снимки.
- **Single Responsibility (Совершенный код, гл. «Классы»)** — `OrderGuestLine` отвечает только за состояние одной строки; цена считается отдельным `OrderPriceCalculator` (Application).
- **Open/Closed** — новые типы билетов и опций добавляются без изменения агрегата.
- **Tell, don't ask** — `OrderTicket::totalPrice()` сам собирает итог по строкам, а не отдаёт `guests[]` наружу для подсчёта в контроллере.
- **Value Object'ы неизменяемы** — `OrderGuestLine`, `Money` — readonly, любое изменение порождает новый объект.

### 1.4. Совместимость с History / Event Sourcing

`domain_history` — append-only. Старые события остаются как есть: `OrderCreatedEvent` будет читать `ticket_type` из первой строки (для legacy-заказов до миграции — это будет общий `ticket_type_id` заказа). Новые события:

- `OrderCreatedV2Event` — payload включает массив `guest_lines: [{ticket_type_id, options[], promo_code, total}]`. Старый `OrderCreatedEvent` сохраняем, но не используем для новых заказов.
- `OrderGuestLineChangedEvent` — для будущей фичи смены `ticket_type` у конкретного гостя (не в scope v2.6.0).
- `OrderPriceChangedEvent` — расширяется полем `guest_id`, чтобы понимать, у какого гостя поменялась цена.

Принцип — **новые события, старые не трогаем**. Это даёт обратную совместимость для админ-просмотра истории.

---

## 2. Изменения доменной модели

### 2.1. Класс `OrderTicket` (агрегат)

**Удалить из конструктора:**
- `PriceDto $price` — переезжает внутрь строк
- `?string $promo_code` — переезжает в `OrderGuestLine`

**Не трогать:** `festival_id`, `user_id`, `types_of_payment_id`, `status`, `id`, `location_id`, `curator_id`. Они остаются на уровне заказа.

**Изменить:** поле `array $ticket` → `array $guests` (массив `OrderGuestLine`, а не `GuestsDto`).

**Новые методы:**
```php
public function totalPrice(): Money;
public function discountTotal(): Money;
public function guests(): array;            // OrderGuestLine[]
public function uniqueTicketTypeIds(): array; // для совместимости со старыми Domain Events
```

**Фабричные методы — сигнатуры:**

| Метод | Что меняется |
|-------|-------------|
| `create(OrderTicketDto, int $kilter)` | DTO теперь содержит `guests: OrderGuestLine[]`. `getTicketTypeId()` уходит. `ProcessUserNotificationNewOrderTicket` принимает `array $ticketTypeIds` вместо одного UUID (или массив `OrderGuestLine`). |
| `toPaid(...)` | `ProcessUserNotificationOrderPaid` принимает строки целиком (нужно для письма «Ваш заказ: оргвзнос + детский + парковка»). `isChildTicket()` исчезает — проверка перекладывается на уровень строки (для каждого гостя своя анкета). |
| `toPaidFriendly(...)` | То же что `toPaid` |
| `toPaidInLiveTicket(...)` | Сейчас весь заказ — live. Будет: только строки, где `ticket_type.is_live_ticket = true`, идут через live-flow. **Открытый вопрос:** смешивать ли live + non-live в одном заказе? (см. §9) |
| `toCancel(...)` | Без изменений сигнатуры, но внутри `ProcessUserNotificationOrderCancel` принимает массив `ticket_type_id`. |
| `toLiveIssued(OrderTicketDto, array $liveNumber)` | `$liveNumber` остаётся `[ticketId => number]`. Каждая строка живого билета получает свой номер. |
| `toChangeTicket(OrderTicketDto, array $valueMap, array $emailMap)` | Без изменений — меняем только ФИО/email строки. |
| `toRemoveTicket(OrderTicketDto, Uuid $ticketId)` | Без изменений на уровне сигнатуры, но `recordHistory` теперь содержит `ticket_type_id` удалённого гостя. |
| `createList(...)` / `toApproveList(...)` / `toCancelList(...)` / `toDifficultiesAroseList(...)` | Списки **не используют** `ticket_type_id` ни на уровне заказа, ни на уровне гостя (билеты-списки — это билеты на локацию). `OrderGuestLine` для списков создаётся с `ticket_type_id = null` (см. §9 — обсудить, может, лучше выделить отдельный тип строки). |

**History events:** оставляем `OrderStatusChangedEvent` как есть. Новый `OrderCreatedV2Event` логирует строки.

### 2.2. DTO

**`OrderTicketDto` (изменения):**

Удалить:
- `?Uuid $ticket_type_id`
- `?string $promo_code`
- `PriceDto $priceDto`
- `?Uuid $questionnaire_type_id` (тип анкеты теперь определяется по `ticket_type` каждой строки — см. логику `ProcessGuestNotificationQuestionnaire`)

Добавить:
- `OrderGuestLine[] $guests` — основной массив строк
- `MoneySnapshot $total` — итог по всему заказу (для денормализации, для отчётов)

Старые поля остаются: `festival_id`, `user_id`, `email`, `phone`, `types_of_payment_id`, `id_buy`, `datePay`, `status`, `is_live_ticket`, `id`, `inviteLink`, `friendly_id`, `location_id`, `curator_id`, `project`.

**Фабричные методы:**
- `fromState($data, $userId, Money $totalCalculated, ?Uuid $pusherId = null)` — `Money` приходит уже посчитанный из `OrderPriceCalculator`. DTO **не считает цену сам**.
- `fromStateForList(...)` — без изменений в сигнатуре, но guests создаются с `ticket_type_id = null`.

**`GuestsDto` (статус):**
- **Помечаем `@deprecated`**, но не удаляем сразу. Используется в Domain Events, которые читаются worker'ом из очереди (могут быть старые задачи в момент деплоя).
- Через одну minor-версию (v2.7.0) — удаляем.
- Для миграции добавляем метод `GuestsDto::toOrderGuestLine(?Uuid $defaultTicketTypeId, ?string $defaultPromoCode): OrderGuestLine`.

**Новый `OrderGuestLineDto`:**

```php
final class OrderGuestLineDto implements EntityDataInterface {
    public function __construct(
        public readonly string $value,
        public readonly ?string $email,
        public readonly ?int $number,
        public readonly Uuid $id,
        public readonly Uuid $festivalId,
        public readonly ?Uuid $ticketTypeId,     // null только для list-orders
        public readonly array $optionIds,         // Uuid[]
        public readonly ?string $promoCode,
        public readonly MoneySnapshot $priceSnapshot,
    ) {}

    public static function fromState(array $data, Uuid $festivalId): self;
    public function toArray(): array;
}
```

**`PriceDto` — судьба:**
- Удаляем из `OrderTicketDto` как конструктор-параметр.
- Сам класс **оставляем** (используется в legacy-местах: `ProcessUserNotificationNewOrderTicket`, в репозиториях для денормализованных колонок `price` / `discount`).
- Заменяем по факту на новый `MoneySnapshot` (более типобезопасный, иммутабельный, с детализацией).

### 2.3. Новые Value Objects

```php
// Shared/Domain/ValueObject/Money.php (новый)
final class Money {
    public function __construct(private readonly int $amount, private readonly string $currency = 'RUB') {}
    public function add(Money $other): self;
    public function subtract(Money $other): self;
    public function multiply(int $factor): self;
    public static function zero(): self;
    public static function fromFloat(float $value): self;  // 4200.0 → 4200 (целые рубли — копейки не нужны)
    public function asFloat(): float;
}

// Tickets/Order/OrderTicket/Domain/ValueObject/OrderGuestLine.php
final class OrderGuestLine {
    public function __construct(
        private readonly Uuid $id,
        private readonly string $value,
        private readonly ?string $email,
        private readonly ?int $number,
        private readonly Uuid $festivalId,
        private readonly ?Uuid $ticketTypeId,
        private readonly array $options,         // OrderGuestOption[]
        private readonly ?string $promoCode,
        private readonly MoneySnapshot $price,
    ) {}

    public function total(): Money;
    public function ticketTypeId(): ?Uuid;
    public function isChild(): bool;
    public function isLive(): bool;
    public function withRegeneratedId(): self;
    public function withValue(string $value): self;
    public function withEmail(?string $email): self;
}

// Tickets/Order/OrderTicket/Domain/ValueObject/OrderGuestOption.php
final class OrderGuestOption {
    public function __construct(
        private readonly Uuid $optionId,
        private readonly string $nameSnapshot,
        private readonly Money $priceSnapshot,
    ) {}
}

// Tickets/Order/OrderTicket/Domain/ValueObject/MoneySnapshot.php
final class MoneySnapshot {
    public function __construct(
        private readonly Money $basePrice,
        private readonly Money $optionsSum,
        private readonly Money $discount,
    ) {}
    public function total(): Money;
}
```

---

## 3. Миграция БД

### 3.1. Текущая схема `order_tickets`

```sql
CREATE TABLE order_tickets (
  id UUID PRIMARY KEY,
  guests JSON NOT NULL,                  -- [{value, email, id, festival_id, number}]
  festival_id UUID NOT NULL,
  user_id UUID NOT NULL,
  ticket_type_id UUID NULL,              -- общий на весь заказ (NULL для list)
  promo_code VARCHAR NULL,
  id_buy VARCHAR NOT NULL,
  phone VARCHAR NOT NULL,
  types_of_payment_id VARCHAR NULL,
  price FLOAT DEFAULT 0,                 -- общая сумма
  discount FLOAT DEFAULT 0,
  status VARCHAR NOT NULL,
  ...
);
```

### 3.2. Целевая схема (после v2.6.0)

```sql
-- 1. Поля заказа: ticket_type_id, promo_code, price, discount помечаем nullable.
--    НЕ удаляем сразу — нужны для legacy-кода и отчётов.
ALTER TABLE order_tickets
  MODIFY ticket_type_id UUID NULL,
  MODIFY promo_code VARCHAR NULL,
  MODIFY price FLOAT NULL,
  MODIFY discount FLOAT NULL;

-- 2. JSON-структура guests расширяется (новые ключи, старые остаются для legacy-чтения):
-- БЫЛО:
--   [{ "id", "value", "email", "number", "festival_id" }]
-- СТАЛО:
--   [{
--     "id", "value", "email", "number", "festival_id",
--     "ticket_type_id": "uuid",
--     "options": [{"option_id": "uuid", "name": "Саженец", "price": 500}],
--     "promo_code": "TMBR5X" | null,
--     "price_snapshot": { "base": 4200, "options_sum": 500, "discount": 0, "total": 4700 }
--   }]

-- 3. Новая таблица опций
CREATE TABLE ticket_options (
  id UUID PRIMARY KEY,
  name VARCHAR NOT NULL,
  price FLOAT NOT NULL,
  ticket_type_id UUID NULL,                -- NULL = опция доступна всем типам
  festival_id UUID NULL,                   -- NULL = на всех фестивалях
  active BOOLEAN DEFAULT TRUE,
  deleted_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_ticket_type (ticket_type_id),
  INDEX idx_festival (festival_id)
);

-- 4. Опционально (для будущих отчётов и быстрых выборок) — нормализованная таблица:
CREATE TABLE order_guest_lines (
  id UUID PRIMARY KEY,                     -- = guest.id
  order_ticket_id UUID NOT NULL,
  ticket_type_id UUID NULL,
  promo_code VARCHAR NULL,
  base_price FLOAT NOT NULL DEFAULT 0,
  options_sum FLOAT NOT NULL DEFAULT 0,
  discount FLOAT NOT NULL DEFAULT 0,
  total_price FLOAT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_ticket_id) REFERENCES order_tickets(id) ON DELETE CASCADE,
  INDEX idx_order (order_ticket_id),
  INDEX idx_ticket_type (ticket_type_id)
);
-- ↑ обсудить в §9 (нужна ли) — добавляет работы, но даёт быстрые отчёты.
```

### 3.3. Алгоритм миграции данных

**Файл:** `2026_06_XX_140000_migrate_order_tickets_to_per_guest_format.php`

1. **Бэкап:** `mysqldump systo > backup_pre_v2.6.0_<timestamp>.sql` × 5 копий (локально + 2 внешние).
2. **Dry-run на staging** — обкатать на копии прода (см. § 7).
3. **Транзакция:**
   ```php
   DB::transaction(function () {
     $orders = DB::table('order_tickets')->cursor();
     foreach ($orders as $order) {
       $guests = json_decode($order->guests, true);
       $defaultTicketType = $order->ticket_type_id;
       $defaultPromo = $order->promo_code;
       $totalPerGuest = $order->price > 0 && count($guests) > 0
         ? round(($order->price - $order->discount) / count($guests), 2)
         : 0;
       $discountPerGuest = count($guests) > 0
         ? round($order->discount / count($guests), 2)
         : 0;

       foreach ($guests as &$guest) {
         $guest['ticket_type_id'] = $defaultTicketType;       // для не-list
         $guest['promo_code'] = $defaultPromo;
         $guest['options'] = [];                              // legacy = пусто
         $guest['price_snapshot'] = [
           'base' => $totalPerGuest + $discountPerGuest,
           'options_sum' => 0,
           'discount' => $discountPerGuest,
           'total' => $totalPerGuest,
         ];
       }
       DB::table('order_tickets')
         ->where('id', $order->id)
         ->update(['guests' => json_encode($guests, JSON_UNESCAPED_UNICODE)]);
     }
   });
   ```
4. **Валидация после миграции:**
   - `SELECT COUNT(*) FROM order_tickets WHERE JSON_LENGTH(guests) = 0` → должно быть 0
   - `SELECT id FROM order_tickets WHERE JSON_CONTAINS_PATH(guests, 'one', '$[0].ticket_type_id') = 0 AND curator_id IS NULL LIMIT 5` → должно быть пусто (для не-list заказов)
   - Сверить сумму `SUM(price)` до и после миграции
5. **Rollback план:**
   - Если миграция упала на проде → восстановление из `backup_pre_v2.6.0_<timestamp>.sql`
   - Все legacy-колонки оставлены nullable → откат кода (revert merge) сам по себе не сломает БД, заказы продолжат читаться по старому формату через legacy-fallback в `GuestsDto::fromState`

### 3.4. Idempotency

Миграция должна быть **идемпотентной** — если упала на 50%, при повторном запуске не должна задвоить данные. Проверка: `if (isset($guest['price_snapshot'])) continue;` — пропускаем уже мигрированных гостей.

---

## 4. Изменения CQRS handlers

### 4.1. CreatingOrderCommandHandler

**Минимальное изменение** — handler принимает `OrderTicketDto` с новыми полями и передаёт в репозиторий. Репозиторий сам кладёт `guests[]` в JSON-колонку (благодаря Eloquent cast `'array'`).

**Большая работа** — на стороне `CreateOrder::createAndSave()` и контроллера `OrderTickets::create()`: они должны собрать `OrderGuestLine[]` с уже посчитанной ценой. Для этого нужен новый Application-сервис **`OrderPriceCalculator`**.

### 4.2. Новый Application-сервис: `OrderPriceCalculator`

```php
// Backend/src/Order/OrderTicket/Application/Pricing/OrderPriceCalculator.php
final class OrderPriceCalculator {
    public function __construct(
        private TicketTypePriceRepositoryInterface $priceRepo,
        private TicketOptionRepositoryInterface $optionRepo,
        private IsCorrectPromoCode $promoCodeChecker,
    ) {}

    /**
     * Возвращает массив OrderGuestLine с заполненными price_snapshot.
     */
    public function calculateLines(
        Uuid $festivalId,
        array $rawGuests,   // [{value, email, ticket_type_id, options, promo_code, ...}]
    ): array {
        $lines = [];
        foreach ($rawGuests as $raw) {
            $ticketTypeId = new Uuid($raw['ticket_type_id']);
            $base = Money::fromFloat($this->priceRepo->currentPrice($ticketTypeId, $festivalId));

            $optionsSum = Money::zero();
            $options = [];
            foreach ($raw['options'] ?? [] as $optionId) {
                $option = $this->optionRepo->getItem(new Uuid($optionId));
                $optionsSum = $optionsSum->add(Money::fromFloat($option->price));
                $options[] = new OrderGuestOption(
                    new Uuid($option->id),
                    $option->name,
                    Money::fromFloat($option->price),
                );
            }

            $subtotal = $base->add($optionsSum);
            $discount = $this->calculateDiscount($raw['promo_code'] ?? null, $subtotal, $ticketTypeId, $festivalId);

            $lines[] = new OrderGuestLine(
                id: isset($raw['id']) ? new Uuid($raw['id']) : Uuid::random(),
                value: $raw['value'],
                email: $raw['email'] ?? null,
                number: $raw['number'] ?? null,
                festivalId: $festivalId,
                ticketTypeId: $ticketTypeId,
                options: $options,
                promoCode: $raw['promo_code'] ?? null,
                price: new MoneySnapshot($base, $optionsSum, $discount),
            );
        }
        return $lines;
    }

    private function calculateDiscount(?string $code, Money $subtotal, Uuid $ticketTypeId, Uuid $festivalId): Money {
        if ($code === null) return Money::zero();
        $promo = $this->promoCodeChecker->findPromoCode($code, $subtotal->asFloat(), $ticketTypeId, $festivalId);
        return Money::fromFloat($promo->getDiscount());
    }
}
```

**Важно:** `OrderPriceCalculator` живёт в Application-слое, не в Domain. Domain-слой (`OrderGuestLine`) не знает про репозитории. Это Dependency Rule из «Чистой архитектуры» (гл. 22).

### 4.3. ChangeStatusCommandHandler

**Изменения минимальны:** handler меняет статус, не трогает строки. Но Domain Events внутри `OrderTicket::toPaid()` и др. должны принимать новый формат строк (массив `OrderGuestLine` вместо одного `ticket_type_id`).

### 4.4. ChangeOrderPriceCommandHandler

**Требует решения:**
- **Вариант A (минимальный):** оставляем как есть — админ меняет общую цену заказа (`order_tickets.price`), но при этом перерасчёт по гостям не делается. История пишет общую сумму.
- **Вариант B (правильный):** новый `ChangeGuestLinePriceCommand` — меняем цену конкретной строки. Старый эндпоинт `ChangeOrderPrice` помечаем deprecated.

Рекомендация tech-lead: **вариант B, но в v2.6.0 — только новый Command для одного гостя, старый оставляем deprecated**. Полное удаление — в v2.7.0. Это решает «одно бизнес-действие = одна команда» из правил CQRS.

### 4.5. ChangeTicketCommandHandler (`toChangeTicket`)

**Без структурных изменений** — меняет только ФИО/email. Внутри `OrderTicket::toChangeTicket` теперь работает с `OrderGuestLine[]` через `withValue()` / `withEmail()`. Сигнатура контроллера не меняется.

### 4.6. Новые Command/Query

- **`CalculateOrderPriceQuery`** — для фронта (нужен до отправки заказа, чтобы показать итог). Реализация через `OrderPriceCalculator::calculateLines()`, возвращает массив `OrderGuestLineResponse` (id, total per guest, total order).
- **`AddOptionToGuestCommand`** / **`RemoveOptionFromGuestCommand`** — пока **не делаем** в v2.6.0. Корзина собирается на фронте и отправляется одним запросом `/order/create`.
- **`CreateTicketOptionCommand`** / **`EditTicketOptionCommand`** / **`DeleteTicketOptionCommand`** — CRUD опций в админке. Отдельный модуль `Backend/src/TicketOption/`.

---

## 5. Расчёт цены — детально

### 5.1. Где живёт расчёт

| Уровень | Что считает | Откуда берёт данные |
|---------|-------------|---------------------|
| **`Money`** (Shared VO) | Арифметика без бизнес-логики | — |
| **`MoneySnapshot`** (Domain VO) | Хранит {base, options_sum, discount, total} | передаётся в конструктор `OrderGuestLine` |
| **`OrderGuestLine::total()`** | Возвращает `$priceSnapshot->total()` | — (всё уже посчитано) |
| **`OrderPriceCalculator`** (Application) | Собирает строки с актуальной волной цены, опциями, промокодом | TicketTypePriceRepository, TicketOptionRepository, IsCorrectPromoCode |
| **`OrderTicket::totalPrice()`** | Сумма по всем строкам | агрегирует `OrderGuestLine::total()` |

**Принцип:** Domain не знает про репозитории. `OrderGuestLine` принимает уже посчитанный `MoneySnapshot` — он не «вычисляет», а «хранит».

### 5.2. Псевдокод

```php
// Domain
class OrderTicket {
    public function totalPrice(): Money {
        return array_reduce(
            $this->guests,
            fn(Money $acc, OrderGuestLine $line) => $acc->add($line->total()),
            Money::zero(),
        );
    }
}

class OrderGuestLine {
    public function total(): Money {
        return $this->price->total();
    }
}

class MoneySnapshot {
    public function total(): Money {
        return $this->basePrice->add($this->optionsSum)->subtract($this->discount);
    }
}
```

### 5.3. Где хранится итог

**Гибрид: денормализация + перерасчёт по запросу.**

- В JSON `order_tickets.guests[].price_snapshot` — храним итог на момент создания (для отчётов, для письма «вот ваш чек», для аудита).
- В новой колонке `order_tickets.price` (nullable) — `totalPrice()` всего заказа, для быстрых запросов в админке.
- Перерасчёт делается **только** при создании заказа и при `toChangeTicket` (если меняется ticket_type или options — а это пока вне scope).

**Почему так:** волны цен сдвигаются — если пересчитывать «на лету», старые заказы покажут новую цену. Это сломает аудит.

### 5.4. Live-билеты (фиксированная цена)

`is_live_ticket = true` — это флаг типа билета, не способа расчёта. Цена живого билета берётся так же — через `TicketTypePriceRepository::currentPrice()`. Разница только в Domain Events (live идёт через `toPaidInLiveTicket` / `toLiveIssued`).

**Открытый вопрос (см. §9):** можно ли в одном заказе смешивать live + non-live? Сейчас нельзя (`is_live_ticket` — флаг заказа). Если оставляем как сейчас — проверка на уровне контроллера: «все строки одного типа».

### 5.5. Промокод процентный — от чего считать

**Текущая логика:** `IsCorrectPromoCode::findPromoCode($name, $price, ...)` — берёт переданный `$price` и считает скидку.

**Новый формат:**
- На входе у нас `subtotal = base + options_sum` для конкретного гостя
- Промокод процентный считаем **от subtotal** (билет + опции)
- Это нужно подтвердить с бизнесом (см. §9 — это открытый вопрос для встречи)

Альтернативный вариант: считать только от `base`, опции скидке не подлежат. Решение — за пользователем.

---

## 6. Обратная совместимость API

### 6.1. Старый формат запроса

**Решение: deprecated + автопреобразование внутри.** В v2.6.0 поддерживаем оба формата, в v2.7.0 — убираем старый.

**Логика преобразования в контроллере `OrderTickets::create()`:**

```php
$payload = $request->all();
// Новый формат: каждый guest имеет ticket_type_id
$isNewFormat = isset($payload['guests'][0]['ticket_type_id']);

if (!$isNewFormat) {
    // Legacy: ticket_type_id, promo_code на уровне заказа → расшиваем по гостям
    foreach ($payload['guests'] as &$guest) {
        $guest['ticket_type_id'] = $payload['ticket_type_id'];
        $guest['promo_code'] = $payload['promo_code'] ?? null;
        $guest['options'] = [];
    }
}

$lines = $this->orderPriceCalculator->calculateLines(
    new Uuid($payload['festival_id']),
    $payload['guests'],
);
// Далее — собираем OrderTicketDto и передаём в CreateOrder::createAndSave()
```

**API-документация:**
- В `API.md` добавляем секцию «Новый формат заказа (v2.6.0+)» и оставляем «Legacy формат (deprecated, удалится в v2.7.0)».
- Заголовок `X-API-Version: 2` (опционально) — пометить новые клиенты.

### 6.2. Кто обновляет фронт

- **Наш фронт (Vue):** обновляем в той же ветке `feat/v2.6.0-frontend-buy-form`.
- **qr.spaceofjoy.ru:** обновляем синхронно — это **наш сервис** (зафиксировано в `RESULTS.md` п.3). Отдельная sub-ветка.
- **Telegram-боты, внешние интеграции:** legacy остаётся до v2.7.0. Уведомление авторов внешних интеграций — задача scrum-master.

---

## 7. Тестовая стратегия

### 7.1. Юнит-тесты (приоритет — критический)

| Что тестируем | Файл | Кол-во кейсов |
|---------------|------|---------------|
| `Money::add/subtract/multiply` | `tests/Unit/Shared/Domain/ValueObject/MoneyTest.php` | 8 |
| `MoneySnapshot::total()` | `tests/Unit/Order/OrderTicket/Domain/ValueObject/MoneySnapshotTest.php` | 4 |
| `OrderGuestLine::total/isChild/isLive/withValue` | `tests/Unit/Order/OrderTicket/Domain/ValueObject/OrderGuestLineTest.php` | 12 |
| `OrderTicket::totalPrice()` — корректная сумма по строкам | `tests/Unit/Order/OrderTicket/Domain/OrderTicketTest.php` | 6 |
| `OrderTicket::create/toPaid/toCancel` — пуш правильных Domain Events | `tests/Unit/Order/OrderTicket/Domain/OrderTicketStatusTransitionTest.php` | 10 |
| `OrderPriceCalculator::calculateLines` — расчёт с опциями, промокодом, разными типами | `tests/Unit/Order/OrderTicket/Application/Pricing/OrderPriceCalculatorTest.php` | 8 |

**Цель покрытия Application слоя — 80% (как в `PROJECT_MEMORY.md`).**

### 7.2. Интеграционные тесты

| Что тестируем | Файл |
|---------------|------|
| `POST /api/v1/order/create` с новым форматом — заказ сохраняется, корректный JSON в `guests`, цена правильная | `tests/Feature/Order/CreateOrderNewFormatTest.php` |
| `POST /api/v1/order/create` с legacy форматом — авто-конверсия работает | `tests/Feature/Order/CreateOrderLegacyFormatTest.php` |
| `POST /api/v1/order/toChangeStatus/{id}` для смешанного заказа (оргвзнос + детский) — корректные письма каждому | `tests/Feature/Order/MixedTicketTypesStatusChangeTest.php` |
| `POST /api/v1/order/create` с опциями — `ticket_options` правильно подцепляются | `tests/Feature/Order/CreateOrderWithOptionsTest.php` |

### 7.3. Тесты миграции

**Файл:** `tests/Feature/Migration/MigrateOrderTicketsToPerGuestFormatTest.php`

Кейсы:
1. Legacy-заказ с 3 гостями → после миграции у каждого гостя `ticket_type_id` = заказа, `promo_code` = заказа, `options = []`, корректный `price_snapshot`.
2. List-заказ (`curator_id != null`) → `ticket_type_id` остаётся null у всех гостей.
3. Заказ без промокода → `promo_code = null` у всех гостей, `discount = 0`.
4. Идемпотентность — повторный запуск миграции не задваивает данные.
5. Сумма `price` всего заказа = сумма `price_snapshot.total` по гостям (с поправкой на округление).

### 7.4. E2E тесты

- Cypress / ручной чек-лист: фронт → создание заказа со смешанными типами и опциями → проверка письма с PDF → проверка списка заказов.
- Прогон на staging до релиза.

### 7.5. Регресс на копии прода

Перед мержем в master — обкатать миграцию на свежем дампе прода (5+ тысяч заказов). Зафиксировать время выполнения, метрики БД до/после.

---

## 8. Риски и митигация

| # | Риск | Вероятность | Влияние | Митигация |
|---|------|-------------|---------|-----------|
| 1 | Миграция БД сломает старые заказы | Высокая | Критическое | 5 бэкапов до миграции + dry-run на staging + идемпотентность + nullable старых колонок (откат без потери данных) |
| 2 | Worker'ы в очереди при деплое имеют старые задачи с `GuestsDto` | Средняя | Среднее | Не удаляем `GuestsDto` в v2.6.0 — помечаем `@deprecated`. Все Domain Events работают с обоими форматами через адаптеры |
| 3 | Расчёт цены ниже/выше старой логики из-за округлений | Средняя | Высокое (деньги) | `Money` хранит копейки в `int`. Все тесты на расчёт + сверка итогов до/после миграции |
| 4 | qr.spaceofjoy.ru не успевает обновиться к 2026-06-12 | Средняя | Высокое (партнёр не работает) | Параллельная ветка `feat/v2.6.0-qr-sync` + legacy-формат API живёт до v2.7.0 |
| 5 | Фронт-форма «BuyTicket» большая, времени мало | Высокая | Высокое | Перестроить как корзину: одна общая логика для всех guests. UX-помощь от ux-ui-designer. Регресс на staging |
| 6 | Volume промокодов с историей использования вырастет (новая таблица) | Низкая | Низкое | Партицирование таблицы по festival_id (опционально, в v2.7.0) |
| 7 | BREAKING change ломает Friendly-флоу | Средняя | Высокое | Отдельный тест-набор для `toPaidFriendly` + ручной прогон сценария пушера на staging |
| 8 | Дедлайн 2026-06-12 нереалистичен | Высокая | Критическое | Декомпозиция на 6 sub-веток с независимыми milestones (см. §10). При риске — выкидываем «нормализованную таблицу `order_guest_lines`» из scope |
| 9 | Live-билеты + non-live в одном заказе не предусмотрено | Средняя | Среднее | Валидация в контроллере: все строки заказа должны иметь одинаковый `is_live_ticket` |
| 10 | Анкеты гостей — `questionnaire_type_id` сейчас на уровне типа билета, после миграции разные гости получают разные анкеты | Высокая | Среднее | Уже работает на уровне `ticket_type.questionnaire_type_id`. `ProcessGuestNotificationQuestionnaire` определяет тип анкеты автоматически. **Проверить тестом** |

---

## 9. Открытые вопросы (для встречи с пользователем)

1. **Старый формат API** — поддерживать в v2.6.0 (deprecated) или сразу breaking без перехода?
2. **Промокод процентный** — считать от (билет + опции) или только от билета?
3. **Live + non-live в одном заказе** — разрешать или валидация «всё одно»?
4. **Заказы-списки (`createList`)** — опции тоже на каждого гостя или общие для всех гостей списка?
5. **`types_of_payment_id`** — остаётся на уровне заказа (один способ оплаты на весь заказ), подтверждаем?
6. **`festival_id`** — на уровне заказа (один фестиваль), подтверждаем?
7. **Нормализованная таблица `order_guest_lines`** — делаем сейчас (XL scope) или откладываем в v2.7.0 (отчёты будут считаться через JSON)?
8. **`ChangeOrderPriceCommand`** — разделяем на `ChangeGuestLinePriceCommand` сразу или в v2.7.0?
9. **Опции** — кратность (можно 2 саженца на одного гостя)? Если да — `options[]` хранит дубликаты или `[{option_id, qty}]`?
10. **Опция привязана** к одному типу билета или нескольким (M:N)?
11. **Снимок имени/цены опции** в `price_snapshot` — нужен (защита от смены имени/цены опции после покупки)? Да = плюсуем сложности, но защищаем аудит.

---

## 10. План sub-веток (внутри `feat/v2.6.0-fall-festival`)

> Все ветки мержатся через PR в `feat/v2.6.0-fall-festival`. Интеграционная — в master тегом `v2.6.0` после прогона на staging.

| Приоритет | Sub-ветка | Что внутри | Зависит от | Оценка |
|-----------|-----------|------------|------------|--------|
| 1 | `feat/v2.6.0-money-vo` | `Shared/Domain/ValueObject/Money.php`, тесты | — | 1 день |
| 2 | `feat/v2.6.0-ticket-options-crud` | Модуль `Backend/src/TicketOption/` (DTO, Repository, Application CRUD), миграция `ticket_options`, API `/api/v1/ticketOption/*`, админ-UI | money-vo | 3 дня |
| 3 | `feat/v2.6.0-domain-model-rewrite` | `OrderGuestLine`, `OrderGuestOption`, `MoneySnapshot`, изменения `OrderTicket` агрегата, изменения `OrderTicketDto`, deprecation `GuestsDto`, миграция БД | money-vo | 4 дня |
| 4 | `feat/v2.6.0-pricing-calculator` | `OrderPriceCalculator` (Application), `CalculateOrderPriceQuery`, обновление `IsCorrectPromoCode` | domain-model-rewrite, ticket-options-crud | 2 дня |
| 5 | `feat/v2.6.0-handlers-and-api` | Обновление контроллеров `OrderTickets::create/createFriendly/createList`, legacy-конверсия, обновление `CreateOrder`, обновление Domain Events (новые поля) | pricing-calculator | 3 дня |
| 6 | `feat/v2.6.0-frontend-buy-form` | Перестройка `BuyTicket.vue` под корзину: каждый гость — свой тип/опции/промокод. Обновление Vuex `OrderModule`. Превью цены через `CalculateOrderPriceQuery` | handlers-and-api | 5 дней |
| 7 | `feat/v2.6.0-qr-sync` | Обновление qr.spaceofjoy.ru под новый формат заказа | handlers-and-api | 2 дня (партнёрская команда / наш же дев) |
| 8 | `feat/v2.6.0-data-migration` | Миграция данных + dry-run скрипт + регресс на копии прода | domain-model-rewrite | 2 дня |
| 9 | `feat/v2.6.0-promocode-aggregator` | История использования промокодов, привязка к создателю, расширение прав | — | 3 дня |
| 10 | `feat/v2.6.0-qr-sso-passport` | Laravel Passport, OAuth2 для qr.spaceofjoy.ru, роль `qr_service` | — | 4 дня |

**Итого pessimistic:** 29 дней работы. С двух недель до дедлайна — **сильно перегружено**. Декомпозируется только параллелизацией:
- Ветки 1, 2, 9, 10 могут идти **независимо**.
- 3 → 4 → 5 → 6 — критическая последовательная цепочка (≈14 дней).
- 8 (миграция) делается параллельно с 5–6.

**Минимально необходимое к 2026-06-12 (если ужимать):** ветки 1, 2, 3, 4, 5, 6, 8. Ветки 9, 10 — можно отложить в `v2.6.1` (патч-релиз через неделю).

---

## 11. Применение принципов «Чистой архитектуры»

| Принцип | Где применяется |
|---------|-----------------|
| **Dependency Rule** (гл. 22) | `OrderGuestLine` (Domain) не знает про репозитории. Расчёт цены делает `OrderPriceCalculator` (Application), он же зовёт инфраструктуру (TicketTypePriceRepository) |
| **SRP** (Совершенный код, гл. 10) | `Money` — только арифметика. `MoneySnapshot` — только хранение детализации. `OrderPriceCalculator` — только сбор цены. `OrderTicket` — только агрегат и Domain Events |
| **OCP** (Чистая архитектура, гл. 8) | Новые опции добавляются через данные (запись в `ticket_options`), без изменения `OrderGuestLine` |
| **LSP** (гл. 9) | `OrderGuestLine` — readonly VO, `withValue()` возвращает новый объект — заменяемость гарантирована |
| **ISP** (гл. 10) | `TicketOptionRepositoryInterface` — отдельный, не часть `OrderTicketRepositoryInterface` |
| **DIP** (гл. 11) | `OrderPriceCalculator` зависит от `TicketTypePriceRepositoryInterface`, не от MySQL-реализации |
| **Boundaries** (гл. 17) | Граница Application/Domain: `OrderPriceCalculator` живёт в Application, передаёт в Domain уже готовый `MoneySnapshot`. Domain ничего не «достаёт», только «принимает» |
| **CQRS** (правила проекта + Greg Young) | Одно бизнес-действие = одна команда. `CreatingOrderCommand` отдельно, `ChangeStatusCommand` отдельно, `ChangeGuestLinePriceCommand` отдельно. Не смешиваем. |

---

## 12. Definition of Done для v2.6.0

- [ ] Все 6 критичных sub-веток смержены в `feat/v2.6.0-fall-festival`
- [ ] Юнит-тесты Money/MoneySnapshot/OrderGuestLine/OrderTicket/OrderPriceCalculator — зелёные, покрытие ≥80% Application слоя
- [ ] Интеграционные тесты `POST /api/v1/order/create` — оба формата работают
- [ ] Миграция БД прогнана на копии прода, время ≤ 5 минут на 10к заказов
- [ ] qr.spaceofjoy.ru обновлён и протестирован сквозно
- [ ] Фронт-форма «Покупка билетов» работает на staging для смешанных заказов
- [ ] `API.md`, `DOMAIN.md`, `BUSINESS_RULES.md` обновлены
- [ ] `CHANGELOG.md` секция v2.6.0 с пометкой `### BREAKING CHANGES`
- [ ] Migration guide в `.claude/docs/process/migrations/v2.6.0.md`
- [ ] Регресс на staging пройден, бэкап прода готов
- [ ] Релиз согласован с пользователем

---

## История изменений документа

| Дата | Изменение |
|------|-----------|
| 2026-05-30 | Создан tech-lead'ом после фиксации итогов встречи 2026-05-30. Архитектурный план рассчитан на v2.6.0, дедлайн 2026-06-12 |
