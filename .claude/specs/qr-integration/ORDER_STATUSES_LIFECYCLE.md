# QR → ORG — статусы заказа и жизненный цикл (`create` + `changeStatus`)

> **Статус:** реализовано в коде на ветке `feat/qr-lifecycle-and-questionnaire-approved` (2026-06-19).
> **Адресат:** ИИ-агент / команда витрины **qr.spaceofjoy.ru**.
> **Связь:** `QR_CREATE_API.md` — полный контракт тела заказа; `QR_INGEST_AUTH.md` — защита канала (`X-QR-Token`); `EMAIL_SEND_API.md` — S2S-канал писем (в т.ч. `questionnaire_approved`).
> **Зачем документ:** объяснить qr, **какие статусы слать** и **что каждый из них запускает на org**, плюс восстановленный двухшаговый цикл «создан → оплачен» и письмо «анкета одобрена».

---

## 1. TL;DR

qr передаёт статус заказа в двух местах:

- при приёме — поле **`order_data.status`** в `POST /api/v1/qrOrder/create`;
- при смене — тело **`{ "status": "..." }`** в `POST /api/v1/qrOrder/changeStatus/{order_id}`.

Есть **два режима работы**, выбирай любой:

1. **Двухшаговый** (рекомендуется, если на витрине есть отдельный шаг оплаты):
   `create(status="создан")` → org шлёт письмо **«заказ создан»**, билеты НЕ выпускает.
   Потом `changeStatus(status="оплачен")` → org **выпускает билеты** (PDF + письмо «оплачен» + Baza + Telegram).
2. **Одношаговый** (если заказ приходит уже оплаченным):
   `create(status="оплачен")` → org сразу выпускает билеты. `changeStatus` не нужен.

org защищён от повторной выдачи (см. §5): повторный «оплачен» билеты второй раз не выпустит.

---

## 2. Статусы, которые qr должна присылать

Значения регистронезависимы, лишние пробелы обрезаются. **Шли строки ниже как есть** (русские предпочтительны).

| Статус (что слать) | Синонимы | Что делает org |
|---|---|---|
| **`создан`** | `new`, `created` | Сохраняет заказ. Шлёт письмо **«заказ создан»** (шаблон `orderToCreate`) на `order_data.email`. Билеты **НЕ** выпускает, `issued_at` остаётся пустым. |
| **`оплачен`** | `paid` | Запускает **выдачу билетов один раз**: создание билетов → PDF → письмо **«оплачен»** с билетами → запись в Baza (вход) → Telegram. Набор шагов зависит от `order_data.type_order`. |
| **`отменён`** *(и любой иной)* | — | Только сохраняется и пишется в историю заказа (`status_changed`). **Действий с билетами/отмены пока НЕТ** (отмена через qr — запланирована, ещё не реализована). |

> **Где статус смотреть после:** в админке org (раздел «QR-заказы» → история заказа) каждое изменение пишется в журнал (`created` / `status_changed` / `issued`, актор — `qr`).

---

## 3. Сценарий A — двухшаговый («создан» → «оплачен»)

### Шаг 1. Приём заказа в статусе «создан»

```
POST /api/v1/qrOrder/create
Content-Type: application/json
Accept: application/json
X-QR-Token: <сервисный ключ qr>
```

Тело — полный контракт заказа (см. `QR_CREATE_API.md`), `order_data.status = "создан"`. Пример (реальный):

```json
{
  "order_id": "834b0bb7-63c9-43a0-b43d-7aa72afb41e4",
  "external_order_no": 49,
  "order_data": {
    "email": "buyer@example.com",
    "status": "создан",
    "type_order": "regular",
    "festival": { "id": "46f5a62a-90f6-469b-9fb2-ff59d3b55e2e", "title": "СИСТО ОСЕНЬ" },
    "comment": "qr.spaceofjoy.ru заказ #49"
  },
  "user": { "city": "Не указано", "phone": "99999" },
  "price": { "total": 15200 },
  "guests": [
    { "name": "Иван Иванов", "email": "buyer@example.com", "telegram": "",
      "type_ticket": { "id": "a6dbffb8-9942-44a9-b197-3de8388082c3", "title": "Оргвзнос" },
      "options": [ { "name": "Саженец", "price": 500 } ] }
  ]
}
```

**Что делает org:** сохраняет заказ и шлёт письмо «заказ создан». Для письма org берёт:
- **имя фестиваля** — из `order_data.festival.title` (в примере «СИСТО ОСЕНЬ»);
- **номер заказа** — из `external_order_no` (в примере 49);
- **число позиций** — из длины `guests[]`.

Билеты на этом шаге **не создаются** — заказ ждёт оплаты.

**Ответ 200:** `{ "success": true, "order_id": "<uuid>", "message": "Заказ принят" }`

### Шаг 2. Перевод в «оплачен» → выдача билетов

```
POST /api/v1/qrOrder/changeStatus/834b0bb7-63c9-43a0-b43d-7aa72afb41e4
Content-Type: application/json
Accept: application/json
X-QR-Token: <сервисный ключ qr>

{ "status": "оплачен" }
```

**Что делает org:** меняет статус, пишет историю и **запускает выдачу билетов** (PDF + письмо «оплачен» с билетами + Baza + Telegram). Выдача — один раз (см. §5).

---

## 4. Сценарий B — одношаговый (сразу «оплачен»)

Если шага «создан» на витрине нет — пришли заказ сразу оплаченным, и `changeStatus` не нужен:

```
POST /api/v1/qrOrder/create   (X-QR-Token)
{ ...тот же контракт..., "order_data": { ..., "status": "оплачен" } }
```

org немедленно запустит выдачу билетов. Письма «заказ создан» при этом **не будет** (заказ сразу оплачен).

---

## 5. Эндпоинт `changeStatus` — контракт

```
POST /api/v1/qrOrder/changeStatus/{order_id}
```

| | |
|---|---|
| **Метод** | `POST` |
| **Путь (staging)** | `https://api.staging.spaceofjoy.ru/api/v1/qrOrder/changeStatus/{order_id}` |
| **Путь (prod)** | `https://api.spaceofjoy.ru/api/v1/qrOrder/changeStatus/{order_id}` |
| **Аутентификация** | `X-QR-Token` (тот же ключ, что у `qrOrder/create`, middleware `qr.ingest`) |
| **Тело** | `{ "status": "оплачен" }` — `status` обязателен |

### Идемпотентность выдачи

Выдача билетов привязана к отметке `issued_at`: первый «оплачен» её ставит **до** запуска, поэтому **повторный «оплачен»** (ретрай qr, дубль вебхука) билеты второй раз **не выпустит**. Слать «оплачен» повторно безопасно.

### Ответы

| Код | Тело | Значение | Что делать на стороне qr |
|---|---|---|---|
| `200` | `{ "success": true, "item": { ... }, "message": "Статус обновлён" }` | статус изменён (при «оплачен» — выдача запущена) | успех |
| `422` | `{ "success": false, "message": "Не передан status" }` | пустой `status` | исправить тело |
| `404` | `{ "success": false, "message": "Заказ не найден" }` | нет заказа с таким `order_id` | сначала `qrOrder/create` |
| `401` | `{ "success": false, "message": "Доступ запрещён: ..." }` | нет/неверный `X-QR-Token` | **не ретраить** — проверить ключ |

> `order_id` в пути = тот же `order_id`, что слался при `create` (id заказа qr == id заказа org).

---

## 6. Письмо «анкета одобрена» (`questionnaire_approved`)

В каталог событий писем org добавлено новое (16-е) событие:

| `event` | Дефолтный шаблон | Метка |
|---|---|---|
| `questionnaire_approved` | `questionnaireApproved` | Анкета одобрена |

- **Когда шлётся само:** при одобрении анкеты администратором в org гостю автоматически уходит письмо «анкета одобрена» (org делает это сам, qr ничего слать не нужно).
- **Если витрина хочет инициировать это письмо сама** (например, анкета одобряется на стороне qr) — используй S2S-канал писем `POST /api/v1/emailNotification/send` (тот же `X-QR-Token`) с `event = "questionnaire_approved"`, полем `email` и `vars` (как минимум `link`). Рекомендуется передавать `external_id` для идемпотентности. Полный контракт — в **`EMAIL_SEND_API.md`**.

---

## 7. Примеры

### Python (requests) — двухшаговый цикл

```python
import os, requests

ORG = "https://api.spaceofjoy.ru/api/v1"
HEADERS = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-QR-Token": os.environ["ORG_QR_INGEST_TOKEN"],
}

def create_order(order: dict):
    # order_data.status = "создан" → org пришлёт письмо «заказ создан», билеты не выпустит
    r = requests.post(f"{ORG}/qrOrder/create", json=order, headers=HEADERS, timeout=15)
    r.raise_for_status()
    return r.json()["order_id"]

def mark_paid(order_id: str):
    # перевод в «оплачен» → org выпустит билеты (PDF/письма) — один раз
    r = requests.post(f"{ORG}/qrOrder/changeStatus/{order_id}",
                       json={"status": "оплачен"}, headers=HEADERS, timeout=15)
    r.raise_for_status()
    return r.json()
```

### curl — смена статуса

```bash
curl -X POST "https://api.spaceofjoy.ru/api/v1/qrOrder/changeStatus/834b0bb7-63c9-43a0-b43d-7aa72afb41e4" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -H "X-QR-Token: $ORG_QR_INGEST_TOKEN" \
  -d '{"status":"оплачен"}'
```

---

## 8. Провенанс (файлы кода org)

| Что | Файл |
|---|---|
| Приём + смена статуса (логика статусов) | `Backend/src/QrOrder/Application/QrOrderApplication.php` (`create`, `changeStatus`, `isPaid`, `isCreated`, `sendCreatedEmail`) |
| Контроллер | `Backend/app/Http/Controllers/QrOrder/QrOrderController.php` (`create`, `changeStatus`) |
| Роуты (`create` + восстановленный `changeStatus`) | `Backend/routes/qrOrder.php` |
| Каталог событий писем (+ `questionnaire_approved`) | `Backend/src/EmailDelivery/Domain/EmailEvent.php` |
| Письмо «заказ создан» | `Backend/app/Mail/OrderToCreate.php` (шаблон `orderToCreate`) |
| Защита канала (`X-QR-Token`) | `QR_INGEST_AUTH.md` |
| Контракт тела заказа | `QR_CREATE_API.md` |
| S2S-канал писем | `EMAIL_SEND_API.md` |
