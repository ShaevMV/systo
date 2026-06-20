# Для ИИ-агента qr.spaceofjoy.ru — как работают шаблоны писем и PDF (org)

> **Кому:** агенту, разрабатывающему витрину `qr.spaceofjoy.ru` (отдельный репозиторий/Claude).
> **Зачем:** понять, как org выбирает и рендерит шаблон письма/PDF, чтобы qr слал **правильные поля** и получал нужное оформление. Парные документы: [EMAIL_SEND_API.md](EMAIL_SEND_API.md) (как отправить письмо), [QR_CREATE_API.md](QR_CREATE_API.md) (как создать заказ), [HANDOFF_FOR_QR_AGENT.md](HANDOFF_FOR_QR_AGENT.md) (шпаргалка).

---

## 0. TL;DR (главное за 30 секунд)

- **Шаблонами владеет org.** Контент писем и PDF-билетов (тело, картинки, вёрстка) и **привязки** «какой шаблон за каким событием» редактирует **только администратор org** через свою админку. **qr шаблоны НЕ создаёт и НЕ редактирует** — у qr нет CRUD-доступа к ним.
- **qr только ТРИГГЕРИТ** шаблон, передавая **событие** и **данные заказа**. org сам резолвит, какой именно шаблон отрендерить.
- На выбор шаблона из данных qr влияют **5 полей**: `event`, `types_of_payment_id`, `ticket_type_id`, `order_type`, `festival_id`. Шли их корректно — получишь правильное письмо/PDF. Не пришлёшь — сработает дефолтный шаблон события (всё равно валидно).
- Если поведение «по умолчанию» устраивает — **специально делать ничего не нужно**: каждое событие имеет дефолтный шаблон, заказные письма уходят сами при смене статуса.

---

## 1. Два канала, где qr влияет на выбор шаблона

### Канал A — заказные письма и PDF-билеты (автоматически)
Когда qr создаёт/оплачивает заказ (`POST /api/v1/qrOrder/create`, `POST /api/v1/qrOrder/changeStatus/{id}` → «оплачен»), org **сам** выпускает билеты (PDF) и шлёт письма. Тебе НЕ нужно отдельно дёргать письма — они уходят как часть выдачи. На выбор шаблона тут влияют поля **самого заказа**: `order_data.type_order`, `guests[].type_ticket.id`, способ оплаты (`payment.method` → `types_of_payment`), `festival`.

### Канал B — не-заказные письма (явный вызов)
Письма, не привязанные к заказу (регистрация, сброс пароля и т.п.) — qr шлёт явно через `POST /api/v1/emailNotification/send` (S2S, заголовок `X-QR-Token`). Контракт — в [EMAIL_SEND_API.md](EMAIL_SEND_API.md). Там ты сам передаёшь `event` (+ опционально `order_type`/`ticket_type_id`/`festival_id`), которые попадают в резолвер.

> В обоих каналах работает **один и тот же** механизм выбора шаблона (см. §3).

---

## 2. Каталог событий писем (`EmailEvent`) — 16 кодов

Каждое письмо привязано к **событию**. Событие → **дефолтный slug** шаблона (на него всё откатывается, если нет специфичной привязки). Полный список и какие `vars` ждёт каждое — в [EMAIL_SEND_API.md §6](EMAIL_SEND_API.md). Кратко:

| event | дефолтный slug | когда |
|-------|----------------|-------|
| `order_created` | `orderToCreate` | заказ создан (двухшаговый цикл, билеты ещё не выпущены) |
| `order_paid` | `orderToPaid` | заказ оплачен (письмо с PDF-билетами) |
| `order_paid_friendly` | `TypeTicketMailOrderToPaidFriendly1` | заказ оплачен (Friendly) |
| `order_paid_live` | `orderToPaidLiveTicket` | живой билет оплачен |
| `order_cancel` | `orderToCancel` | заказ отменён |
| `order_changed` | `orderToChangeTicket` | данные заказа изменены |
| `order_difficulties` | `orderToDifficultiesArose` | трудности с заказом |
| `order_live_issued` | `orderToLiveTicketIssued` | живой билет выдан |
| `list_approved` | `orderListApproved` | заказ-список одобрен |
| `list_cancel` | `orderListCancel` | заказ-список отменён |
| `list_difficulties` | `orderListDifficultiesArose` | трудности со списком |
| `user_registered` | `newUser` | регистрация пользователя |
| `password_reset` | `passwordResets` | сброс пароля |
| `invite` | `invate` | приглашение |
| `questionnaire` | `questionnaire` | ссылка на анкету гостя |
| `questionnaire_approved` | `questionnaireApproved` | анкета одобрена |

- Заказные события (`order_*`, `list_*`) обычно срабатывают **сами** в канале A — слать их через `emailNotification/send` не нужно.
- Не-заказные (`user_registered`, `password_reset`, `invite`, `questionnaire`) — твой основной кейс для канала B.
- **Неизвестное событие → `422`.** Список — единственный источник правды (`Backend/src/EmailDelivery/Domain/EmailEvent.php`).

---

## 3. Как org выбирает конкретный шаблон (резолвер привязок)

org держит таблицу **привязок** `(event, festival_id, order_type, ticket_type_id, types_of_payment_id) → шаблон`. Для каждого письма/PDF резолвер:

1. Берёт все привязки, **подходящие** под запрос. Привязка подходит, если **каждая** её ось либо `NULL` (wildcard — «любое значение»), либо **равна** значению из запроса.
2. Из подходящих выбирает **самую специфичную** по сумме весов:

   | Ось | Вес | Что это со стороны qr |
   |-----|-----|------------------------|
   | `types_of_payment_id` | **16** | способ оплаты = **конкретный внешний продавец/магазин** (сильнейший override — «под каждого продавца своё письмо/PDF») |
   | `event` | **8** | код события письма |
   | `ticket_type_id` | **4** | тип билета (`guests[].type_ticket.id`) |
   | `order_type` | **2** | `regular` / `friendly` / `list` / `live` |
   | `festival_id` | **1** | фестиваль |

   `специфичность = 16·tp + 8·event + 4·ticket + 2·order + 1·festival`. Побеждает наибольшая.
3. Совпадений нет → берётся **дефолтная привязка** на тип (`email`/`pdf`). Нет и дефолта → **дефолтный slug события** (см. §2). То есть письмо/PDF уйдёт **всегда**, даже без единой привязки.

> **Вывод для qr:** чем точнее поля заказа (особенно `payment.method` → продавец и `guests[].type_ticket.id`), тем точнее подбор шаблона. Привязки **создаёт админ org**; ты лишь поставляешь данные, по которым они срабатывают.

---

## 4. Что слать, чтобы попасть в нужный шаблон

| Поле в контракте qr | Ось резолвера | Влияние |
|---------------------|---------------|---------|
| `order_data.type_order` (`regular`/`friendly`/`list`/`live`) | `order_type` | разные письма/PDF под тип заказа |
| `guests[].type_ticket.id` | `ticket_type_id` | разные PDF-билеты/письма под тип билета (детский, парковка, лесная карта…) |
| `payment.method` (→ `types_of_payment`) | `types_of_payment_id` | **спец-письмо/PDF под конкретного продавца/магазин** (напр. адрес магазина в теле письма) — сильнейший приоритет |
| `order_data.festival.id` | `festival_id` | оформление под конкретный фестиваль |
| `event` (только канал B, `emailNotification/send`) | `event` | какое письмо вообще |

- Все поля **опциональны** для резолвера: чего не пришлёшь — та ось считается wildcard, подбор откатывается на менее специфичную привязку / дефолт.
- `vars` (подстановки в шаблон: имя гостя, сумма, ссылки, QR-код и т.п.) — **обязательны для канала B**, см. [EMAIL_SEND_API.md §6](EMAIL_SEND_API.md). В канале A org собирает `vars` сам из заказа.

---

## 5. PDF-билеты — тот же механизм

PDF-билета — это **тоже шаблон** (`kind = pdf`), выбирается **тем же резолвером** по `(types_of_payment, ticket_type, order_type, festival)` (ось `event` для PDF не используется). То есть тип билета и продавец из заказа qr определяют, **какой PDF** отрендерится гостю. Дефолт — базовый `pdf`. Никаких отдельных действий от qr не требуется — PDF выпускается при выдаче билетов (канал A).

---

## 6. Два режима доступа — что можно по qr-токену, а что по логину админа

| Режим | Аутентификация | Что можно с шаблонами |
|-------|----------------|------------------------|
| **qr S2S-агент** (этот документ, §1–5) | `X-QR-Token` | Только **триггерить** шаблоны (создать/оплатить заказ → авто-письма/PDF; слать письмо по событию). **Редактировать шаблоны нельзя.** |
| **ИИ с логином/паролем администратора** (см. **§9**) | `POST /api/login` → JWT (`Authorization: Bearer …`) | **Полный доступ к редактированию** шаблонов писем и PDF-билетов: читать, править, превью, публиковать, откатывать. |

По **qr-токену**:
- ❌ Нельзя создавать/редактировать шаблоны (`/api/v1/template/*`) и привязки (`/api/v1/templateBinding/*`) — это admin-only.
- ❌ Нельзя выбирать slug напрямую — qr передаёт событие + данные заказа, slug выбирает резолвер.
- ✅ Можно: создавать/оплачивать заказы (канал A) и слать письма по событию (канал B).

> **Если ИИ дали логин/пароль администратора** — он может **сам править шаблоны писем и билетов** через admin-API. Полная инструкция — в **§9**. Это нужно, например, чтобы поправить текст/вёрстку письма, подставить адрес магазина продавца, починить PDF-билет.

---

## 7. Доставка и наблюдаемость (для понимания)

- Любое письмо, отправленное по любому каналу, **отслеживается**: статусы `queued → sending → sent → [delivered → opened] / failed`, видно в админке org «Доставка писем» (`source = qr_pipeline` для выдачи заказа, `qr_intake` для `emailNotification/send`). Подробнее — [EMAIL_SEND_API.md §8](EMAIL_SEND_API.md).
- Весь путь заказа qr (приём → билеты(PDF) → письма → шаги) — `GET /api/v1/qrOrder/getPipeline/{id}` (admin org).
- Идемпотентность: повтор `emailNotification/send` с тем же `external_id` дубль не создаёт; повторный «оплачен» билеты второй раз не выдаёт (защита по `issued_at`).

---

## 8. Провенанс (файлы кода org — источник правды)

- Каталог событий: `Backend/src/EmailDelivery/Domain/EmailEvent.php` (16 событий + `defaultSlug` + `label`).
- Резолвер привязок: `Backend/src/TemplateBinding/Domain/TemplateBindingResolver.php`, веса — `Backend/src/TemplateBinding/Dto/TemplateBindingDto.php::specificity()`.
- Рендер шаблона из БД (с fallback на blade): `Backend/src/Template/` (движок **Mustache**, logic-less).
- Отправка/трекинг: `Backend/src/EmailDelivery/Application/MailDispatcher.php`, эндпоинт `emailNotification/send` (middleware `qr.ingest`, `X-QR-Token`).
- Каталог событий для UI: `GET /api/v1/templateBinding/events` (admin org).

> При расхождении этого документа с кодом — **прав код**. Сообщи org-агенту, чтобы синхронизировать (см. TD-24 — координация контрактов qr↔org).

---

## 9. РЕДАКТИРОВАНИЕ шаблонов администратором (для ИИ с логином/паролем)

Раздел для ИИ, которому дали **логин и пароль администратора org**. Даёт всё, чтобы **читать и править шаблоны писем и PDF-билетов** (поправить текст/вёрстку письма, адрес магазина продавца, оформление PDF-билета).

### 9.1 База и вход

- **Базовый URL:** прод `https://api.spaceofjoy.ru`, стенд `https://api.staging.spaceofjoy.ru`. Все пути ниже — от базы.
- **Логин:** `POST /api/login`, тело `{ "email": "<логин>", "password": "<пароль>" }` → ответ `{ "authorisation": { "token": "Bearer eyJ…" } }`.
- Дальше **во всех** запросах: заголовки `Authorization: Bearer <token>` и `Accept: application/json` (для POST — ещё `Content-Type: application/json`).
- Токен живёт **60 минут**; продлить — `POST /api/refresh` (с тем же `Authorization`). Все `/api/v1/template/*` требуют роль **admin**.

### 9.2 Движок шаблонов — Mustache (важно для правок)

- `{{ имя }}` — подстановка с **экранированием HTML** (безопасно). `{{{ имя }}}` — **сырой вывод без экранирования**, используется ТОЛЬКО для QR-кода (`data:image/png;base64,…`) в PDF — **не ломать тройные скобки** у QR, иначе картинка не отрисуется.
- Секции: `{{#список}}…{{/список}}` (повтор/условие), `{{^x}}…{{/x}}` («если пусто/false»).
- **НЕТ** PHP/blade/кода — только logic-less Mustache. Любой PHP в теле выводится как текст, не исполняется.
- Какие переменные доступны шаблону — узнавать через `variables/{slug}` (9.4), **не выдумывать имена**.

### 9.3 Что редактируется

- `kind = email` — письма (~24), `kind = pdf` — PDF-билеты (~9). У каждого: `id`, `slug`, `title`, **`description`** (краткое назначение — добавлено недавно, помогает найти нужный), `body` (исходник Mustache), `draft_body` (черновик), `active`.

### 9.4 Эндпоинты (рабочий набор)

| Действие | Запрос | Зачем |
|---|---|---|
| Список | `POST /api/v1/template/getList` · `{ "filter": { "kind":"email\|pdf"?, "slug"?, "active"? }, "orderBy": {"title":"asc"}? }` | найти шаблон (в ответе есть `description`) |
| Открыть | `GET /api/v1/template/getItem/{id}` | получить `body`, `draft_body`, `description`, `slug`, `kind` |
| Палитра переменных | `GET /api/v1/template/variables/{slug}?kind=email\|pdf` | какие `{{ плейсхолдеры }}` есть |
| **Превью (без сохранения)** | `POST /api/v1/template/preview` · `{ "kind":"email\|pdf", "slug":"…", "body":"<исходник>" }` | проверить рендер ДО публикации. email → `{ "html":"…" }`; pdf → поток `application/pdf`. Ошибка Mustache → `422` с текстом |
| Черновик | `POST /api/v1/template/saveDraft/{id}` · `{ "draft_body":"…" }` | отложить наработку, **прод не меняется** |
| **Опубликовать** | `POST /api/v1/template/publish/{id}` · `{ "body":"…", "comment":"что изменил"? }` | **меняет то, что видят гости** + снапшот в версии |
| Метаданные | `POST /api/v1/template/edit/{id}` · `{ "data": { "title"?, "description"?, "body"?, … } }` | поправить название/описание/тело |
| Создать | `POST /api/v1/template/create` · `{ "data": { "slug","kind","engine":"html","title","description"?,"body","active":false } }` | новый шаблон |
| Вкл/выкл | `POST /api/v1/template/activate/{id}` · `{ "active": true\|false }` | выкл = откат на старый blade |
| Версии / Откат | `GET …/versions/{id}` · `POST …/rollback/{id}/{versionId}` | история тел + возврат прежней версии |
| Журнал | `GET /api/v1/template/history/{id}` | кто/что/когда менял |
| Картинка | `POST /api/v1/template/uploadImage` (multipart `image`) | URL для `<img src>` (фон PDF/иллюстрации) |

### 9.5 Безопасный порядок правки (рекомендуется ИИ)

1. `getList` → найти шаблон по `description`/`slug`/`kind`.
2. `getItem/{id}` → взять текущий `body`.
3. `variables/{slug}` → свериться, какие плейсхолдеры доступны.
4. Внести правку в `body`.
5. **`preview`** с новым `body` → убедиться, что рендерится (email — html глазами; pdf — отдался валидный PDF). При `422` — починить синтаксис, **НЕ публиковать**.
6. (опц.) `saveDraft`.
7. **`publish`** с проверенным `body` + `comment` — только после успешного preview.
8. Если что-то не так — `versions` + `rollback`.

### 9.6 Предостережения

- **`publish` меняет живой рендер сразу** (письма/PDF гостям). Всегда `preview` перед `publish`.
- **Не ломать `{{{ … }}}`** (тройные скобки) в PDF — это сырой вывод QR-кода; двойные скобки сломают картинку.
- `preview` рендерит на **тестовых фикстурах** (без реальных ПДн) — это норма; реальные данные подставятся в проде.
- Деактивация (`activate false`) откатывает шаблон на **старый blade** — безопасный аварийный откат.
- **Не менять `slug`/`kind`** существующего шаблона — привязки указывают на `slug`.

### 9.7 Мини-пример (curl)

```bash
BASE=https://api.spaceofjoy.ru   # стенд: https://api.staging.spaceofjoy.ru
# 1. логин (логин/пароль даст владелец)
TOKEN=$(curl -s -X POST $BASE/api/login -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"email":"<АДМИН-EMAIL>","password":"<ПАРОЛЬ>"}' | jq -r '.authorisation.token' | sed 's/^Bearer //')
A="Authorization: Bearer $TOKEN"
# 2. найти письмо «заказ оплачен»
curl -s -X POST $BASE/api/v1/template/getList -H "$A" -H 'Accept: application/json' \
  -H 'Content-Type: application/json' -d '{"filter":{"kind":"email"}}'
# 3. превью правки (НЕ публикует) — проверить рендер
curl -s -X POST $BASE/api/v1/template/preview -H "$A" -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"kind":"email","slug":"orderToPaid","body":"<p>Новый текст для {{ order.email }}</p>"}'
# 4. опубликовать (МЕНЯЕТ прод) — только после успешного preview
curl -s -X POST $BASE/api/v1/template/publish/<ID-ШАБЛОНА> -H "$A" -H 'Accept: application/json' \
  -H 'Content-Type: application/json' -d '{"body":"<p>Новый текст…</p>","comment":"правка через ИИ"}'
```

> Контракт admin-CRUD шаблонов целиком — `.claude/docs/API.md §8.2` (org-репозиторий). При расхождении — прав код.
