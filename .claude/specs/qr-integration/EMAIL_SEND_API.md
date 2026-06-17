# QR → ORG — контракт отправки письма (`POST /api/v1/emailNotification/send`)

> **Статус:** актуально на 2026-06-17. Отражает **реально реализованный код** (ветка `feat/qr-order-pipeline-view`, PR #77, Ф4 системы писем).
> **Связь:** `QR_INGEST_AUTH.md` — защита канала (тот же `X-QR-Token`). `QR_CREATE_API.md` — приём заказа (письма заказа org шлёт **сам**, см. §1 ⚠️). Этот документ — **про отправку НЕ-заказного письма по запросу витрины**.
> **Verified:** контракт извлечён из кода (`EmailNotificationController`, `MailDispatcher`, `EmailEvent`, `GenericTemplatedMail`) + e2e на стенде.

---

## 1. Когда использовать этот канал

org умеет рендерить письмо по шаблону (Mustache из БД, с fallback на blade), **отслеживать его доставку** (таблица `email_messages`, статусы `queued→sending→sent→…`) и слать асинхронно. Витрина qr может попросить org отправить письмо, **не привязанное к выпуску билета**: регистрация аккаунта, сброс пароля, приглашение, ссылка на анкету и т.п.

| | |
|---|---|
| **qr.spaceofjoy.ru** | Инициирует не-заказное письмо (есть событие + получатель + данные для шаблона). |
| **org** (этот сервис) | Выбирает шаблон по событию, рендерит, шлёт, **трекает путь** (видно в админке «Доставка писем»). |
| **Шаблоны** | Владеет **org**. qr передаёт только **данные** (`vars`), вёрстку не присылает. |
| **Отправка** | Асинхронная (`SendEmailJob`, ретрай `tries=3`). Ответ `200` = письмо **принято в очередь**, не «доставлено». |

> ⚠️ **НЕ дублировать письма заказа.** Письма о заказе (оплата, отмена, выдача билета, заказ-список) org формирует **сам** при приёме заказа через `POST /api/v1/qrOrder/create` (пайплайн выдачи) и при сменах статуса. **Не** слать для них `emailNotification/send` — будет дубль. Этот канал — для писем, которых в заказном пайплайне нет (см. §6: рекомендуемые события — `user_registered`, `password_reset`, `invite`, `questionnaire`).

---

## 2. Endpoint

```
POST /api/v1/emailNotification/send
```

| Параметр | Значение |
|---|---|
| **Метод** | `POST` |
| **Префикс** | `/api` добавляет `RouteServiceProvider` (группа `api`); внутри — `v1/emailNotification/send`. |
| **Полный путь (staging)** | `https://api.staging.spaceofjoy.ru/api/v1/emailNotification/send` |
| **Полный путь (prod)** | `https://api.spaceofjoy.ru/api/v1/emailNotification/send` |
| **Тело** | JSON (см. §5) |

### Заголовки

| Заголовок | Обязателен | Зачем |
|---|:---:|---|
| `Content-Type: application/json` | ✅ | контроллер читает тело через `$request->toArray()` |
| `Accept: application/json` | ✅ (реком.) | чтобы ошибки приходили JSON-ом |
| `X-QR-Token` | ✅ | сервисный ключ канала qr (тот же, что у `qrOrder/create`). Нет/неверный → `401` |

---

## 3. Аутентификация

Канал закрыт **тем же** сервисным ключом, что и приём заказов: middleware **`qr.ingest`** (`App\Http\Middleware\QrIngestAuth`) сверяет `X-QR-Token` со списком `config('services.qr_ingest.tokens')` (env `QR_INGEST_TOKENS`) через `hash_equals`. Нет/неверный ключ → **`401`**, письмо не создаётся. Тот же ключ, что для `qrOrder/create` — отдельный выдавать не нужно. Детали (генерация, ротация, allowlist IP) — **`QR_INGEST_AUTH.md`**.

---

## 4. Идемпотентность

Опциональна, по полю **`external_id`**:

- Если qr передаёт `external_id`, и письмо с таким `external_id` уже принято — повтор **не создаёт дубль**, возвращает `200 { "success": true, "message": "Уже принято ранее (идемпотентно)" }`.
- Без `external_id` идемпотентности нет — каждый запрос создаёт новое письмо.

**Рекомендация:** всегда слать стабильный `external_id` (например, `pwd-reset:<user_id>:<timestamp>` или uuid операции на стороне qr) — тогда сетевые сбои/ретраи безопасны.

---

## 5. Тело запроса — схема

| Поле | Тип | Обяз. | Описание |
|---|---|:---:|---|
| `event` | string | ✅ (422) | Код события из каталога `EmailEvent` (см. §6). Неизвестное → `422`. Определяет шаблон (slug). |
| `email` | string | ✅ (422) | Email получателя. Пустой → `422`. |
| `vars` | object | ❌ | Данные для подстановки в шаблон (плейсхолдеры Mustache). Какие именно — зависят от события/шаблона (см. §6). Лишние ключи игнорируются. |
| `subject` | string | ❌ | Тема письма. Нет → берётся `vars.subject` → иначе дефолт `"Уведомление"`. |
| `festival_id` | uuid | ❌ | Только чтобы уточнить **привязку шаблона** по фестивалю (если для события есть разные шаблоны на фестивали). Обычно для auth-писем не нужно. |
| `order_type` | string | ❌ | `regular`/`friendly`/`list`/`live` — тоже только для уточнения привязки шаблона. Обычно опускается. |
| `ticket_type_id` | uuid | ❌ | Аналогично — уточнение привязки по типу билета. Обычно опускается. |
| `aggregate_id` | uuid | ❌ | Связать письмо с сущностью org (например, id заказа qr) — тогда письмо видно в «весь путь заказа» (`aggregate_type=qr_order`). |
| `external_id` | string | ❌ (реком.) | Ключ идемпотентности (см. §4). |

> **Про `vars`:** тело письма — это Mustache-шаблон в БД org (или blade-fallback). qr обязан передать те плейсхолдеры, которые шаблон ожидает (см. §6). org НЕ присылает список плейсхолдеров в ответе — состав согласуется по этому документу / каталогу плейсхолдеров в админке org (`GET /api/v1/template/variables/{slug}`).
>
> **152-ФЗ:** не передавай в `vars` лишние ПДн — только то, что нужно для письма. `recipient` (email) хранится в `email_messages` (admin-only чтение).

---

## 6. Каталог событий (`EmailEvent`) и ожидаемые `vars`

15 событий. Дефолтный slug = шаблон по умолчанию (org может переопределить привязкой). **Для этого канала** витрине нужны прежде всего **не-заказные** события (верхний блок).

### Рекомендованы для `emailNotification/send`

| `event` | Когда слать | Дефолтный шаблон | Ожидаемые `vars` |
|---|---|---|---|
| `user_registered` | Создан аккаунт на витрине | `newUser` | `login`, `password` |
| `password_reset` | Запрошен сброс пароля | `passwordResets` | `link` |
| `invite` | Приглашение пользователю | `invate` | `link` |
| `questionnaire` | Ссылка на анкету гостя | `questionnaire` | `link` |

### Заказные/листовые события — обычно НЕ через этот канал

> Их org шлёт **сам** при выдаче/смене статуса (см. §1 ⚠️). Перечислены для полноты каталога (приём не запрещён, но обычно не нужен).

| `event` | Дефолтный шаблон |
|---|---|
| `order_created` | `orderToCreate` |
| `order_paid` | `orderToPaid` |
| `order_paid_friendly` | `TypeTicketMailOrderToPaidFriendly1` |
| `order_paid_live` | `orderToPaidLiveTicket` |
| `order_cancel` | `orderToCancel` |
| `order_changed` | `orderToChangeTicket` |
| `order_difficulties` | `orderToDifficultiesArose` |
| `order_live_issued` | `orderToLiveTicketIssued` |
| `list_approved` | `orderListApproved` |
| `list_cancel` | `orderListCancel` |
| `list_difficulties` | `orderListDifficultiesArose` |

---

## 7. Ответы

| Код | Тело | Значение | Что делать на стороне qr |
|---|---|---|---|
| `200` | `{ "success": true, "email_id": "<uuid>", "message": "Письмо принято" }` | письмо поставлено в очередь | успех (доставка асинхронная) |
| `200` | `{ "success": true, "message": "Уже принято ранее (идемпотентно)" }` | повтор того же `external_id` | успех, дубля нет |
| `422` | `{ "success": false, "message": "Неизвестное событие письма" }` | `event` не из каталога | исправить `event`, не ретраить как есть |
| `422` | `{ "success": false, "message": "Не передан email получателя" }` | пустой `email` | исправить тело |
| `401` | `{ "success": false, "message": "Доступ запрещён: …" }` | нет/неверный `X-QR-Token` | **не ретраить** — проверить ключ |
| `500` | — | ошибка на стороне org | можно ретраить (с `external_id` — безопасно) |

> `email_id` — id записи в `email_messages` (для корреляции/диагностики; путь письма виден в админке org «Доставка писем»).

---

## 8. Что происходит на стороне org (для понимания)

1. Контроллер валидирует `event` (по `EmailEvent::isValid`) и `email`.
2. Если задан `external_id` и он уже встречался → ранний `200` (идемпотентность).
3. Slug шаблона = привязка по событию (`event` + опц. `festival_id`/`order_type`/`ticket_type_id`) → fallback на дефолтный slug события.
4. Создаётся `email_messages` (`status=queued`, `source=qr_intake`) + история `email_queued`.
5. `SendEmailJob` (асинхронно, `tries=3`, backoff `30/120/600` сек): рендер `GenericTemplatedMail(slug, subject, vars)` → отправка → `status=sent`/`failed`.
6. Путь письма виден в админке org: **«Доставка писем»** (статус, ошибка, повтор) и, если задан `aggregate_id`, в «весь путь заказа».

---

## 9. Пример (Python, requests)

```python
import os, uuid, requests

ORG_API = "https://api.spaceofjoy.ru/api/v1/emailNotification/send"
QR_TOKEN = os.environ["ORG_QR_INGEST_TOKEN"]  # тот же ключ, что для qrOrder/create

def send_password_reset(email: str, reset_link: str):
    resp = requests.post(
        ORG_API,
        headers={
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-QR-Token": QR_TOKEN,
        },
        json={
            "event": "password_reset",
            "email": email,
            "subject": "Сброс пароля — Solar Systo",
            "vars": {"link": reset_link},
            "external_id": f"pwd-reset:{email}:{uuid.uuid4()}",
        },
        timeout=15,
    )
    resp.raise_for_status()
    return resp.json()["email_id"]
```

---

## 10. Провенанс (файлы кода org)

| Слой | Файл |
|---|---|
| Контроллер | `Backend/app/Http/Controllers/EmailDelivery/EmailNotificationController.php` |
| Роут | `Backend/routes/emailDelivery.php` (`v1/emailNotification/send` + `->middleware('qr.ingest')`) |
| Каталог событий | `Backend/src/EmailDelivery/Domain/EmailEvent.php` |
| Диспетчер/трекинг | `Backend/src/EmailDelivery/Application/MailDispatcher.php` + `email_messages` + `SendEmailJob` |
| Mailable | `Backend/app/Mail/GenericTemplatedMail.php` (рендер slug + vars) |
| Привязка шаблона по событию | `Backend/src/TemplateBinding/` (`templateBinding/*`, поле `event`) |
| Auth канала | `QR_INGEST_AUTH.md` (тот же `X-QR-Token`) |

> Просмотр доставки (админка org): `POST /api/v1/emailDelivery/getList`, `GET /getItem/{id}`, `POST /resend/{id}` — `auth:api + admin` (JWT админа org), к этому каналу qr доступа не имеет.
