# CONTRACT RFC v0 — интеграция qr.spaceofjoy.ru → org (Systo)

> **Статус:** черновик (v0). Восстановлен 2026-06-14. Ссылается из кода прототипа
> (`Shared/Integration/Rabbit/EventEnvelope.php`). Согласовывается с qr-стороной.
>
> **Назначение:** живой контракт обмена между внешней витриной **qr.spaceofjoy.ru**
> (Python, мастер коммерции: заказ/промокод/оплата) и **org/Systo** (мастер исполнения:
> билет/PDF/QR/доставка/каталог/вход). Описывает входящий поток **qr → org** (приём заказа
> и создание билетов). Встречный поток **org → BAZA** (`ticket.issued`) описан в §9.

---

## 0. Зафиксированные решения (сессия 2026-06-14)

| № | Решение | Значение |
|---|---------|----------|
| Р1 | **Транспорт** | RabbitMQ (`php-amqplib`), topic-exchange `systo.events`. НЕ MySQL outbox. |
| Р2 | **Мастер цены** | **qr**. org доверяет подписанному снимку цены, **сверяет** с собственным расчётом и **алертит** при расхождении (не блокирует). Следствие: цена переносится **на уровень гостя** (см. §5). |
| Р3 | **Сущности билетов** | Переиспользуем существующие: единый агрегат `OrderTicket` (дискриминаторы `friendly_id`/`curator_id`/`is_live_ticket`) + таблицы входа BAZA `el_tickets`/`spisok_tickets`/`live_tickets` + готовые PDF/email-шаблоны по типу. |
| Р4 | **Режим** | Боевой код (v2.6.0 в проде). Релиз **v2.7.0**. Деплой — с учётом festival gating (`RELEASES.md §7`). |

**Прямое следствие Р1:** в проде нужен RabbitMQ (TLS, HA, DLQ, мониторинг) — сейчас его НЕТ
(`docker-compose.prod.yml` без брокера). Это инфраструктурный пререквизит деплоя (см. §10).
Также нужно развязать конфликт двух AMQP-драйверов (§11).

---

## 1. Архитектура потока

```
 qr (Python, витрина+оплата)
   │  публикует подписанное событие order.created в RabbitMQ
   ▼
 [ RabbitMQ topic-exchange: systo.events ]  routing_key = order.created
   │  binding
   ▼
 org consumer (Backend, php artisan qr:consume-orders)
   │ 1. verify подпись (EventSigner) + anti-replay
   │ 2. dedup по idempotency_key (processed_messages в Backend)
   │ 3. QrOrderAssembler: контракт qr → RawGuestInput[]/OrderTicketDto
   │ 4. фабрика OrderTicket::create|toPaidFriendly|createList|live + ChangeStatus
   │ 5. ProcessCreateTicket → билеты → PDF/QR → письма (переиспользуем)
   │ 6. история: ActorType::QR
   ▼
 org → BAZA (ticket.issued, существующий поток) → el_tickets/spisok_tickets/live_tickets
```

org **валидирует каждую команду** qr (подпись + лимиты + каталог), не слепо.
org/BAZA автономны офлайн в день фестиваля (вход/касса без qr).

---

## 2. Транспорт (RabbitMQ)

| Параметр | Значение |
|---|---|
| Exchange | `systo.events` (topic, durable) |
| Routing keys (qr → org) | `order.created`, `order.status_changed` |
| Routing keys (org → BAZA) | `ticket.issued` (существует) |
| Очередь org | `org.qr-orders` (durable), binding `order.#` |
| DLQ | `org.qr-orders.dlq` (TTL + ручной разбор) — **добавить, в прототипе нет** |
| Delivery | persistent (`delivery_mode=2`), QoS `prefetch=1` |
| Источник классов | `Shared/Integration/Rabbit/*` (`php-amqplib`) |

Заголовки сообщения (вне тела, подписывается тело): `x-signature`, `x-timestamp`,
`x-idempotency-key`, `x-schema-version`, `x-source`.

---

## 3. Безопасность

| Механизм | Реализация |
|---|---|
| Подпись | HMAC-SHA256: `hash_hmac('sha256', timestamp.'.'.body, secret)`. Класс `EventSigner`. `hash_equals` (timing-safe). Секрет `RABBITMQ_SIGNING_SECRET` — ротируемый, разный для dev/staging/prod. |
| Anti-replay | `abs(now - timestamp) > max_skew_seconds (300)` → reject. Требует NTP на обеих сторонах. |
| Идемпотентность | `idempotency_key = "order.<qr_order_id>"`. Дедуп в таблице `processed_messages` (**добавить в Backend** по образцу Baza). Транзакция: бизнес-эффект + запись дедупа атомарно. |
| Валидация каталога | option активна и привязана к ticket_type; ticket_type ∈ festival; types_of_payment exists. Несоответствие → reject + алерт (анти-фрод: qr не сможет «подсунуть» 0₽-опцию). |
| Лимиты | `MAX_GUESTS_PER_ORDER` (=10), `MAX_QTY` опции (=20), допуск расхождения цены (см. §5.3). |

---

## 4. Конверт события (EventEnvelope) — переиспользуем

```jsonc
{
  "schema_version": "1.0",
  "event_type": "order.created",
  "trace_id": "uuid — сквозная трассировка qr→org→BAZA",
  "idempotency_key": "order.<qr_order_id>",
  "occurred_at": "ISO8601",
  "source": "qr",
  "payload": { /* см. §5 */ }
}
```

---

## 5. Контракт `order.created` (payload)

Исправлен относительно исходного черновика: добавлены `festival_id`, машинные коды,
`{option_id, qty}`, **per-guest цена** (следствие Р2), убрана опечатка `discound`→`discount`.

```jsonc
{
  "order": {
    "qr_order_id": "uuid заказа в qr (= idempotency_key)",
    "festival_id": "uuid фестиваля",                  // ОБЯЗАТЕЛЬНО (без него заказ не создастся)
    "type_order": "regular | friendly | live | list", // машинный enum
    "email": "email получателя билетов",
    "comment": "коммент к заказу | null",
    "user":  { "user_id": "uuid|null", "name": "...", "city": "...", "phone": "..." },
    "types_of_payment": { "id": "uuid", "title": "...", "payment_data": "..." },
    "price": { "price": 4200, "discount": 200, "total": 4000 }, // ИТОГ заказа = checksum (qr — мастер)
    "friendly": { "id": "uuid пушера",  "name": "..." },  // опц (type_order=friendly)
    "pusher":   { "id": "uuid",          "name": "..." },  // опц
    "curator":  { "id": "uuid куратора", "name": "..." },  // опц (type_order=list)
    "location": { "id": "uuid локации",  "title": "..." }   // опц (type_order=list)
  },
  "guests": [
    {
      "name": "ФИО гостя | парковка: 'ГосНомер / Марка / ФИО водителя'",
      "email": "обязательно на каждого гостя",
      "number": "номер живого билета | null",   // только live (см. §6.4)
      "promocode": "строка | null",
      "ticket_type": {
        "id": "uuid типа билета",                // null для type_order=list
        "title": "...",
        "options": [ { "option_id": "uuid", "qty": 1, "title": "опц для UI" } ]
      },
      "price": {                                  // per-guest снимок (qr — мастер, Р2)
        "base_price": 4200,
        "options_sum": 0,
        "discount": 200,
        "total": 4000
      }
    }
  ]
}
```

### 5.1. Обязательные поля (иначе reject)
`order.festival_id`, `order.email`, `order.type_order`, `order.qr_order_id`,
каждый `guests[].email`, `guests[].name`/`value`, `guests[].price` (кроме list),
`guests[].ticket_type.id` (кроме list).

### 5.2. Почему цена на уровне гостя
Внутренний формат v2.6.0 хранит `price_snapshot {base_price, options_sum, discount, total}`
**в каждом госте** (`OrderGuestLine`/`MoneySnapshot`). Раз qr — мастер цены (Р2), он шлёт
ровно тот снимок, что списал, по каждому гостю. org сохраняет его **verbatim**, не пересчитывая.

### 5.3. Сверка цены (org как валидатор)
org прогоняет `OrderPriceCalculator` для тех же строк и сравнивает:
- сумма `guests[].price.total` == `order.price.total` (арифметика) — иначе reject;
- |org_calc − qr_total| ≤ допуск (конфиг, напр. 0₽ строго или 1₽ на округление) — иначе **алерт** (Sentry + история), заказ создаётся (qr — мастер);
- процентный промокод считается от **базовой цены билета** (не от опций) — фиксируем как общее правило, чтобы qr не пересчитывал иначе.

---

## 6. Маппинг qr → внутренний формат

### 6.1. Тип заказа (`type_order` → дискриминаторы)
| `type_order` | Фабрика | Дискриминаторы | Письмо |
|---|---|---|---|
| `regular` | `OrderTicket::create` → `ChangeStatus(PAID)` | `friendly_id=null`, `curator_id=null` | `ProcessUserNotificationOrderPaid` |
| `friendly` | `create` + `toPaidFriendly` | `friendly_id = friendly.id`/`pusher.id` | `ProcessUserNotificationOrderPaidFriendly` |
| `live` | `create` → `toPaidInLiveTicket` | `guests[].is_live_ticket=true` | `ProcessUserNotificationOrderPaidLiveTicket` |
| `list` | `createList` → `toApproveList` | `curator_id = curator.id`, `location_id` | `ProcessUserNotificationListApproved` |

**Инвариант:** live + non-live в одном заказе нельзя (валидация при сборке).

### 6.2. Идентичность (friendly/pusher/curator)
В UI-flow org берёт их из `Auth::id()`. Для qr — берём из **подписанного payload**
(`friendly.id`/`curator.id`). UUID **должны существовать** в `users` org (резолв при сборке;
не найден → reject + алерт). Это связано с будущей ролью `qr_service` (v2.7.0 SSO) —
до её появления доверяем подписи канала.

### 6.3. Статус
qr **не задаёт** машину статусов напрямую. Целевой статус выводится из `type_order`
(`regular/friendly`→`PAID`, `live`→`PAID_FOR_LIVE`, `list`→`APPROVE_LIST`). Последующие
изменения (возврат/отмена) — отдельным событием `order.status_changed` с машинным кодом
`Status` VO, валидируемым по матрице переходов.

### 6.4. Живые билеты (`guests[].number`)
**Открытый вопрос к qr** (§10): предзаданный номер (как `createFriendly`, с проверкой дубля
`CheckLiveTicketService`) ИЛИ сначала `PAID_FOR_LIVE`, потом `LIVE_TICKET_ISSUED` с присвоением.
До ответа `number` валидируется на дубль в рамках фестиваля и кладётся в live-flow.

### 6.5. Парковка
Признак — `ticket_type.is_parking` (каталог org). qr кладёт в `guests[].name`/`value` склейку
`"ГосНомер / Марка / ФИО водителя"`, email водителя — в `guests[].email`. `masterName` не нужен.

### 6.6. Опции
qr шлёт `{option_id, qty}`. org разворачивает `qty` в N снимков `OrderGuestOption`
(имя/цена — из каталога org, не из payload). `title` из qr — только для UI-сверки.

---

## 7. Обработка на стороне org

1. `EventConsumer.consume('org.qr-orders', ['order.#'], handler)`.
2. verify подпись + anti-replay → невалидно: reject (no requeue) + лог.
3. dedup: `processed_messages` по `idempotency_key`; повтор → ack без эффекта.
4. `QrOrderAssembler`: payload → `RawGuestInput[]` (+ резолв festival/payment/identity/options).
5. сверка цены (§5.3).
6. `AccountApplication::creatingOrGetAccountId(email)` — получатель.
7. фабрика `OrderTicket` по типу (§6.1) → `pullDomainEvents()` → `Bus::chain(...)->dispatch()`.
8. история: `SaveHistoryDto(actorType: ActorType::QR, actorId: null)` — **добавить `QR='qr'`**.
9. маппинг `qr_order_id → order_id` (для трассировки и `order.status_changed`).
10. всё в транзакции с записью `processed_messages` (at-most-once на бизнес-эффект).

Билеты, PDF/QR, письма, sync в BAZA — **существующий pipeline без изменений** (Р3).

---

## 8. Идемпотентность и маппинг заказов

- Таблица `processed_messages` (Backend) — UNIQUE `idempotency_key` (по образцу Baza).
- Таблица/поле маппинга `qr_order_id ↔ order_tickets.id` — для `order.status_changed`
  и сквозной трассировки `trace_id`. (Вариант: колонка `order_tickets.qr_order_id` nullable + index.)

---

## 9. Встречный поток org → BAZA (`ticket.issued`) — существует

Прототип уже публикует `ticket.issued` (org) и консьюмит в BAZA (`processed_messages` + `el_tickets`).
Боевой переход: заменить прямую запись org→БД BAZA (`DB::connection('mysqlBaza')`, креды
хардкодом, без подписи) на шину. Для списков/живых — `toArrayForSpisok`/`setInBazaLive`
(routing keys `ticket.issued.list` / `ticket.issued.live` или поле `kind` в payload).

---

## 10. Открытые вопросы к qr-стороне (блокеры контракта)

1. **Инфраструктура:** кто и где поднимает RabbitMQ для прода (РФ-сеть, TLS, HA)? Без брокера деплой невозможен.
2. **Идентичность:** `friendly.id`/`curator.id`/`user.user_id` из qr — это те же UUID, что в `users` org/BAZA, или идентификаторы БД qr (нужен маппинг)?
3. **Цена per-guest:** qr готов слать `guests[].price`? (следствие Р2 — иначе org не соберёт корректный snapshot).
4. **Live-номера:** предзаданный `number` или присвоение через `LIVE_TICKET_ISSUED`? (§6.4)
5. **Статусы:** qr шлёт только `order.created`, а возвраты/отмены — `order.status_changed`? Согласовать enum переходов.
6. **Допуск расхождения цены** (§5.3): строго 0₽ или ±1₽ на округление?
7. **Механизм контракта:** OpenAPI+AsyncAPI в общем git (TD-24)? Кто владелец схемы и правил обратной совместимости.

---

## 11. Развязка двух AMQP-драйверов

- Старый `Shared/Infrastructure/Bus/Event/RabbitMq/*` — PECL `ext-amqp` (мёртвый код).
- Новый `Shared/Integration/Rabbit/*` — `php-amqplib` (целевой).
- **Действие:** оставить только `php-amqplib`; старый удалить или явно пометить deprecated и исключить из autoload, чтобы не было двух API в одном проекте.

---

## 12. Версионирование схемы

- `schema_version` в конверте (semver). Консьюмер **должен** проверять major-версию
  (сейчас прототип не проверяет — добавить). Новые поля — backward-compatible (игнорируются
  старым консьюмером); удаление/переименование обязательного поля — major bump + согласование деплоя.

---

## История документа

| Дата | Изменение |
|------|-----------|
| 2026-06-14 | Восстановлен/создан RFC v0 на основе разведки кодовой базы и решений Р1–Р4. |
