# CONTRACT_RFC_v0.md — Контракт интеграции qr → org → BAZA

> **Статус:** DRAFT v0 (черновик для согласования) · **Дата:** 2026-06-13
> **Событие в фокусе:** `order.paid` (`OrderPaid`).
> **Аудитория:** команда Systo (org/BAZA) **и** координация с командой `qr.spaceofjoy.ru` (отдельный репозиторий, Python). Контракт сформулирован **нейтрально** (язык-агностично, через JSON-примеры), чтобы быть пригодным для обеих сторон.
> **Принципы:** Р. Мартин «Чистая архитектура» (Dependency Rule — qr и org обмениваются **контрактами**, а не лезут в чужую БД); решения совещания 2026-06-13 (гибрид, push-модель, MySQL outbox, org валидирует каждую команду qr).
> **Режим:** только «бумага» сейчас; боевой код — после релиза v2.6.0. Код qr (Python) недоступен — всё, что требует подтверждения qr-стороны, помечено `[QR?]` и собрано в разделе «Открытые вопросы к qr».

---

## Executive Summary

Интеграция направлена **push**: qr (мастер коммерции) после оплаты публикует событие `OrderPaid`; org (мастер билетов) консьюмит его, валидирует, материализует заказ → билеты → PDF/QR → email-подтверждение (НЕ-фискальный чек) → анкеты → синхронизация в BAZA. BAZA остаётся мастером входа/кассы и автономна офлайн.

**Ключевые проектные решения этого RFC:**

1. **Нейтральный конверт события** `{schema_version, event_type, trace_id, idempotency_key, occurred_at, source, payload}` — один для всех будущих событий (`order.paid`, далее `order.refunded` и т.д.).
2. **org заводит собственный `order_id`**, а `order_id` из qr хранит как `external_order_id` (org остаётся мастером своей PK-схемы `order_tickets`; нужно для дедупликации и обратной связи).
3. **org НЕ исполняет команду qr слепо:** проверяет подпись, idempotency, бизнес-инварианты (фестиваль / тип / опции / промокод / лимиты / сумму) до выпуска билета.
4. **Транспорт — на том, что есть в проде:** MySQL transactional outbox (отправитель) + подписанный HTTP-webhook (push) + idempotency-таблица на приёме + ретраи/DLQ на существующем `failed_jobs`. RabbitMQ не вводится (в проде нет).
5. **Прямая запись org → БД BAZA выводится** на аутентифицированный внутренний API BAZA (или подписанное событие) — взамен `DB::connection('mysqlBaza')` с хардкод-кредами.
6. **Безопасность — обязательное предусловие разворота:** HMAC-SHA256 подпись сырого тела + anti-replay, idempotency at-most-once на выпуск билета, строгая (whitelist) валидация полей, IP-allowlist, согласие 152-ФЗ в конверте.

Самая срочная задача **не зависит от qr и закрывается немедленно:** убрать root-креды BAZA из `Backend/config/database.php` в env + ротация пароля `common404` (скомпрометирован фактом коммита в git).

---

## Контекст (как сейчас в коде — отправная точка, подтверждено Read/grep)

| Факт | Где в коде |
|------|-----------|
| Очереди — только MySQL (`jobs` / `failed_jobs`), RabbitMQ в проде нет | `Backend/config/queue.php` (`default = sync`, рабочая `database`, `failed.driver = database-uuids`) |
| org пишет в БД BAZA **напрямую**, креды хардкодом `root` / `common404`, без подписи | `Backend/config/database.php` (conn `mysqlBaza`, строки 66–78), методы `setInBaza` / `setInBazaList` / `setInBazaLive` в `Backend/src/Ticket/CreateTickets/Repositories/InMemoryMySqlTicketsRepository.php` |
| Платёжный webhook `/api/v1/order/succes` **не проверяет источник** | `Backend/routes/order.php` — `Route::any('/v1/order/succes', BillingController::webHook)` (без middleware) |
| `createTickets()` дедуплицирует по `whereId(...)->exists()` (upsert по `uuid`) | `InMemoryMySqlTicketsRepository.php` |
| Уже есть правильный паттерн `hash_equals` для сравнения секрета | `OrderTickets::create` (заголовок `AutoPayment`, `AUTO_PAYMENT_TOKEN`) |
| Заказ в org — контейнер строк `OrderGuestLine` (тип / опции / промо / `price_snapshot` per-guest) | `Backend/src/Order/OrderTicket/Domain/ValueObject/OrderGuestLine.php`, `MoneySnapshot.php`, `OrderGuestOption.php` |
| Контакты получателя на заказе: `email`, `phone`, `city`, `name` | `OrderTicketDto` getters |
| Деньги в org — **целые рубли** (`Money` в `int`) | `Shared/Domain/ValueObject/Money.php`, `MoneySnapshot` |
| Схема BAZA `el_tickets` (`$fillable`) | `Baza/app/Models/ElTicketsModel.php`: `kilter, uuid, name, email, phone, comment, date_order, status, change_id, date_change, is_need_seedling, type_ticket, type_ticket_id` (поле `festival_id` добавлено миграцией прода, в `$fillable` сейчас отсутствует) |
| `POST scan` / `POST enter` в BAZA — **без auth-middleware** | `Baza/routes/api.php` |

Из этого вытекает направление: **qr публикует `OrderPaid` → org консьюмит → создаёт билеты + PDF + чек → синхронизирует в BAZA через внутренний API/событие** (не прямой `DB::connection`).

---

## 1. Событие `OrderPaid` (доменное событие, payload)

Поля payload спроектированы по аналогии с `OrderGuestLine` (org — мастер билетов, формат гостя должен ложиться 1:1 на VO org). qr — мастер коммерции, поэтому именно он сообщает **факт и сумму оплаты** и снимки цен.

| Поле | Тип | Обяз. | Семантика / маппинг на org |
|------|-----|-------|----------------------------|
| `order_id` | uuid | да | Идентификатор заказа в qr. **НЕ** используется как PK `order_tickets` напрямую — org заводит свой `order_id`, а `order_id` из qr хранит как `external_order_id`. `[QR?]` формат — uuid v4? |
| `festival_id` | uuid | да | Должен существовать в каталоге org (`festivals`). org валидирует. |
| `payment` | object | да | Факт оплаты (см. ниже). |
| `recipient` | object | да | Контакты получателя билетов → `OrderTicketDto`: `email/phone/city/name`. |
| `guests[]` | array | да | Строки заказа → `OrderGuestLine[]`. |
| `consent` | object | да | Метаданные согласия 152-ФЗ (см. security §6). org отклоняет событие без `consent.given=true`. |
| `comment` | string \| null | нет | Комментарий заказа. |
| `friendly_id` / `curator_id` | uuid \| null | нет | Если qr умеет friendly/list — иначе всегда null. `[QR?]` |

**`payment` object:**

| Поле | Тип | Обяз. | Семантика |
|------|-----|-------|-----------|
| `total` | int | да | Итог оплаты. `[QR?]` **единица измерения** — org `Money`/`MoneySnapshot` работает в **целых рублях**. Зафиксировать: рубли или копейки. |
| `currency` | string | да | `"RUB"` (org поддерживает только RUB). |
| `paid_at` | string (ISO-8601 UTC) | да | Время оплаты у qr. |
| `provider` | string | да | Платёжный провайдер qr (для чека / аудита). `[QR?]` |
| `receipt_url` | string \| null | нет | Если qr формирует свой чек. org **всё равно** формирует своё PDF/email-подтверждение (НЕ-фискальный чек, решение №7 совещания). |
| `external_payment_id` | string | да | ID платежа у qr — для аудита и сверки. |

**`recipient` object:** `{ "email": string, "phone": string, "city": string, "name": string|null }`.

**`guests[]` элемент** (1:1 с `OrderGuestLine`):

| Поле | Тип | Обяз. | Маппинг |
|------|-----|-------|---------|
| `value` | string | да | ФИО/данные гостя (для парковки — склейка). → `OrderGuestLine.value` |
| `email` | string \| null | нет | → `OrderGuestLine.email` |
| `number` | int \| null | нет | Номер живого билета (если live). → `OrderGuestLine.number` |
| `ticket_type_id` | uuid | да | Должен принадлежать фестивалю в каталоге org. org **валидирует**. → `ticketTypeId` |
| `options[]` | array | нет | `{option_id, name, price}` — снимок (как `OrderGuestOption`). org валидирует, что опция активна и привязана к типу. |
| `promo_code` | string \| null | нет | → `promoCode`. org валидирует существование/лимит. |
| `price_snapshot` | object | да | `{base_price, options_sum, discount}` (как `MoneySnapshot`). org **пересчитывает** независимо (сверка суммы, решение №6 — не исполняет слепо). |
| `is_live_ticket` | bool | нет | → `isLiveTicket`. Инвариант org: live + non-live в одном заказе нельзя. |

**Пример `OrderPaid` payload:**

```json
{
  "order_id": "0c2f1e7a-3b44-4c9a-9f10-1a2b3c4d5e6f",
  "festival_id": "9a8b7c6d-5e4f-4a3b-2c1d-0e9f8a7b6c5d",
  "payment": {
    "total": 7600,
    "currency": "RUB",
    "paid_at": "2026-06-13T09:41:22Z",
    "provider": "qr_sbp",
    "receipt_url": "https://qr.spaceofjoy.ru/receipt/abc123",
    "external_payment_id": "qr-pay-558123"
  },
  "recipient": {
    "email": "ivanov@example.com",
    "phone": "+79991234567",
    "city": "Москва",
    "name": "Иванов Иван"
  },
  "consent": {
    "given": true,
    "policy_version": "2026-06-01",
    "consented_at": "2026-06-13T09:39:00Z",
    "subject_ip": "1.2.3.4"
  },
  "comment": null,
  "friendly_id": null,
  "curator_id": null,
  "guests": [
    {
      "value": "Иванов Иван Иванович",
      "email": "ivanov@example.com",
      "number": null,
      "ticket_type_id": "d4e5f6a7-b8c9-4012-cdef-345678901111",
      "options": [
        { "option_id": "aa11bb22-cc33-4455-6677-8899aabbccdd", "name": "Саженец", "price": 0 }
      ],
      "promo_code": "SPRING10",
      "price_snapshot": { "base_price": 4200, "options_sum": 0, "discount": 420 },
      "is_live_ticket": false
    },
    {
      "value": "Петрова Анна Сергеевна",
      "email": "petrova@example.com",
      "number": null,
      "ticket_type_id": "d4e5f6a7-b8c9-4012-cdef-345678901111",
      "options": [],
      "promo_code": null,
      "price_snapshot": { "base_price": 3800, "options_sum": 0, "discount": 0 },
      "is_live_ticket": false
    }
  ]
}
```

> Сумма `payment.total` должна сходиться с Σ `price_snapshot.total` гостей (`base_price + options_sum − discount` по каждой строке). При расхождении org обязан отвергнуть/пометить заказ «требует ручной проверки» и **не выпускать** PDF/чек автоматически (см. §3).

---

## 2. Нейтральный конверт события

Один конверт для всех событий между сервисами (сейчас актуален `OrderPaid`, дальше — `OrderRefunded`, `OrderUpdated`). Соответствует решению совещания.

| Поле | Тип | Семантика |
|------|-----|-----------|
| `schema_version` | string (semver) | Версия схемы **payload** (`"1.0"`). Эволюция без слома. |
| `event_type` | string | `"order.paid"` — тип события. **Рекомендация к согласованию:** без него консьюмер не знает, как разбирать payload. |
| `trace_id` | uuid | Сквозной идентификатор для логов/корреляции qr ↔ org ↔ BAZA. |
| `idempotency_key` | string | Ключ дедупликации (см. §3). Рекомендация: `"{event_type}:{order_id}:{external_payment_id}"` — стабилен при ретраях, уникален на бизнес-событие. `[QR?]` генерирует qr. |
| `occurred_at` | string (ISO-8601 UTC) | Время возникновения события у источника. |
| `source` | string | `"qr"`. Для будущих обратных событий org → qr — `"org"`. |
| `payload` | object | Тело (`OrderPaid` из §1). |

**Пример конверта:**

```json
{
  "schema_version": "1.0",
  "event_type": "order.paid",
  "trace_id": "f1e2d3c4-b5a6-4789-9012-3456789abcde",
  "idempotency_key": "order.paid:0c2f1e7a-3b44-4c9a-9f10-1a2b3c4d5e6f:qr-pay-558123",
  "occurred_at": "2026-06-13T09:41:23Z",
  "source": "qr",
  "payload": { "...": "см. §1" }
}
```

---

## 3. Поведение консьюмера на стороне org

Эндпоинт-приёмник (новый, рядом с `/order/succes`, но **с обязательной проверкой подписи** — в отличие от текущего webhook):
`POST /api/v1/integration/qr/order-paid` (middleware: проверка подписи источника + IP-allowlist, см. security §1, §3).

**Алгоритм (по шагам):**

1. **Проверка подписи и `source`** (security §1). Невалидно → `401/403`, событие **не** записывается, лог только после проверки и с маскированием ПДн.
2. **Идемпотентность по `idempotency_key`.** Таблица `inbound_events` / `processed_messages` (новая): `idempotency_key` UNIQUE.
   - Ключ уже есть и `processed` → вернуть `200` (тот же результат, без повторного создания билетов).
   - Ключ есть и `processing` / `failed` → `409` / `202` (qr ретраит позже) либо переобработка по политике.
   - Ключа нет → вставить строку `received` (`insertOrIgnore`, атомарно ловит гонку), продолжить.
3. **Anti-replay по `occurred_at`** (окно ±5 мин, см. security §2). Устаревшее/будущее → `409`.
4. **Сохранить сырой конверт** в `raw_payload` (JSON) + `trace_id` — для аудита и разбора.
5. **Поставить внутреннюю джобу** `ProcessQrOrderPaid` в очередь (`database` connection) — HTTP отвечает `202 Accepted`, тяжёлая работа асинхронно (как у org все письма/PDF — `ShouldQueue`).
6. **Валидация бизнес-инвариантов** (решение №6 — org не исполняет слепо). В джобе, до создания заказа (см. security §5):
   - `festival_id` существует в каталоге org;
   - каждый `ticket_type_id` принадлежит фестивалю; опции активны и привязаны к типу; промокод валиден (лимит/активность);
   - live + non-live в одном заказе — запрет;
   - дубль `number` live — запрет (есть `checkLiveNumber`);
   - **сверка суммы:** Σ `price_snapshot.total` == `payment.total` (с учётом единицы измерения, `[QR?]`). Расхождение → статус «требует ручной проверки» + алерт, PDF/чек **не выпускаем** автоматически.
7. **Создание заказа** через существующий домен: собрать `OrderTicketDto` (per-guest строки `OrderGuestLine` из payload, `external_order_id = payload.order_id`), вызвать `OrderTicket::toPaid(...)` (live → `toPaidInLiveTicket` / `toLiveIssued`). Порождает существующие доменные события:
   - `ProcessCreateTicket` → билеты;
   - `ProcessCreatingQRCode` → QR + PDF (`storage/app/public/tickets/{id}.pdf`);
   - `ProcessUserNotificationOrderPaid` → email с PDF (это и есть **чек-подтверждение**, НЕ-фискальное, решение №7);
   - `ProcessGuestNotificationQuestionnaire[]` → анкеты гостям.
8. **Синхронизация в BAZA** — через внутренний контракт §4 (не прямой `DB::connection('mysqlBaza')`).
9. Пометить событие `processed`. Ошибка на шагах 6–8 → джоба падает → ретраи/`failed_jobs` (§5 транспорта).

**Что создаёт консьюмер:** заказ (`order_tickets`) → билеты (`tickets`) → QR + PDF → email-подтверждение (чек) → анкеты гостям → запись в BAZA. org остаётся мастером билета/чека/каталога; qr — мастером коммерции.

**Идемпотентность билетов (двойная защита):** даже при двойном выполнении джобы `createTickets()` проверяет `whereId(...)->exists()`, а `setInBaza` — upsert по `uuid`. Плюс `inbound_events.idempotency_key` UNIQUE на входе. Двойного выпуска быть не должно.

---

## 4. Внутренний контракт org → BAZA (взамен прямой записи в чужую БД)

Сейчас org вызывает `DB::connection('mysqlBaza')->table('el_tickets')->insert(...)` с хардкод-кредами `root` / `common404` и без подписи — это и техдолг, и security-дыра. Замена — **аутентифицированный API BAZA** (BAZA уже использует `auth:sanctum` на `/user`, инфраструктура есть).

**Новый эндпоинт BAZA** (рядом с `scan` / `enter` в `Baza/routes/api.php`):

`POST /api/tickets/sync` — апсерт «расширенного билета». Middleware: `auth:sanctum` (token сервис-аккаунта org) **+** проверка подписи (по образцу security §1). Идемпотентность по `uuid` билета (как сейчас `setInBaza` дедуплицирует по `uuid`).

**Поля «расширенного билета»** (база — реальный `el_tickets.$fillable` + `spisok_tickets` / `live_tickets`; новые поля — заглушки `[QR?]` / `[BAZA?]`):

| Поле | Тип | Источник в org | Статус |
|------|-----|----------------|--------|
| `uuid` | uuid | id билета (PK дедупликации) | существует (`el_tickets.uuid`) |
| `kilter` | int | `Ticket.kilter` | существует |
| `name` | string | `OrderGuestLine.value` | существует |
| `email` | string | гость/получатель | существует |
| `phone` | string | заказ | существует |
| `city` | string | заказ | существует (есть в `setInBaza` payload) |
| `status` | string | статус заказа/билета | существует |
| `comment` | string | последний комментарий | существует |
| `date_order` | datetime | created_at заказа | существует |
| `is_need_seedling` | bool | флаг саженца | существует |
| `type_ticket_id` | uuid | `ticket_type_id` | существует |
| `type_ticket` | string | имя типа билета | существует |
| `festival_id` | uuid | фестиваль | добавлено миграцией прода (в текущем `$fillable` отсутствует — учитывать) |
| `ticket_kind` | enum | `el` / `spisok` / `live` / `parking` — какой таблицей BAZA обслуживается | **заглушка** — заменяет логику выбора таблицы, что сейчас в коде org |
| `options[]` | array | снимок опций гостя | **заглушка** `[BAZA?]` — нужно ли BAZA на входе/кассе знать про опции (саженец и т.п.)? |
| `external_order_id` | uuid | `order_id` из qr | **заглушка** — для сверки и обратной связи |

**Контракт ответа BAZA:** `{ "success": true, "uuid": "...", "action": "created|updated|noop" }`. Ошибка валидации → `422`, ошибка авторизации → `401`.

**Промежуточный шаг (без переписывания BAZA сразу):** оставить три существующих метода `setInBaza` / `setInBazaList` / `setInBazaLive`, но спрятать их за этим API (org вызывает HTTP, BAZA внутри пишет тем же кодом). Это убирает прямой коннект к чужой БД и хардкод-креды, не ломая текущую запись. `[BAZA?]` подтвердить, что можно добавить эндпоинт в `auth:sanctum`-группу.

---

## 5. Транспорт: MySQL outbox + подписанный webhook + ретраи/DLQ

RabbitMQ в проде нет — строим на том, что есть (`jobs` / `failed_jobs`, `database` connection). Push-модель.

### 5.1. Inbound (qr → org): подписанный webhook + idempotency

- Подпись: HMAC-SHA256 от **сырого тела** запроса общим секретом (`QR_WEBHOOK_SECRET` в `.env`), заголовок `X-Signature: sha256=...` + `X-Timestamp` (anti-replay, окно ±5 мин). Сравнение через `hash_equals` (паттерн уже есть в коде — `AutoPayment`). `[QR?]` HMAC или подпись приватным ключом qr — на выбор qr (см. security §1).
- IP-allowlist qr (решение совещания) — на уровне middleware / nginx (security §3).
- Idempotency: таблица `inbound_events` / `processed_messages` (`idempotency_key` UNIQUE) — §3.
- Текущий `/order/succes` (`BillingController::webHook`) **дорабатываем тем же механизмом** — сейчас принимает любой запрос (дыра).

### 5.2. Outbox на стороне отправителя

- Транзакционный outbox: при изменении состояния в той же БД-транзакции пишется строка в `outbox_events` (`id, event_type, idempotency_key, payload(JSON), status, attempts, next_attempt_at, created_at`). Гарантирует «состояние изменилось ⟺ событие зафиксировано» (нет потери при падении между commit и публикацией).
  - На стороне qr (Python) — `[QR?]` qr реализует свой outbox.
  - На стороне org (для обратных событий org → qr и для org → BAZA, если через событие) — таблица `outbox_events` в основной БД org.
- Публикатор: воркер (Laravel job по расписанию / отдельный процесс) читает `outbox_events WHERE status='pending'`, шлёт подписанный HTTP-POST, при `2xx` → `status='sent'`.

### 5.3. Ретраи и DLQ на имеющемся (`failed_jobs`)

- Внутренняя обработка (`ProcessQrOrderPaid`, публикация outbox) — обычные Laravel-джобы на `database` connection.
- Ретраи: `public $tries = 5; public $backoff = [60, 300, 900, 3600, 7200];` (экспоненциально). `retry_after=1800` уже в `config/queue.php`.
- **DLQ = `failed_jobs`** (уже сконфигурирован, `database-uuids`). После исчерпания `$tries` джоба попадает в `failed_jobs` — это и есть dead-letter. Мониторинг: алерт при росте `failed_jobs`; ручной разбор `php artisan queue:retry`.
- Для outbox-публикации: после N неуспешных HTTP — `outbox_events.status='dead'` + алерт (отдельный «DLQ» на уровне строки, чтобы джоба-публикатор не падала в `failed_jobs` на каждое событие).

### Схема потока

```
[qr] оплата
   └─(tx)→ qr.outbox_events (OrderPaid)
        └─ qr publisher → POST /api/v1/integration/qr/order-paid  (HMAC + X-Timestamp)
              └─[org] проверка подписи → inbound_events (idempotency_key UNIQUE) → 202
                    └─ job ProcessQrOrderPaid (database queue, tries=5, backoff)
                         ├─ валидация инвариантов (festival/type/options/promo/сумма)
                         ├─ OrderTicket::toPaid → ProcessCreateTicket / QR+PDF / email(чек) / анкеты
                         └─ org→BAZA: POST /api/tickets/sync (auth:sanctum + подпись)
                    исчерпаны tries → failed_jobs (DLQ) + алерт
```

---

## 6. Security-предусловия гибрида qr ↔ org/BAZA

> Раздел опирается на реальный код (Read/grep). Где требуется подтверждение Python-стороны (qr) — помечено как открытый вопрос. Боевой код — только после релиза v2.6.0.

Базовая модель угроз: **qr — мастер коммерции (заказ/оплата), org/BAZA — мастер билетов**. org консьюмит `OrderPaid` от qr и **выпускает реальные билеты (PDF/QR) с денежной стоимостью**. Значит, любая команда/событие от qr — это **untrusted input, способный выпустить ценность**. Отсюда 6 предусловий ниже.

### Текущее состояние (отправная точка)

| Что | Где (реальный файл) | Проблема |
|-----|---------------------|----------|
| Webhook `/v1/order/succes` | `routes/order.php` → `BillingController::webHook` | `Route::any`, публичный, **нет проверки источника**, нет подписи, нет idempotency. Любой POST с `data.metadata.order_id` + `type=payment.completed` переводит заказ в `PAID` и выпускает билеты |
| Прямая запись org → БД BAZA | `config/database.php` (conn `mysqlBaza`): `host='database'`, `username='root'`, `password='common404'` хардкодом | Креды в репозитории; root-доступ к чужой БД; обход бизнес-правил BAZA; **никакой подписи/аутентификации** |
| Методы записи в BAZA | `InMemoryMySqlTicketsRepository.php`: `setInBaza`, `setInBazaList`, `setInBazaLive`, `checkLiveNumber` | Пишут `INSERT/UPDATE` напрямую в `el_tickets` / `spisok_tickets` / `live_tickets` через `DB::connection('mysqlBaza')` |
| Скан/вход в BAZA | `Baza/routes/api.php`: `POST scan`, `POST enter` | **Без auth-middleware** — публичные (сопутствующая дыра, в скоупе IdP) |
| Хороший образец (эталон) | `OrderTickets::create`: `hash_equals((string)$token, $header)` | Уже есть правильный паттерн сравнения секрета с защитой от timing-attack |
| Анти-паттерн (не повторять) | `app/Http/Middleware/Bot.php`: `const TOKEN_AI = 'PCf4yeeM...'` хардкодом | Секрет в коде — переносить в env |

### 6.1. Подпись входящих событий/команд от qr

**Рекомендация: HMAC-SHA256 с общим секретом** (а не подписанный JWT).

Обоснование выбора:
- В проекте **уже устоялся симметричный паттерн** общего секрета + `hash_equals` (`AutoPayment`, `AUTO_PAYMENT_TOKEN` в env). HMAC — его прямое продолжение, минимальная новая поверхность.
- qr на Python: `hmac.new(secret, body, sha256).hexdigest()` — тривиально, без libs для JWT/JWK.
- Подписанный JWT (RS256) оправдан только если подписантов >1 или нужна ротация публичных ключей. Здесь один доверенный издатель (qr) → асимметрия избыточна.
- JWT/Passport (`.claude/specs/qr-sso-security.md`) остаётся для **пользовательского SSO** (org=IdP, qr=PKCE-клиент). Это **другой канал** — не путать с подписью server-to-server событий. Webhook от qr подписывается HMAC, а не Bearer-JWT пользователя.

**Что подписывать:** сырое тело запроса целиком (`raw body`, до JSON-парсинга) + `X-Timestamp` (подпись покрывает и анти-replay окно).

Заголовки запроса от qr:
```
X-Signature: sha256=<hex hmac>
X-Timestamp: 1760000000          # unix-секунды, момент отправки
X-Idempotency-Key: <uuid>         # см. §6.2
```

Проверка на стороне org (по образцу `OrderTickets::create`, middleware на webhook-роуте):
```php
$secret    = config('services.qr_webhook.secret');   // env QR_WEBHOOK_SECRET, не хардкод
$payload   = $request->getContent();                  // СЫРОЕ тело, не toArray()
$timestamp = (string) $request->header('X-Timestamp', '');
$provided  = (string) $request->header('X-Signature', '');

// тело + таймстамп в подписываемой строке (привязка подписи к окну времени)
$expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

if ($secret === '' || ! hash_equals($expected, $provided)) {
    abort(403, 'Invalid signature');   // подпись считаем ДО парсинга и любых side-effect
}
```

- `hash_equals` обязателен — обычное `===` уязвимо к timing-attack.
- Сравниваем **раньше любых side-effect** (до `Log::debug`, до изменения статуса). Сейчас `BillingController::webHook` логирует весь request на debug **до** валидации — это утечка ПДн в логи и обработка untrusted-данных. Лог делать после проверки подписи и с маскированием ПДн (email/phone).
- qr должен слать **тот же байт-в-байт body**, что подписал — нельзя re-serialize на нашей стороне до проверки.

### 6.2. Idempotency / anti-replay

Webhook qr → выпуск билетов = **строго at-most-once на бизнес-эффект**. Сетевые ретраи qr (и злонамеренный replay перехваченного валидного запроса) не должны выпустить билет дважды. Два независимых механизма (defense in depth):

**(a) Таблица `processed_messages` по `idempotency_key`:**
```sql
CREATE TABLE processed_messages (
    idempotency_key VARCHAR(36) PRIMARY KEY,   -- из конверта
    trace_id        VARCHAR(36) NULL,
    source          VARCHAR(32) NOT NULL,      -- 'qr'
    occurred_at     TIMESTAMP NOT NULL,        -- из конверта
    processed_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    result_status   VARCHAR(16) NOT NULL       -- 'ok' | 'rejected'
);
```
Обработка в **одной транзакции** (паттерн transactional inbox):
```php
DB::transaction(function () use ($key, $payload) {
    // INSERT IGNORE / insertOrIgnore — PK ловит дубль атомарно
    $isNew = DB::table('processed_messages')->insertOrIgnore([...]) === 1;
    if (! $isNew) {
        return; // уже обработано — возвращаем 200, side-effect НЕ повторяем
    }
    // только здесь — смена статуса заказа / выпуск билетов
});
```
- PK на `idempotency_key` + `insertOrIgnore` атомарно отсекает гонку параллельных ретраев.
- Запись в БД — внутри репозитория (новый `InboxRepository`), не в контроллере (правило проекта: БД только в репозитории).

**(b) Окно времени по `occurred_at` (anti-replay):**
```php
$skewSeconds = 300; // 5 минут
$occurred = Carbon::parse($payload['occurred_at']);
if ($occurred->diffInSeconds(now(), false) > $skewSeconds || $occurred->isFuture()) {
    abort(409, 'Stale or future message');
}
```
- Перехваченный валидный запрос вне окна 5 мин — отклоняется, даже если подпись верна и ключа ещё нет в таблице.
- `X-Timestamp` (в HMAC, §6.1) и `occurred_at` (в конверте) должны совпадать — иначе reject.

### 6.3. IP-allowlist для webhook

Сетевой фильтр **поверх** подписи (не вместо — IP подделывается, но отсекает массовый шум/скан до криптопроверки).

- Список IP qr — в env, не хардкодом (контраст с текущим CORS-whitelist в коде, TD-26):
```
QR_WEBHOOK_ALLOWED_IPS=203.0.113.10,203.0.113.11
```
- Реализация — middleware на роуте `/v1/order/succes` (и на будущем `/v1/integration/qr/*`):
```php
$allowed = array_filter(explode(',', (string) config('services.qr_webhook.allowed_ips')));
if (! empty($allowed) && ! in_array($request->ip(), $allowed, true)) {
    abort(403);
}
```
- Учесть reverse-proxy: за nginx брать реальный IP через корректно настроенный `TrustProxies` (иначе `$request->ip()` вернёт IP прокси). **Проверить `App\Http\Middleware\TrustProxies`** перед включением.
- Лучший вариант (если инфра позволяет) — фильтр на уровне nginx/firewall до PHP; PHP-allowlist остаётся вторым слоем.

### 6.4. Вывод прямой записи org → БД BAZA на аутентифицированный канал + секреты в env

**Самая опасная точка.** Сейчас org с **root-кредами в репозитории** пишет в чужую БД в обход всех бизнес-правил BAZA.

**Немедленно (до любого разворота, не ждёт qr):**
1. Убрать хардкод из `config/database.php` (conn `mysqlBaza`) → перевести на env:
```php
'mysqlBaza' => [
    'driver'   => 'mysql',
    'host'     => env('DB_BAZA_HOST', 'database'),
    'port'     => env('DB_BAZA_PORT', '3306'),
    'database' => env('DB_BAZA_DATABASE', 'baza'),
    'username' => env('DB_BAZA_USERNAME'),
    'password' => env('DB_BAZA_PASSWORD'),
    // ...
],
```
Добавить `DB_BAZA_*` в `.env.example` (по образцу `KEY_CLIENT_BILLING` / `AUTO_PAYMENT_TOKEN`).
2. **Сменить пароль `common404`** на проде и в BAZA — он скомпрометирован самим фактом коммита в git. Проверить `git log -p -- Backend/config/database.php` (пароль уже в истории git → ротация обязательна, недостаточно убрать из текущего файла).
3. Завести для org **отдельного MySQL-пользователя BAZA** с правами **только** на `el_tickets` / `spisok_tickets` / `live_tickets` (`GRANT INSERT/UPDATE/SELECT`), без root.

**Целевое (вместе с разворотом, замена прямой записи):**
- Заменить `DB::connection('mysqlBaza')->...insert/update(...)` (`setInBaza` / `setInBazaList` / `setInBazaLive`) на вызов **аутентифицированного API BAZA** (§4) или публикацию **подписанного события** `TicketIssued` (тот же HMAC-конверт §6.1, BAZA — консьюмер).
- Параллельно закрыть `POST scan` / `POST enter` в `Baza/routes/api.php` — сейчас **без auth-middleware**. Это вход в день фестиваля → обязательно за аутентификацией (BAZA как IdP/Passport по `.claude/specs/qr-sso-security.md`, либо минимум server-to-server подпись).
- Зафиксировать в DOMAIN.md и TECH_DEBT.md (родственно TD-26: вынос секретов в env).

### 6.5. Автономная валидация команд qr на стороне org (defense in depth)

Решение встречи: **org валидирует каждую команду qr** (подпись + лимиты + бизнес-инварианты) и автономен офлайн в день фестиваля. Подпись (§6.1) подтверждает «команда от qr», но **не** «команда корректна». org не доверяет qr содержимое — qr может быть скомпрометирован или иметь баг.

Слои валидации (все — до side-effect, до выпуска билета):

| Проверка | Правило | Реакция на провал |
|----------|---------|-------------------|
| Существование фестиваля | `festival_id` есть в `festivals` | reject 422, `result_status='rejected'` |
| Существование типа билета | `ticket_type_id` есть и принадлежит фестивалю | reject 422 |
| Активность опций | каждая `option_id` активна и привязана к типу | reject 422 |
| Лимит кол-ва гостей | `count(guests) <= QR_MAX_GUESTS_PER_ORDER` (env, напр. 50) | reject 422 |
| Лимит суммы | `total <= QR_ORDER_MAX_AMOUNT` (env, напр. 500000 ₽) | reject + алерт в Sentry |
| Неотрицательность | `price >= 0`, `qty >= 1` | reject 422 |
| Whitelist полей | **отклонять неизвестные ключи** в payload (strict-валидация FormRequest, не `$request->all()`) | reject 422 |
| Дубль номера live | `number` уникален и не выдан (есть `checkLiveNumber`) | reject 409 |
| Статусная машина | переход допустим по матрице `Shared/Domain/ValueObject/Status` | reject 409 |

Реализация:
- Отдельный **строгий FormRequest** для входящего qr-события (по образцу TD-29 — раздельные FormRequest, не `$request->all()`). Strict-режим: неизвестное поле = ошибка, а не молчаливое игнорирование. Защита от того, что qr пришлёт `is_admin` / `status='paid'` / произвольный `price` и org это проглотит.
- Переиспользовать существующий `OrderPriceCalculator` как **источник истины по цене**: цену из события qr использовать только для сверки/чека, фактический выпуск считать своим калькулятором (по аналогии с Friendly, где «рассчитанная цена отбрасывается»). Расхождение qr-цены и пересчёта org > порога → reject + алерт. **Открытый вопрос:** в гибриде qr — мастер цены/оплаты, поэтому уточнить, org **доверяет** сумму qr (qr уже принял деньги) или **пересчитывает** и расхождение = инцидент. От этого зависит, что писать в чек.
- Лимиты — в env (`QR_ORDER_MAX_AMOUNT`, `QR_MAX_GUESTS_PER_ORDER`), не в коде.

### 6.6. Где живёт consent 152-ФЗ при гибриде

**Мастер ПДн в гибриде — qr** (витрина + заказ: именно там пользователь вводит email/phone/город/ФИО гостей при оформлении и оплате). Значит **первичный consent на обработку ПДн собирает qr** в момент оформления — на своей стороне, до отправки события в org.

Требования:
1. **qr собирает и хранит факт согласия** (галочка «согласен на обработку ПДн» + версия Политики + timestamp + IP). Переезжает на qr вместе с формой заказа.
2. **Конверт события `OrderPaid` обязан нести метаданные согласия** — org как обработчик ПДн (генерит билет / PDF / email) должен иметь доказательство правового основания:
```json
"payload": {
  "consent": {
    "given": true,
    "policy_version": "2026-06-01",
    "consented_at": "2026-06-13T16:59:00Z",
    "subject_ip": "1.2.3.4"
  }
}
```
   org **отклоняет событие без `consent.given=true`** (валидация §6.5) — нельзя выпускать билет на ПДн без правового основания.
3. **Передача ПДн между двумя операторами/обработчиками** (qr → org) сама требует основания. В Политике конфиденциальности (на qr и на org) должно быть явно указано, что данные передаются между сервисами `qr.spaceofjoy.ru` и `spaceofjoy.ru` для выпуска и проверки билетов. Это **дополнение к существующей Политике** — пометить в TECH_DEBT (родственно TD-7).
4. **SSO-канал (`qr-sso-security.md`) — отдельная история consent:** там нужен **consent screen** при первом входе пользователя org в qr (явное согласие на передачу ПДн при OAuth-flow). Уже заложено в SSO-спеке (org=IdP) — не дублировать, переиспользовать.
5. **РКН (TD-7):** при гибриде у проекта **два контура обработки ПДн** (qr в РФ + org). Уточнить с товарищем-помощником по РКН: регистрируется ли оператором одно юрлицо на оба домена, или нужна фиксация поручения обработки (договор между qr и org как обработчиками). Вне кода, но блокирует «чисто» с точки зрения 152-ФЗ.

### Сводка приоритетов для разворота (security)

| # | Предусловие | Срочность | Зависит от qr? |
|---|-------------|-----------|----------------|
| 6.4a | Убрать root-креды BAZA из `config/database.php` в env + ротация `common404` | **Немедленно** (утечка в git) | Нет |
| 6.1 | HMAC-SHA256 подпись webhook/событий + `hash_equals` | До приёма боевых событий | Да (формат подписи) |
| 6.2 | `processed_messages` + окно `occurred_at` (idempotency/anti-replay) | До приёма боевых событий | Да (стабильность ключа, skew) |
| 6.5 | Строгий FormRequest + автономная валидация (лимиты/инварианты) | До приёма боевых событий | Частично (семантика цены) |
| 6.6 | `consent` в конверте + отказ без него + Политика про передачу ПДн | До приёма боевых событий | Да (qr хранит согласия) |
| 6.3 | IP-allowlist webhook (env) + `TrustProxies` | Желательно | Да (пул IP qr) |
| 6.4b | Вывод прямой записи в BAZA на API/событие + auth на `scan` / `enter` | Целевое (вместе с разворотом) | Нет (BAZA-сторона) |

Всё боевое — после релиза v2.6.0 (решение встречи). Сейчас — фиксация требований и подготовка env-каркаса. Коммитов в код без одобрения пользователя не делаем.

---

## Минимальные шаги (для оценки, не код сейчас — режим «бумага» до v2.6.0)

| Шаг | Действие | Зависит от |
|-----|----------|-----------|
| 1 | Согласовать схему `OrderPaid` + конверт с qr | `[QR?]` |
| 2 | Ввести подпись + idempotency в `/order/succes` (закрыть дыру, независимо от разворота) + убрать root-креды BAZA в env + ротация `common404` | — |
| 3 | Таблицы `inbound_events` / `processed_messages`, `outbox_events` | шаг 1 |
| 4 | Эндпоинт-консьюмер `/integration/qr/order-paid` + джоба `ProcessQrOrderPaid` | шаги 2, 3 |
| 5 | Эндпоинт BAZA `/api/tickets/sync` + перевод `setInBaza*` за HTTP, убрать прямой `DB::connection('mysqlBaza')` | `[BAZA?]` |

---

## Открытые вопросы к qr

Единый список вопросов к команде `qr.spaceofjoy.ru`, требующих подтверждения перед разворотом боевого кода (объединены и дедуплицированы из всех трёх разделов: карта функций, контракт, security).

### A. Идентификация и схема события

1. **Поля события `OrderPaid`.** Точный payload, который публикует qr: `order_id` / `external_order_id`, сумма, валюта, состав строк/гостей, тип билета, опции, промокод, ссылка на чек, фискальные данные (если есть)?
2. **Формат `order_id` из qr** — uuid v4? Приемлемо ли, что org заведёт собственный `order_id`, а qr-шный сохранит как `external_order_id` (для дедупликации и обратной связи)?
3. **Единица измерения `payment.total` и `price_snapshot`** — рубли или копейки? org `Money`/`MoneySnapshot` работает в **целых рублях**; без фиксации сверка суммы сломается.
4. **Версионирование схемы** — согласны на поле `schema_version` (semver) + `event_type` в конверте для эволюции без слома?
5. **Типы заказов friendly/list** — поддерживает ли qr поля `friendly_id` / `curator_id`, или всегда обычный заказ?

### B. Транспорт, подпись, idempotency

6. **Транспорт webhook qr → org** — HTTP push на эндпоинт org (зафиксировано в RESULTS.md), или org поллит outbox qr? qr технически готов делать исходящие webhook с ретраями?
7. **Outbox на стороне qr** — реализует ли qr транзакционный outbox для гарантии доставки?
8. **Механизм подписи.** HMAC-SHA256 от строки `timestamp + '.' + raw_body` (заголовки `X-Signature` + `X-Timestamp`, anti-replay ±5 мин), ИЛИ подпись приватным ключом qr? Какой секрет/ключ, где хранится, как ротируется, по какому каналу передаётся?
9. **Канонизация JSON.** qr шлёт **тот же байт-в-байт body**, что подписал (org не должен re-serialize до проверки). Подтвердить порядок полей в подписываемой строке.
10. **Стабильность `idempotency_key`.** Кто генерирует и по какой формуле? Предложение org: `"{event_type}:{order_id}:{external_payment_id}"`. Гарантирует ли qr один и тот же ключ между сетевыми ретраями одного события?
11. **Допустимый clock skew** между qr и org для anti-replay по `occurred_at` (предлагаем 300 секунд).
12. **Код возврата на повторный ключ.** Что qr ждёт в ответе на webhook — синхронный `202 Accepted` (org обработает асинхронно) или синхронный результат с номерами билетов? Какой код возврата на повторный `idempotency_key`?
13. **IP-allowlist.** Статический пул исходящих IP qr-сервера? Если IP плавающие — переходим на mTLS вместо IP-фильтра.

### C. Каталог, цена, промокоды, способы оплаты

14. **Мастер каталога** (фестивали / типы / волны цен / опции). qr читает каталог из org по API, или ведёт свою копию? Если копию — как синхронизируем и кто разрешает конфликты цен?
15. **Каталог опций.** Совпадает ли каталог опций qr с org (`option_id`)? Подтверждаем, что org — мастер опций (модуль `Backend/src/Option`)?
16. **Промокоды.** Остаются полностью на qr (мастер коммерции), или org тоже валидирует/учитывает лимиты независимо? Запланированный в org промокоды-агрегатор (v2.7.0) — отменяем или совмещаем? Будет ли qr слать `promo_code` per-guest?
17. **Семантика цены.** В гибриде qr — мастер оплаты (деньги уже приняты): org **доверяет** итоговую сумму из события, или **пересчитывает** своим `OrderPriceCalculator` и расхождение считает инцидентом? От этого зависит, что писать в НЕ-фискальный чек.
18. **Способы оплаты на витрине qr.** Нужен ли qr справочник `types_of_payment` org, или qr ведёт свой? Флаг `is_billing` в org имеет особую семантику («внешний заказ с авто-подтверждением») — как соотносится с реальными способами оплаты qr?

### D. Чек, возвраты, обратная связь

19. **Фискальный чек (ОФД / 54-ФЗ)** — формирует ли его qr / платёжный шлюз, или это вне зоны обоих? org делает только НЕ-фискальный чек (PDF/email) — согласовано?
20. **Возвраты/отмены (refund).** qr публикует отдельное событие `OrderRefunded`, или это часть статусной модели? Как маппится на матрицу статусов org (`CANCEL`)?
21. **Обратная связь org → qr.** Нужны ли qr события от org (`OrderRefunded` / ticket issued, `source='org'`) и по какому каналу?

### E. SSO и 152-ФЗ

22. **SSO.** Подтверждаете роль org/BAZA как IdP и qr как публичный PKCE-клиент (по `.claude/specs/qr-sso-security.md`)? Какой `redirect_uri` и где qr хранит токены (httpOnly secure cookie vs иное)?
23. **Consent 152-ФЗ.** Где qr физически хранит журнал согласий (галочка / версия Политики / timestamp / IP) и согласен ли qr добавить блок `consent` в конверт каждого события с ПДн (org отклоняет событие без `consent.given=true`)?

### F. Сторона BAZA (для tech-lead/BAZA, не qr)

24. Готова ли BAZA принять выделенный аутентифицированный API (`POST /api/tickets/sync` в `auth:sanctum`-группе) / событийный канал взамен прямой записи org в её БД, или на v2.6.0 ограничиваемся минимумом (env-креды + отдельный ограниченный MySQL-юзер, прямая запись как переходный костыль в TECH_DEBT)?

---

## Допущения (DRAFT)

1. Код qr (Python, РФ) недоступен — все поля и форматы со стороны qr спроектированы по аналогии с доменом org (`OrderGuestLine` / `MoneySnapshot` / `OrderGuestOption`) и помечены как требующие подтверждения qr (`[QR?]`).
2. Направление интеграции — **push** (qr публикует `OrderPaid`, org консьюмит) — из решений совещания 2026-06-13, не из кода.
3. Деньги в org — **целые рубли** (`Money` в int, `MoneySnapshot.toArray` отдаёт `amount()` как int); предполагается, что `payment.total` нужно привести к этой единице — требует подтверждения qr.
4. «Чек» = существующее PDF/email-подтверждение org (`ProcessUserNotificationOrderPaid`), **НЕ** фискальный 54-ФЗ (решение №7 совещания).
5. Контракт org → BAZA предложен как HTTP-эндпоинт в существующей `auth:sanctum`-группе BAZA (инфраструктура есть на `/user`); добавление нового роута предполагается возможным — требует подтверждения BAZA-стороны.
6. Поля «расширенного билета» для BAZA взяты из реального `ElTicketsModel::$fillable` + `setInBaza*` payload; новые поля (`ticket_kind`, `options[]`, `external_order_id`) — заглушки до согласования. Поле `festival_id` отсутствует в текущем `$fillable` (добавлено миграцией прода) — учитывать.
7. DLQ реализуется на существующем `failed_jobs` (`config/queue.php`, `failed.driver=database-uuids`) — отдельная инфраструктура очередей не вводится, RabbitMQ не используется (в проде его нет).
8. org заводит собственный `order_id` и хранит qr-шный как `external_order_id` (org — мастер билетов, у него своя PK-схема `order_tickets`).
9. Idempotency на входе — новая таблица `inbound_events` / `processed_messages` с UNIQUE `idempotency_key`; на выходе/синхронизации — выигрыш от существующих upsert-проверок `createTickets(whereId exists)` и `setInBaza` (upsert по `uuid`).
10. Поле `event_type` добавлено в конверт (в исходном ТЗ было `{schema_version, trace_id, idempotency_key, occurred_at, source, payload}`) — без него консьюмер не знает, как разбирать payload; помечено как рекомендация к согласованию.
11. HMAC-SHA256 рекомендован вместо подписанного JWT исходя из единственного доверенного издателя (qr) и уже существующего в проекте паттерна общего секрета + `hash_equals` (`AutoPayment`); при появлении нескольких издателей выбор пересматривается.
12. Лимиты (`QR_ORDER_MAX_AMOUNT`, `QR_MAX_GUESTS_PER_ORDER`) — иллюстративные значения (500000 ₽, 50 гостей); фактические пороги задаёт бизнес.
13. SSO/Passport (qr = PKCE-клиент, org = IdP) — **отдельный канал** (пользовательская аутентификация), не пересекается с HMAC-подписью server-to-server событий; переиспользуется существующая спека `.claude/specs/qr-sso-security.md`.
14. Пароль `mysqlBaza` `common404` считается скомпрометированным (закоммичен в репозиторий, предположительно есть в истории git) — рекомендована ротация; точная история git в рамках этой задачи не проверялась.
15. Предполагается reverse-proxy (nginx) перед PHP на проде — поэтому требуется проверка `TrustProxies` перед включением IP-allowlist; конкретная конфигурация прокси не проверялась в коде.

---

*Связанный документ: `FUNCTION_MAP.md` — карта функций qr / org / BAZA (текущее vs целевое, что переезжает/остаётся/дублируется).*