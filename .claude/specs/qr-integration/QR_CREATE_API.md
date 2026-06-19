# QR → ORG — контракт приёма заказа (`POST /api/v1/qrOrder/create`)

> **Статус:** актуально на 2026-06-16. Отражает **реально реализованный код** (ветка `feat/template-bindings`, коммиты `239ca17d`/`d8c6a0b5`).
> **Связь с другими документами:** `CONTRACT_RFC_v0.md` описывал ранний (переусложнённый: отдельный `order_id` у org, outbox, ассемблер) дизайн — он **устарел** для `/create`. Источник истины по приёму заказа — **этот документ**. `FUNCTION_MAP.md` — карта функций.
> **Verified:** контракт извлечён из кода и адверсариально сверён (точность полей / парковка / полнота) + подтверждён живым e2e на стенде (оплаченный заказ → билеты + письмо в Mailpit).
>
> **🔄 ОБНОВЛЕНИЕ 2026-06-20 (расширенный контракт `QR_FULL_EXAMPLE.md`):** приём `qrOrder/create` расширен под полный контракт qr — секция **`buyer{}`** (fallback на legacy `user{}`), **`payment.amount_total`** (fallback `price.total`), **`order_data.festival.{id,title}`**. Новые проекционные колонки `qr_orders.buyer_fio`, `festival_title` (миграция `2026_06_20_120000`). Приём по-прежнему **не ругается на новые поля** и хранит весь JSON `as-is`; выпуск **уже** читает per-guest `guests[].type_ticket.id` + `guests[].name`. **ПДн расширенного контракта** (`guests[].child{}` — мед.данные ребёнка, `payment.method_details.card_number`) в колонки **НЕ** проецируются — только в `payload` (минимизация 152-ФЗ/PCI; рекомендация: qr минимизирует на источнике). Гард: `> QrOrderDto::MAX_GUESTS` (1000) гостей → 422. **`changeStatus` СУЩЕСТВУЕТ** — разделы ниже, утверждающие обратное, УСТАРЕЛИ.

---

## 1. Модель интеграции

| | |
|---|---|
| **qr.spaceofjoy.ru** | Витрина: каталог для покупателя, корзина, **оплата**, расчёт цены/скидок/опций. |
| **org** (этот сервис) | Выпуск билетов, генерация PDF/QR, письма, запись в Baza (`el_tickets`/касса). |
| **Поток** | qr формирует и оплачивает заказ → шлёт его в org **уже в финальном статусе**. При `status="оплачен"` org **сразу выпускает** билеты. |
| **Идентификатор** | `order_id` — **общий**: id заказа в qr **РАВЕН** id заказа в org. Маппинга нет. |
| **Источник истины** | Весь JSON-контракт сохраняется **as-is** в `qr_orders.payload` (Eloquent cast `array`). Часть полей дополнительно **денормализуется в колонки** `qr_orders` — это проекция **только для фильтров админки** (по ним не строится бизнес-логика). |
| **Расчёт цены** | Делает **qr**. org цену не пересчитывает — принимает `price.total` как есть. |

---

## 2. Endpoint

```
POST /api/v1/qrOrder/create
```

| Параметр | Значение |
|---|---|
| **Метод** | `POST` |
| **Префикс** | `/api` добавляет `RouteServiceProvider` (группа `api`), внутри файла — `v1/qrOrder` + `/create`. |
| **Полный путь (staging)** | `https://api.staging.spaceofjoy.ru/api/v1/qrOrder/create` |
| **Полный путь (prod)** | `https://api.spaceofjoy.ru/api/v1/qrOrder/create` |
| **Тело** | JSON (см. §5) |

### Заголовки

| Заголовок | Обязателен | Зачем |
|---|:---:|---|
| `Content-Type: application/json` | ✅ | контроллер читает тело через `$request->toArray()` |
| `Accept: application/json` | ✅ (рекоменд.) | чтобы ошибки приходили JSON-ом, а не HTML |
| `X-QR-Token` | ✅ | сервисный ключ канала qr (см. §3 + `QR_INGEST_AUTH.md`). Нет/неверный → `401` |

---

## 3. Аутентификация

**Канал закрыт сервисным ключом qr.** На `/create` висит middleware **`qr.ingest`** (`App\Http\Middleware\QrIngestAuth`): запрос обязан нести заголовок **`X-QR-Token: <ключ>`**, который сверяется со списком ключей `config('services.qr_ingest.tokens')` (env `QR_INGEST_TOKENS`) через `hash_equals`. Нет/неверный ключ → **`401`**, заказ не создаётся. Список ключей через запятую → ротация без простоя. Пустой список = канал закрыт (безопасный дефолт).

Подробная настройка (генерация ключа, деплой на org, инструкция для qr-стороны, ротация, опц. allowlist IP) — **`QR_INGEST_AUTH.md`**.

> ⚠️ **Безопасность.** Эндпоинт **выпускает билеты** (письма, запись в Baza) и **хранит ПДн**. Поверх ключа рекомендуется второй барьер — allowlist IP qr-сервера на nginx (если IP статичный). Транспорт уже защищён TLS (`api.spaceofjoy.ru`).
>
> 📌 Историческая заметка: ранее канал был временно публичным (коммит `d8c6a0b5`), ещё раньше — на Sanctum-токене `qr:ingest`. Артефакт `QrIssueServiceToken` (artisan `qr:issue-token`) и роль `qr_service` от Sanctum-версии в коде **остались, но не используются** — кандидаты на чистку.

Остальные эндпоинты группы (для контекста, **read-only для админки org**, требуют JWT+admin): `POST /getList`, `POST /getStats`, `GET /getItem/{id}`, `GET /getHistory/{id}`.

> Маршрут **смены статуса** `POST /api/v1/qrOrder/changeStatus/{id}` (S2S, заголовок `X-QR-Token`, middleware `qr.ingest`) **СУЩЕСТВУЕТ**: двухшаговый цикл «создан» → «оплачен»; при переходе в «оплачен» (`issued_at == null`) запускается выдача билетов — один раз. Выдачу можно триггерить и через `/create` сразу с оплаченным статусом (одношаговый приём). *(Прежнее утверждение «changeStatus нет» устарело — см. топ-ноту.)*

---

## 4. Идемпотентность

Гарантируется по `order_id`:

1. На входе `create()` вызывает `existsById(order_id)` — если заказ с таким id уже есть, сразу `return true` (HTTP `200`) **без повторной записи и без повторной выдачи**.
2. Перед постановкой задачи выдачи ставится `issued_at` (`markIssued`) — защита от двойной выдачи в гонке/ретраях.

**Вывод:** повторный `POST /create` с тем же `order_id` безопасен — вернёт `200 success` без побочных эффектов. qr может ретраить сетевые сбои.

---

## 5. Тело запроса — полная схема

### Обозначения
- **Обяз. (422)** — поле, чьё отсутствие/невалидность даёт `422` уже на приёме.
- **Для выдачи** — поле, без которого приём пройдёт (`200`), но билет **не выпустится корректно**.
- **Проекция** — дублируется в колонку `qr_orders.*` (для фильтров). Остальное — **только в `payload`**.

### 5.1. Верхний уровень

| Поле | Тип | Обяз. (422) | Проекция | Описание |
|---|---|:---:|---|---|
| `order_id` | uuid | ✅ | `id` | id заказа (== id в org). Не string/пустой → `422`. Невалидный uuid → `422`. |
| `order_data` | object | ✅* | — | Контейнер данных заказа. *Сам по себе обрабатывается мягко (нет → `[]`), но **фактически обязателен** через дочерний `order_data.email`. |
| `user` | object | ❌ | — | Контейнер данных заказчика (нет → `[]`). |
| `price` | object | ❌ | — | Контейнер цены (нет → `[]`). |
| `guests` | array<object> | ❌¹ | — (весь в payload) | Строки заказа (гости/билеты/машины). |

¹ формально не `required`, но без `guests[]` билеты не создаются (лог `create_tickets.no_guests`).

### 5.2. `order_data.*`

| Поле | Тип | Обяз. | Проекция | Описание |
|---|---|:---:|---|---|
| `order_data.email` | string | ✅ (422) | `email` | Куда слать билеты. Не string/пустой → `422`. Fallback для гостей без email. |
| `order_data.status` | string | ❌ (дефолт `"создан"`) | `status` | Финальный статус заказа. **`"оплачен"`/`"paid"` → немедленная выдача** (см. §7). Иное → только сохранение. |
| `order_data.type_order` | string | ❌ | `type_order` | Тип заказа: `regular`/`friendly`/`list`/`live` (см. §6). Парковка = `regular`. Неизвестное → `regular`. |
| `order_data.festival` | object `{id,title}` | ❌ при приёме / ✅ для выдачи | — | Фестиваль каталога org. |
| `order_data.festival.id` | uuid | для выдачи ✅ | `festival_id` | Источник `festival_id` (с fallback, см. ниже). Невалидный uuid → `422`. При выдаче `null` → `IssueOrderJob` падает: `«Выдача невозможна: у заказа нет festival_id»` (live: `«Выдача live невозможна…»`). |
| `order_data.festival.title` | string | ❌ | — (payload) | Только отображение, шагами выдачи не читается. |
| `order_data.festival_id` | uuid | ❌ | `festival_id` | **Альтернативный** источник `festival_id` (fallback). |
| `order_data.comment` | string | ❌ | — (payload) | Печатается в письме/PDF (`TicketResponse.comment`). |
| `order_data.location` | object `{id,…}` | только `list` | — (payload) | Локация (сцена) для заказа-списка. |
| `order_data.location.id` | uuid | только `list` | — (payload) | → `TicketResponse.location_id` (определяет запись в `spisok_tickets`). |
| `order_data.curator` | object `{id,email,name}` | только `list` | — (payload) | Куратор заказа-списка. |
| `order_data.curator.id` / `.email` / `.name` | uuid / string / string | только `list` | — (payload) | → `TicketResponse.curator_*`. |
| `order_data.project` | string | только `list` | — (payload) | → `TicketResponse.project`. |

> **Порядок выбора `festival_id`** (через `??` — побеждает первый **не-`null`**): `order_data.festival.id` → `order_data.festival_id` → `festival_id` (top-level) → `null`.
> ⚠️ **Тонкость `??`:** пустая строка `""` или `0` в `festival.id` — это **не `null`**, поэтому она «выигрывает» в `??` и **перебивает fallback**; затем `empty()` обнуляет её в `null`, и `order_data.festival_id` уже **не подхватится** → `festival_id=null` (выдача упадёт). Правило для qr: передавай `festival.id` **валидным uuid**, либо **опускай весь `order_data.festival`** (тогда сработает fallback на `festival_id`). **Не слать пустую строку.**

### 5.3. `user.*`

| Поле | Тип | Обяз. | Проекция | Описание |
|---|---|:---:|---|---|
| `user.city` | string | ❌ | `city` | Город заказчика → `TicketResponse.city`. |
| `user.phone` | string | ❌ | `phone` | Телефон заказчика → `TicketResponse.phone`. |

### 5.4. `price.*`

| Поле | Тип | Обяз. | Проекция | Описание |
|---|---|:---:|---|---|
| `price.total` | integer (₽) | ❌ (дефолт `0`) | `total_price` | Итоговая сумма заказа, **целые рубли** (qr — мастер цены). Только для статистики/дашборда; на выдачу не влияет. |

### 5.5. `guests[].*` (на каждый билет/гостя/машину)

> Весь `guests[]` хранится **только в payload** (в колонки не проецируется). Потребляется шагами выдачи.

| Поле | Тип | Обяз. | Описание / кем читается |
|---|---|:---:|---|
| `guests[].name` | string | ❌ (дефолт `""`) | ФИО гостя → `TicketResponse.name`/`TicketDto.name`, печатается в PDF/письме. **Для парковки — строка авто** `"госномер / марка / водитель"`. |
| `guests[].email` | string | ❌ | Email билета/гостя; пусто → `order_data.email`. Для парковки — email водителя. |
| `guests[].type_ticket` | object `{id,title}` | ❌² | Тип билета гостя. |
| `guests[].type_ticket.id` | uuid | ❌² | **Ключ выбора шаблонов** (`findTemplate(festival_id, id)` → `ticket_type_festival.{pdf,email}`). → `TicketResponse.type_ticket_id`. `null` → шаблон не ищется (PDF=`pdf`, email=дефолт), **билет не пишется в Baza `el_tickets`**. |
| `guests[].type_ticket.title` | string | ❌ | Название → `TicketResponse.type_ticket` (только отображение в PDF/письме). |
| `guests[].telegram` | string | ❌ (мягко) | Ник для уведомления в Telegram-бот. Срезается `@`/пробелы; пусто → пропуск (`send_telegram.skip_empty`). Читается **только** `SendTelegramStep`. |
| `guests[].number` | integer | только `live` | Номер живого билета → связка с `live_tickets` (`LinkLiveStep`). Для regular/parking **не читается**. |
| `guests[].options` | array | ❌ | **Сквозной pass-through** — org **не читает** (опции уже учтены qr в `price.total`). Хранится в payload «как есть». |

² без `type_ticket.id` билет создастся в `tickets`, но не попадёт в каталог/Baza — для реального билета указывать обязательно.

---

## 6. Типы заказа (`type_order`) и логика выдачи

`type_order` (значение `order_data.type_order`) выбирает **стратегию выдачи**. Нормализация: `mb_strtolower(trim())`. Неизвестное/пустое → **`regular`**.

| `type_order` | Стратегия → конвейер шагов | Письмо (Mailable) | Куда пишется билет |
|---|---|---|---|
| `regular` | CreateTickets → SendOrderEmail → PushToBaza → SendTelegram | `OrderToPaid` | `tickets` + Baza `el_tickets` |
| `friendly` | CreateTickets → SendOrderEmail → PushToBaza → SendTelegram | `OrderToPaidFriendly` (без ссылки на ЛК) | `tickets` + `el_tickets` |
| `list` | CreateTickets → **SendListEmail** → PushToBaza → SendTelegram | `OrderListApproved` (blade из `Location.email_template`) | `tickets` + **`spisok_tickets`** (`type_ticket_id` не нужен) |
| `live` | **CreateLiveTickets** → **SendLiveEmail** → **LinkLive** → SendTelegram | `OrderToLiveTicketIssued` | связка с **`live_tickets`** по `guests[].number` |

Дополнительно к базовым полям:
- **`list`** требует `order_data.curator.{id,email,name}` + `order_data.location.id` (+ опц. `project`).
- **`live`** требует `guests[].number` у каждого гостя; PDF/QR (`ProcessCreatingQRCode`) и `el_tickets` **не задействуются**.
- **`type_order` задаётся на заказ целиком** — все гости выпускаются одной стратегией (нельзя смешать live и не-live в одном заказе).

### 6.x. Парковка (поверх `type_order = "regular"`)

Парковка — **не отдельный `type_order`**. Это свойство **типа билета** (`ticket_type.is_parking = 1`), которое выражается только через **шаблоны** этого типа:

| Аспект | Как |
|---|---|
| `type_order` | **`"regular"`** (идёт по `RegularIssuanceStrategy`) |
| Распознавание | В коде выдачи `is_parking` **нигде не проверяется** — всё через выбор blade-шаблонов по `type_ticket.id` |
| PDF | `ticket_type_festival.pdf` парковочного типа = `TypeAvtoPdf1` (пропуск на авто) → `TicketResponse.festivalView` → `Pdf::loadView('TypeAvtoPdf1', …)` |
| Письмо | `ticket_type_festival.email` парковочного типа = `orderToPaidAvto` → `TicketResponse.emailView`; внешний Mailable `OrderToPaid`, внутри рендерится blade `orderToPaidAvto` |
| Данные авто | строка в `guests[].name`: `"госномер / марка / ФИО водителя"` — печатается на пропуске как «имя», без спец-обработки |
| Email водителя | `guests[].email` |
| Несколько машин | несколько `guests[]` (по пропуску на каждую) |
| Владелец/ФИО заказчика | **не передаётся** — заказчик опознаётся по `order_data.email`/`user` |

> Значения `TypeAvtoPdf1`/`orderToPaidAvto` **не хардкодятся** в коде — приходят из строки `ticket_type_festival` конкретного фестиваля. В запросе их указывать не нужно: достаточно `type_ticket.id` парковочного типа.

---

## 7. Конвейер выдачи (`IssueOrderJob`)

### 7.1. Триггер (`QrOrderApplication::create`)

```
1. existsById(order_id) → true  ⇒  return (идемпотентность, см. §4)
2. repository->create(dto)
3. history: QrOrderHistoryEvent('created'), ActorType::QR
4. isPaid(status)?   // mb_strtolower(trim(status)) ∈ {'оплачен','paid'}
     да → markIssued(order_id, now())   // ДО dispatch
          IssueOrderJob::dispatch(order_id)   // асинхронно, вне HTTP
     нет → ничего (заказ сохранён, выдачи нет)
```

`IssueOrderJob` берёт стратегию по `type_order`, прогоняет шаги, по успеху пишет историю `issued` (actor `qr`).

### 7.2. Шаблоны и ловушка `.black.php`

- `CreateTicketsStep` → `findTemplate(festival_id, type_ticket.id)` читает `ticket_type_festival` по `(festival_id + ticket_type_id)` → `{pdf, email}`.
- PDF: `Pdf::loadView(festivalView ?? 'pdf', …)`. Email: `OrderToPaid` рендерит `emailView` (или дефолт `orderToPaid`).
- ⚠️ **Ловушка:** если в колонке `pdf`/`email` лежит невалидное имя вью (например мусорный суффикс `TypeTicketPdf1.black.php` вместо `TypeTicketPdf1`) — `View not found` → `IssueOrderJob` **падает**, билеты в `tickets` создаются, но **PDF/письмо не уходят**, `issued_at` сбрасывается. На стенде такое находили и чинили; **проверяйте каталог** (`ticket_type_festival.pdf`/`email` = имена существующих blade без `.blade.php`).

### 7.3. Идемпотентность и откат

- `ticketId` детерминирован (`QrTicketId::forGuest(order_id, index)`) — повторный прогон не плодит дубли, PDF/QR ставится только для новых билетов.
- При **окончательном** сбое выдачи `IssueOrderJob::failed()` (`tries=1`, без авто-ретраев) вызывает `clearIssued()` → `issued_at = null` (семантика: `issued_at` стоит **только** при фактическом успехе).
- ⚠️ Но из-за `existsById` повторный `/create` уже **не перевыпустит** заказ, а HTTP-эндпоинта `changeStatus` нет → **повторная выдача упавшего заказа по HTTP сейчас недостижима** (только внутренний вызов). Если выдача упала — чинить причину (шаблон/данные) и пересоздавать заказ с новым `order_id`, либо триггерить выдачу внутренне.

---

## 8. Ответы

| Код | Когда | Тело |
|---:|---|---|
| **200** | заказ принят (вкл. повторный с тем же `order_id`) | `{ "success": true, "order_id": "<uuid>", "message": "Заказ принят" }` |
| **422** | `InvalidArgumentException` из `fromQrContract`: нет `order_id` / нет `order_data.email` / невалидный uuid в `order_id`/`festival.id` | `{ "success": false, "message": "<текст исключения>" }` |
| **500** | прочий `Throwable` (ошибка БД при `create`/`markIssued`, постановки задачи) | `{ "success": false, "message": "<текст исключения>" }` ⚠️ утечка деталей ошибки (как в `order/create`, техдолг) |

---

## 9. Поведение и краевые случаи

| Случай | Поведение |
|---|---|
| `status = "оплачен"`/`"paid"` | заказ сохранён + история `created` + **выдача** (`markIssued` + `IssueOrderJob`) → `issued`. |
| `status ≠ оплачен` (напр. дефолт `"создан"`) | заказ сохранён + история `created`, **выдачи нет**. Ответ всё равно `200`. **Позже выпустить по HTTP нельзя** (нет `changeStatus`) → шли сразу `"оплачен"`. |
| повтор `order_id` | `200` без побочных эффектов (идемпотентно). |
| нет `festival_id` | приём пройдёт (`200`), но выдача упадёт (`«Выдача невозможна: у заказа нет festival_id»`). |
| нет `guests[]` | приём `200`, билеты не создаются (лог `no_guests`). |
| `guests[].options` | сохраняются в payload, **org игнорирует** (цена уже в `price.total`). |
| `guests[].telegram` пустой | уведомление в бот пропускается (мягко). |
| live-заказ без `guests[].number` | лог warning, связка с `live_tickets` не делается. |

---

## 10. Примеры

### 10.1. Обычный заказ (regular), 1 гость
```json
{
  "order_id": "0c2f1e7a-3b44-4c9a-9f10-1a2b3c4d5e6f",
  "order_data": {
    "email": "buyer@example.com", "status": "оплачен", "type_order": "regular",
    "festival": { "id": "9d679bcf-b438-4ddb-ac04-023fa9bff4b8", "title": "Solar Systo Togathering" }
  },
  "user": { "city": "Москва", "phone": "+79991234567" },
  "price": { "total": 4000 },
  "guests": [
    { "name": "Иванов Иван", "email": "ivan@example.com", "telegram": "@ivan",
      "type_ticket": { "id": "<ticket_type_id>", "title": "Оргвзнос" }, "options": [] }
  ]
}
```

### 10.2. Парковка (regular + парковочный тип), 2 машины
```json
{
  "order_id": "ddaff198-8417-4142-a11b-3086ec074af8",
  "order_data": {
    "email": "buyer@example.com", "status": "оплачен", "type_order": "regular",
    "festival": { "id": "9d679bcf-b438-4ddb-ac04-023fa9bff4b8", "title": "Solar Systo Togathering" }
  },
  "user": { "city": "Москва", "phone": "+79991234567" },
  "price": { "total": 2000 },
  "guests": [
    { "name": "А123АА777 / Toyota Camry / Иванов И.И.", "email": "driver1@example.com",
      "type_ticket": { "id": "<парковочный ticket_type_id>", "title": "Парковка" }, "options": [] },
    { "name": "В456ВВ199 / Lada Vesta / Петров П.П.", "email": "driver2@example.com",
      "type_ticket": { "id": "<парковочный ticket_type_id>", "title": "Парковка" }, "options": [] }
  ]
}
```
> Staging-id парковочного типа: `50c31a16-02e7-496b-949b-cbff68d2b24d` (на проде будет другой).

### 10.3. Friendly
Как regular, но `"type_order": "friendly"` → письмо `OrderToPaidFriendly`.

### 10.4. Заказ-список (list)
```json
{
  "order_id": "<uuid>",
  "order_data": {
    "email": "recipient@example.com", "status": "оплачен", "type_order": "list",
    "festival": { "id": "<festival_id>", "title": "…" },
    "curator":  { "id": "<uuid>", "email": "curator@example.com", "name": "Куратор" },
    "location": { "id": "<location_id>", "name": "Сцена А" },
    "project":  "Смена 1"
  },
  "guests": [ { "name": "Гость Списка", "email": "g@example.com",
               "type_ticket": { "id": "<ticket_type_id>", "title": "…" } } ]
}
```

### 10.5. Живой билет (live)
```json
{
  "order_id": "<uuid>",
  "order_data": {
    "email": "buyer@example.com", "status": "оплачен", "type_order": "live",
    "festival": { "id": "<festival_id>", "title": "…" }
  },
  "guests": [ { "name": "Иванов Иван", "email": "ivan@example.com", "number": 1234,
               "type_ticket": { "id": "<live ticket_type_id>", "title": "Живой билет" } } ]
}
```

### 10.6. curl (с сервисным ключом)
```bash
curl -X POST https://api.staging.spaceofjoy.ru/api/v1/qrOrder/create \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -H "X-QR-Token: <ключ>" \
  -d @order.json
```

---

## 11. Безопасность и открытые вопросы

| # | Вопрос | Статус |
|---|---|---|
| 1 | `/create` выпускает билеты + хранит ПДн | ✅ закрыт сервисным ключом `X-QR-Token` (middleware `qr.ingest`). Опц. усилить allowlist IP qr на nginx (см. `QR_INGEST_AUTH.md §A.5`) |
| 2 | `500` возвращал текст исключения (`getMessage`) | ✅ исправлено — наружу generic-сообщение, детали в `report()` (лог/Sentry) |
| 3 | `QrIssueServiceToken` + роль `qr_service` от старого Sanctum | остались в коде, не используются — **кандидаты на чистку** (отдельным шагом) |
| 4 | Нет HTTP-эндпоинта смены статуса (`changeStatus`) | упавшую/неоплаченную выдачу по HTTP не перезапустить; слать заказ сразу `"оплачен"` |
| 5 | Битые имена шаблонов в `ticket_type_festival` (`.black.php`) роняют выдачу | проверять каталог; на стенде починено, **прод проверить** |

---

## 12. Провенанс (файлы кода)

| Слой | Файл |
|---|---|
| Роут | `Backend/routes/qrOrder.php` |
| Контроллер | `Backend/app/Http/Controllers/QrOrder/QrOrderController.php` (`create`) |
| Приём + триггер выдачи | `Backend/src/QrOrder/Application/QrOrderApplication.php` (`create`) |
| Парсинг контракта | `Backend/src/QrOrder/Dto/QrOrderDto.php` (`fromQrContract`) |
| Тип заказа | `Backend/src/QrOrder/Domain/ValueObject/TypeOrder.php` |
| Стратегии выдачи | `Backend/src/QrOrder/Application/Issuance/Strategy/*.php` |
| Шаги конвейера | `Backend/src/QrOrder/Application/Step/*.php` |
| Выбор письма | `Backend/src/QrOrder/Application/Support/OrderEmailResolver.php` |

> При изменении любого из этих файлов — **обновить этот документ**.
