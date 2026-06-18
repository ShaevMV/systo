# Бизнес-правила проекта Systo

## 1. Статусы заказов и переходы

### Матрица допустимых переходов

#### Обычные билеты (NEW → PAID)

| Из статуса | → PAID | → CANCEL | → DIFFICULTIES_AROSE |
|------------|--------|----------|----------------------|
| **NEW** | ✅ | ✅ | ✅ |
| **PAID** | — | — | ✅ |
| **DIFFICULTIES_AROSE** | ✅ | ✅ | — |

#### Живые билеты (NEW_FOR_LIVE → PAID_FOR_LIVE → LIVE_TICKET_ISSUED)

| Из статуса | → PAID_FOR_LIVE | → CANCEL | → DIFFICULTIES_AROSE | → LIVE_TICKET_ISSUED |
|------------|-----------------|----------|----------------------|----------------------|
| **NEW_FOR_LIVE** | ✅ | ✅ | ✅ | ✅ |
| **PAID_FOR_LIVE** | — | ✅ (CANCEL_FOR_LIVE) | — | ✅ |
| **DIFFICULTIES_AROSE** | ✅ | ✅ | — | — |
| **LIVE_TICKET_ISSUED** | — | ✅ (CANCEL_FOR_LIVE) | — | — |

#### Заказы-списки (NEW_LIST → APPROVE_LIST)

| Из статуса | → APPROVE_LIST | → CANCEL_LIST | → DIFFICULTIES_AROSE_LIST |
|------------|----------------|----------------|---------------------------|
| **NEW_LIST** | ✅ | ✅ | ✅ |
| **APPROVE_LIST** | — | — | ✅ |
| **DIFFICULTIES_AROSE_LIST** | ✅ | ✅ | — |

#### Терминальные статусы

| Из статуса | → Любые другие |
|------------|----------------|
| **CANCEL** / **CANCEL_FOR_LIVE** / **CANCEL_LIST** | ❌ терминальный |

### Краткая сводка переходов

```
Обычные билеты:
  NEW ──→ PAID ──→ DIFFICULTIES_AROSE ──→ PAID (цикл)
   │         │
   └──→ CANCEL  └──→ DIFFICULTIES_AROSE ──→ CANCEL

Живые билеты:
  NEW_FOR_LIVE ──→ PAID_FOR_LIVE ──→ LIVE_TICKET_ISSUED ──→ CANCEL_FOR_LIVE
       │                │                    │
       └──→ CANCEL      └──→ CANCEL_FOR_LIVE └──→ CANCEL_FOR_LIVE
       └──→ DIFFICULTIES_AROSE ──→ PAID (обычный)
       └──→ LIVE_TICKET_ISSUED (сразу выдать)

Заказы-списки (куратор → admin/manager):
  NEW_LIST ──→ APPROVE_LIST ──→ DIFFICULTIES_AROSE_LIST ──→ APPROVE_LIST (цикл)
       │                                 │
       └──→ CANCEL_LIST                  └──→ CANCEL_LIST
       └──→ DIFFICULTIES_AROSE_LIST ──→ APPROVE_LIST / CANCEL_LIST
```

### Правила смены статуса

- **PAID** — создаёт билеты, генерирует PDF + QR, отправляет email с билетами, рассылает ссылки на анкеты гостям
- **PAID_FOR_LIVE** — подтверждает оплату живого билета, **НЕ генерирует PDF**, ждёт продавца на месте
- **CANCEL** — отменяет все билеты заказа, отправляет email об отмене
- **CANCEL_FOR_LIVE** — отменяет живые билеты, освобождает номера
- **LIVE_TICKET_ISSUED** — требует массив `liveList` (номера живых билетов), присваивает номера билетам, отправляет анкеты
- **DIFFICULTIES_AROSE** — **обязателен комментарий**, отменяет билеты, отправляет email о трудностях
- **APPROVE_LIST** — создаёт билеты заказа-списка, шлёт PDF получателю (через `OrderListApproved`, blade-шаблон из `Location.email_template`), рассылает анкеты гостям
- **CANCEL_LIST** — отменяет билеты заказа-списка, шлёт письмо `OrderListCancel` получателю
- **DIFFICULTIES_AROSE_LIST** — **обязателен комментарий**, отменяет билеты, шлёт `OrderListDifficultiesArose` получателю

### Роли для смены статуса

- Обычные/Live статусы (PAID/CANCEL/PAID_FOR_LIVE/LIVE_TICKET_ISSUED/DIFFICULTIES_AROSE и пр.): `seller`, `admin`, `pusher`
- List-статусы (APPROVE_LIST / CANCEL_LIST / DIFFICULTIES_AROSE_LIST): только `admin`, `manager`
- **Авто-одобрение (без авторизации)**: `POST /api/v1/order/create` с заголовком `AutoPayment: <AUTO_PAYMENT_TOKEN>` сразу переводит свежесозданный заказ `NEW → PAID` (через тот же `ChangeStatus`). Только для не-биллинговых способов оплаты (`types_of_payment.is_billing = false`). В историю пишется `actor_type = auto_payment`. Если токен невалиден — `403`, заказ не создаётся.

### QR-заказы (приём от витрины qr.spaceofjoy.ru)

- **Отдельный канал** приёма заказов от внешней витрины **qr.spaceofjoy.ru** (таблица `qr_orders`, не `order_tickets`). `id` заказа qr == `id` заказа org.
- Создание (`POST /api/v1/qrOrder/create`) и смена статуса (`POST /api/v1/qrOrder/changeStatus/{id}`) — **S2S-канал** (Sanctum-токен сервис-аккаунта со scope `qr:ingest`), не человек.
- `status` — **строки от qr** (`создан` / `оплачен` / `отменён`), не строгая матрица переходов org. `type_order` ∈ `regular` / `friendly` / `list` / `live`.
- При смене статуса в `оплачен`/`paid` запускается **выдача билетов** (PDF/письма) — **один раз**, защита по `issued_at` (повторный «оплачен» от ретраев qr не выдаёт билеты снова).
- В историю всех событий qr-заказа пишется `actor_type = qr`, `actor_id = null`.
- Просмотр (`getList`/`getItem`/`getHistory`) — **только admin** (JWT), read-only (содержит ПДн). Из админки org заказы НЕ редактируются.

#### Пайплайн выдачи qr-заказа в истории (шаги)

При выдаче билетов qr-заказ проходит через цепочку шагов, каждый из которых пишет событие в `domain_history` (`aggregate_type = qr_order`, `actor_type = qr`, `event_name = step_<имя_шага>` со статусом `ok`/`fail` + текст ошибки в `payload`):

| Событие (`event_name`) | Шаг | Что делает |
|------------------------|-----|------------|
| `step_create_tickets` | `CreateTicketsStep` | Создаёт билеты обычного заказа |
| `step_create_live_tickets` | `CreateLiveTicketsStep` | Создаёт живые билеты |
| `step_link_live` | `LinkLiveStep` | Привязывает номера живых билетов |
| `step_push_to_baza` | `PushToBazaStep` | Синхронизация билетов в Baza |
| `step_send_order_email` | `SendOrderEmailStep` | Письмо с PDF-билетами получателю |
| `step_send_list_email` | `SendListEmailStep` | Письмо по заказу-списку |
| `step_send_live_email` | `SendLiveEmailStep` | Письмо по живому билету |
| `step_send_telegram` | `SendTelegramStep` | Уведомление в Telegram |

Полный путь заказа (приём → билеты(PDF) → письма → шаги) отдаётся одним эндпоинтом `GET /api/v1/qrOrder/getPipeline/{id}` (admin). PDF-ссылки заказа — `GET /api/v1/qrOrder/getTicketPdf/{id}` (admin).

#### qr-канал писем (S2S, не-заказные письма)

- Витрина qr может попросить org **отправить письмо**, не связанное с заказом (регистрация, сброс пароля и т.п.) — `POST /api/v1/emailNotification/send` (S2S-канал, заголовок `X-QR-Token`, middleware `qr.ingest`).
- Контракт: `{ event, email, vars{}, festival_id?, order_type?, ticket_type_id?, subject?, aggregate_id?, external_id? }`.
- `event` — код из каталога событий писем (`EmailEvent`, см. §13); неизвестное событие или пустой `email` → `422`, без токена → `401`.
- **Идемпотентность по `external_id`** — повтор того же `external_id` не создаёт дубль письма.
- Письмо отправляется через `GenericTemplatedMail` (рендер slug по событию + `vars`) и **отслеживается** диспетчером (см. §13). В истории — `actor_type = qr`, `source = qr_intake`.

---

## 2. Промокоды

> **⚠️ Модель применения промокодов (2026-06-14, разворот org → admin-only):**
> Расчёт скидки промокодом ниже относится к **старой** модели (org-как-витрина, расчёт цены на стороне org). После переезда коммерции на **qr.spaceofjoy.ru** цену заказа считает **qr (мастер цены)**, а org/qr-заказы **НЕ применяют промокод к цене и не пересчитывают стоимость** — `total_price` приходит готовым из контракта qr. В qr-заказах промокод хранится **только для справки/отчётности** (в `payload`). Будущая фича «промокоды-агрегатор» (v2.7.0) — это **история/статистика использования** промокодов, а не ценовая логика.

### Типы скидок

| Тип | Поле `is_percent` | Расчёт |
|-----|-------------------|--------|
| **Процентная** | `true` | `скидка = цена_билета * (discount / 100)` |
| **Фиксированная** | `false` | `скидка = discount` (в рублях) |

### Правила применения

1. Промокод привязывается к **типу билета** (`ticket_type_id`) или ко всем (`null`)
2. Промокод привязывается к **фестивалю** (`festival_id`) или ко всем (`null`)
3. Промокод должен быть **активен** (`active = true`)
4. Если установлен **лимит** — проверяется `COUNT(заказов с этим промокодом) < limit`
5. Если лимит исчерпан — промокод считается невалидным
6. Один промокод = один заказ (неmultiple application)

### Внешние промокоды

- Хранятся в отдельной таблице `external_promo_codes`
- Привязываются к заказу при оформлении (`order_tickets_id`)
- Один внешний промокод = один заказ

### Правила для бота (`savePromoCodeForBot`)

- Имя приводится к **UPPERCASE** + случайная строка
- Middleware `bot` требует заголовок `auth-token`

---

## 3. Оплата (Billing)

### Способ оплаты через СБП

1. Создаётся заказ со способом оплаты где `is_billing = true`
2. Вызывается `BillingService::createPayments()` — POST на внешний шлюз
3. Шлюз возвращает URL-ы: **Android**, **iOS**, **Desktop** (QR для СБП)
4. Пользователь оплачивает → шлюз шлёт webhook

### Обработка webhook

| Статус от шлюза | Действие |
|-----------------|----------|
| `payment.completed` | Статус заказа → **PAID**, сохраняется ссылка на чек |
| `payment.refund` | Статус заказа → **CANCEL**, комментарий "Возврат платежа" |
| Любой другой | Статус → **DIFFICULTIES_AROSE** |

### Данные чека

- `label`: "Взнос на туристический слёт"
- `taxation_system`: 1
- `vat`: 6

---

## 4. Фестивали и типы билетов

### Волны ценообразования

| Волна | Обычный | Регионы |
|-------|---------|---------|
| **1-я (базовая)** | 3800₽ | 3600₽ |
| **2-я** | 4200₽ | 4000₽ |
| **3-я** | 4600₽ | 4400₽ |

- Актуальная цена — **последняя** по дате `before_date`
- Цена фиксируется на момент создания заказа
- **Исключения**: детский билет (400₽), групповой билет (24000₽), оргвзнос мульти фестиваль (7600₽) — фиксированные цены, не участвуют в волнах

### Типы билетов

| Тип | Цена | Особенности |
|-----|------|-------------|
| **Оргвзнос** | Динамическая | Стандартный билет |
| **Оргвзнос для регионов** | Динамическая (дешевле) | Для удалённых участников |
| **Оргвзнос мульти фестиваль** | 7600₽ | Доступ к нескольким фестивалям |
| **Оргвзнос на осень** | 4000₽ | Специфичный фестиваль |
| **Живой билет** | 3800₽ | Получается на месте, уникальный номер |
| **Живой билет лесная карта** | 7600₽ | Премиум живой билет |
| **Групповой билет** | 24000₽ | `group_limit` — лимит группы |
| **Детский билет** | 400₽ | Фиксированная цена, без волн, детская анкета |
| **Парковка** | Любая | `is_parking = true` — форма ввода авто вместо ФИО гостей |

### Групповые билеты

- Если `group_limit !== null` — это групповой билет
- Можно купить несколько билетов в одном заказе
- Групповой билет — фиксированная цена, не зависит от волн

### Живые билеты

- `is_live_ticket = true` — билет получается на месте
- Номера живых билетов **уникальны** в рамках фестиваля
- Проверка дубликатов: `CheckLiveTicketService`
- Live-номера генерируются через `KEY_LIVE_TICKET` (шифрование)

### Парковочные билеты

- `is_parking = true` — флаг на `ticket_type` (по аналогии с `is_live_ticket`)
- **Обычный заказ** (через `POST /api/v1/order/create`), а не отдельный flow — никакой специальной логики на бэке нет
- Меняется только **форма ввода гостей** на фронте: вместо ФИО + email появляются поля **гос. номер**, **марка авто**, **ФИО водителя**, **email**
- Данные авто **склеиваются** в `guests[].value` одной строкой формата `"А123АА777 / Toyota Camry / Иванов И.И."`. Email водителя идёт в `guests[].email` без изменений
- В одном заказе может быть **несколько автомобилей** (несколько элементов `guests[]`)
- Поле `masterName` (ФИО владельца) **не запрашивается** — заказчик идентифицируется по `email`/`phone`/`city` из шага 1
- Тип анкеты гостя — обычный, привязывается стандартно через `ticket_type.questionnaire_type_id`. Email водителя получает ссылку на анкету по общему flow `ProcessGuestNotificationQuestionnaire`

---

## 5. Генерация билетов

### Процесс

1. Заказ переходит в статус **PAID**
2. Генерируется событие `ProcessCreateTicket` — создаёт записи билетов для каждого гостя
3. Для каждого билета генерируется `ProcessCreatingQRCode`:
   - QR-код: PNG 300×300, high error correction, с логотипом
   - Данные QR: `/newTickets/{ticketId}`
   - PDF рендерится через DomPDF с шаблоном фестиваля (`pdf`, `pdf2`)
4. PDF сохраняется в `storage/app/public/tickets/{ticketId}.pdf`

### Шаблоны PDF

- Шаблоны зависят от фестиваля (`$festivalView`)
- Расположены в `resources/views/pdf.blade.php`, `resources/views/pdf2.blade.php`
- Шрифты: `istok-r.ttf` (Shared/Services/assets/)

---

## 6. Анкеты гостей

### Обязательные поля

Определяются динамически через JSON-конфигурацию типа анкеты (`questionnaire_type.questions`):

- `phone` — телефон (обязательный)
- `telegram` — никнейм Telegram (nullable, 5–32 символа, `^[a-zA-Z0-9_]+$`, unique)
- `agy` — возраст (nullable, integer)
- Остальные поля — по конфигурации типа анкеты

### Типы анкет

| Код | Название | Поля |
|-----|----------|------|
| `guest` | Гостевая анкета | 12 полей (возраст, телефон, telegram, vk, и т.д.) |
| `new_user` | Анкета нового пользователя | 12 полей (аналогично гостевой, email обязательный) |
| `child` | Детская анкета | 8 полей (имя ребёнка, возраст, аллергия, данные родителя, доверенный контакт, платёж, сумма, email/соцсеть) |

#### Детская анкета — поля

| Поле | Name | Тип | Required | Описание |
|------|------|-----|----------|----------|
| Имя и Фамилия Ребенка | `childName` | string | ✅ | |
| Сколько лет ребенку? | `childAge` | number | ✅ | |
| Есть ли аллергия? | `allergy` | text | ❌ | Медицинские особенности |
| Ваше имя и фамилия и номер телефона | `parentInfo` | string | ✅ | Данные родителя |
| Телефон доверенного человека | `trustedPhone` | string | ❌ | С валидацией телефона |
| Данные о платеже / участии | `paymentInfo` | text | ✅ | |
| Сумма платежа | `paymentAmount` | number | ✅ | 0 для волонтёров |
| Email или контакт в соцсетях | `contact` | string | ✅ | |

### Статусы анкеты

| Статус | Описание |
|--------|----------|
| **NEW** | Анкета заполнена, ожидает проверки |
| **APPROVE** | Анкета одобрена администратором |

### Уведомления

- При создании заказа — рассылка ссылок на анкеты гостям (`ProcessGuestNotificationQuestionnaire`)
- При апруве анкеты — отправка invite link (`ProcessInviteLinkQuestionnaire`)
- Если есть telegram — отправка уведомления через Telegram-бота (`ProcessTelegramSend`)
- Telegram-бот: POST на `http://77.222.60.58:8000`

### Валидация полей анкет

Валидация строится динамически из JSON-конфигурации типа анкеты (`questionnaire_type.questions`):

**Формат конфигурации поля:**
```json
{
  "name": "telegram",
  "label": "Никнейм Telegram",
  "validate": {
    "rules": ["nullable", "string", "min:5", "max:32", "regex:/^[a-zA-Z0-9_]+$/"],
    "messages": {"min": "Минимум 5 символов"}
  }
}
```

**Или regex-строка:**
```json
{
  "name": "telegram",
  "validate": "/^[a-zA-Z0-9_]+$/"
}
```

**Специальная обработка:**
- `telegram` — автоматически добавляется `unique:questionnaire,telegram`
- `phone` — валидация телефона (Laravel `phone` rule)
- `email` — валидация email (Laravel `email` rule)

Сервис: `QuestionnaireValidationService::buildValidationRules($questionnaireTypeId)`

### Валидация Telegram (базовая)

```php
'telegram' => [
    'nullable',
    'string',
    'min:5',
    'max:32',
    'regex:/^[a-zA-Z0-9_]+$/',
    Rule::unique('questionnaire', 'telegram'),
]
```

---

## 7. Роли пользователей

| Роль | Описание | Доступные действия |
|------|----------|-------------------|
| **guest** | Обычный пользователь | Покупка билетов, анкеты, свои заказы |
| **admin** | Полный доступ | Всё + управление промокодами, типами билетов, пользователями, анкетами |
| **seller** | Продавец живых билетов | Просмотр списка заказов (`POST /api/v1/order/getList`), смена статуса заказов (`POST /api/v1/order/toChangeStatus`) |
| **pusher** | Продавец Friendly-билетов | Создание Friendly-заказов (`POST /api/v1/order/createFriendly`), список Friendly-заказов (`POST /api/v1/order/getListForFriendly`), смена статуса (`POST /api/v1/order/toChangeStatus`) |
| **manager** | Менеджер анкет и заказов-списков | Просмотр и одобрение анкет, одобрение/отмена заказов-списков (`getListsList`, `toChangeStatus` для `*_list`) |
| **curator** | Куратор заказов-списков | Создание заказа-списка (`createList`), просмотр своих заказов-списков (`getCuratorList`) |

### Проверка прав

- `admin` middleware: `is_admin = true` или `role = 'admin'`
- `role:seller,admin,pusher` — проверка через `CheckRole` middleware
- Ролевые эндпоинты проверяются через `/api/isCorrectRole` (POST с массивом ролей)

---

## 8. Приглашения (Invite Links)

### Правила

1. Ссылка-приглашение доступна **только после одобрения анкеты**
2. Генерируется при `Questionnaire::toApprove()`
3. Проверка валидности: GET `/api/v1/invite/isCorrectInviteLink/{userId}`
4. При оформлении заказа по invite-ссылке — привязка к referrer
5. Статистика по inviter: `OrderStatusListDto` (количество заказов по статусам)

---

## 9. JWT аутентификация

| Параметр | Значение |
|----------|----------|
| Алгоритм | HS256 |
| TTL токена | 60 минут |
| TTL refresh | 2 недели (20160 минут) |
| Обязательные клеймы | `iss`, `iat`, `exp`, `nbf`, `sub`, `jti` |
| Blacklist | Включён (через Laravel cache) |
| Lock subject | Включён (защита от имперсонации) |

### Токен в запросах

- Заголовок: `Authorization: Bearer {token}`
- Формат хранения: `"Bearer {token_value}"`
- Lifetime: UNIX timestamp

---

## 10. Очереди и асинхронная обработка

### Конфигурация

- `QUEUE_CONNECTION=database` — таблица `jobs`
- `retry_after=1800` секунд (30 минут)
- Worker: supervisord (Laravel queue worker)

### Что обрабатывается асинхронно

| Задача | Domain Event |
|--------|-------------|
| Email уведомлений (создание, оплата, отмена, трудности) | `ProcessUserNotification*` |
| Генерация QR-кодов и PDF | `ProcessCreatingQRCode` |
| Создание билетов | `ProcessCreateTicket` |
| Отмена билетов | `ProcessCancelTicket` |
| Рассылка анкет гостям | `ProcessGuestNotificationQuestionnaire` |
| Telegram уведомления | `ProcessTelegramSend`, `ProcessTelegramByQuestionnaireSend` |

---

## 11. Лимиты и ограничения

| Параметр | Значение | Где применяется |
|----------|----------|-----------------|
| **Макс. гостей в заказе** | Зависит от `groupLimit` типа билета | BuyTicket.vue (`isAllowedGuest`) |
| **Мин. гостей** | Зависит от типа билета | BuyTicket.vue (`isAllowedGuestMin`) |
| **Лимит промокода** | `limit` поле (nullable) | `LimitPromoCodeDto::getCorrect()` |
| **Rate Limit API** | 60 запросов/мин на IP/user | `Throttle:api` middleware |
| **Уникальность Telegram** | Да, на всю таблицу | `QuestionnaireValidationService` |
| **Уникальность Email пользователя** | Да | `AuthController@register` |

---

## 12. Заказы-списки и Локации

### Что это
**Заказ-список** — третий тип заказа (наряду с обычным и Friendly). Создаёт **куратор** для группы гостей на конкретную **локацию (сцену)** фестиваля.

### Отличия от обычного и Friendly заказов
- **Не имеет цены** (`price`, `discount`, `types_of_payment_id`, `ticket_type_id` — NULL в `order_tickets`)
- Привязан к `location_id` (FK → `locations`) вместо `ticket_type_id`
- В `curator_id` — UUID куратора-создателя (хранится отдельно от `friendly_id`)
- `user_id` — получатель билетов (создаётся/находится по email, как в обычном заказе)

### Сущность Location (локация / сцена)
| Поле | Тип | Описание |
|------|-----|----------|
| `id` | uuid | PK |
| `name` | string | Название локации/сцены |
| `description` | text NULL | Описание |
| `questionnaire_type_id` | uuid NULL | Шаблон анкеты для гостей этой локации |
| `festival_id` | uuid | Фестиваль |
| `email_template` | string NULL | Имя blade-шаблона письма (по умолчанию `orderListApproved`) |
| `pdf_template` | string NULL | Имя blade-шаблона PDF |
| `active` | bool | Видна ли в форме создания списка |

### Жизненный цикл заказа-списка
1. **Куратор** создаёт список через `POST /api/v1/order/createList` → статус `NEW_LIST`. Никаких писем не уходит.
2. **Admin/manager** одобряет → статус `APPROVE_LIST`:
   - Создаются билеты (`ProcessCreateTicket`)
   - Гостям с email уходят ссылки на анкеты (`ProcessGuestNotificationQuestionnaire`)
   - Получателю (`order.email`) уходит письмо с PDF-билетами (`ProcessUserNotificationListApproved` → `OrderListApproved`)
3. **Admin/manager** может отменить → `CANCEL_LIST` или поставить `DIFFICULTIES_AROSE_LIST` с обязательным комментарием.

### Кто получает письма
- **Получатель** (`order.email`, поле `user_id`) — все письма по смене статуса (approve / cancel / difficulties)
- **Куратор** — НЕ получает писем
- **Гости** — получают ссылки на анкеты только при `APPROVE_LIST`

### Изоляция от обычных заказов
В репозитории добавлены фильтры:
- `getList()` (admin/seller) — `WHERE curator_id IS NULL`
- `getFriendlyList()` (pusher) — `WHERE friendly_id IS NOT NULL AND curator_id IS NULL`
- `getListsList()` / `getCuratorList()` — `WHERE curator_id IS NOT NULL`

Заказы-списки **не появляются** в списках обычных и Friendly заказов.

---

## 13. События писем и контроль доставки

### Каталог событий писем (`EmailEvent`)

Единый справочник «какое письмо за каким событием». Каждое событие → дефолтный slug шаблона (= текущий зашитый в Mailable, полная обратная совместимость) + человекочитаемая метка. Источник правды — `Backend/src/EmailDelivery/Domain/EmailEvent.php`. Каталог для селектора в админке — `GET /api/v1/templateBinding/events` (admin).

| Событие (`event`) | Дефолтный slug | Метка |
|-------------------|----------------|-------|
| `order_created` | `orderToCreate` | Заказ создан |
| `order_paid` | `orderToPaid` | Заказ оплачен |
| `order_paid_friendly` | `TypeTicketMailOrderToPaidFriendly1` | Заказ оплачен (Friendly) |
| `order_paid_live` | `orderToPaidLiveTicket` | Живой билет оплачен |
| `order_cancel` | `orderToCancel` | Заказ отменён |
| `order_changed` | `orderToChangeTicket` | Данные заказа изменены |
| `order_difficulties` | `orderToDifficultiesArose` | Трудности с заказом |
| `order_live_issued` | `orderToLiveTicketIssued` | Живой билет выдан |
| `list_approved` | `orderListApproved` | Список одобрен |
| `list_cancel` | `orderListCancel` | Список отменён |
| `list_difficulties` | `orderListDifficultiesArose` | Трудности со списком |
| `user_registered` | `newUser` | Регистрация пользователя |
| `password_reset` | `passwordResets` | Сброс пароля |
| `invite` | `invate` | Приглашение |
| `questionnaire` | `questionnaire` | Анкета гостя |

### Привязка шаблона по событию

- Привязки шаблонов (`template_bindings`, см. DOMAIN/API §8.2 templateBinding) расширены осью **`event`** (nullable = «любое событие», wildcard). `create`/`edit` принимают поле `event` (валидируется `EmailEvent::isValid`).
- Резолвер выбирает самую специфичную привязку по 5 осям: **`types_of_payment` > `event` > `ticket_type` > `order_type` > `festival`** (вес `types_of_payment` = 16 — сильнейший override продавца, AF-9; `event` = 8). Нет совпадения → привязка-дефолт на `kind` (`email`/`pdf`); нет дефолта → старый slug (обратная совместимость, в т.ч. легаси `types_of_payment.email`). Ось `types_of_payment` (тип оплаты = внешний продавец/магазин) → «под каждого продавца определённый тип письма».
- `event = null` в запросе → подходят только привязки с `event = null` (поведение PDF/выдачи **не меняется**).

### Машина статусов письма (`EmailStatus`)

Контроль полного пути письма «дошло / где застряло». Текущий статус хранится в `email_messages.status`, таймлайн — в `domain_history` (`aggregate_type = email`, `event_name = email_<статус>`).

```
queued ──→ sending ──→ sent ──→ delivered ──→ opened
   │          │          │          │
   └─ failed ◄┘          └─ bounced ◄┘     (failed / bounced ──→ queued при ретрае)
```

| Статус | Что значит |
|--------|------------|
| **queued** | Письмо поставлено в очередь (`MailDispatcher::send`) |
| **sending** | Задача `SendEmailJob` начала отправку |
| **sent** | Передано на SMTP-сервер (**НЕ** «доставлено в ящик») |
| **delivered** | Доставлено почтовому серверу получателя — **только провайдер с вебхуками** (AF-6) |
| **opened** | Сработал пиксель прочтения — письмо точно дошло и открыто |
| **failed** | Сбой до/во время передачи на SMTP; причина = текст в `error` («где застряло») |
| **bounced** | Отскок — **только провайдер с вебхуками** (AF-6) |

- Отправка асинхронная: `SendEmailJob` (`tries = 3`, `backoff = [30, 120, 600]` сек). Финальный сбой → `failed()` фиксирует `failed`. Сам Mailable читается из БД (`email_messages.mailable`, base64-serialize) → **повторная отправка** = повторный dispatch той же задачи по `id`.
- **Повтор из админки**: `POST /api/v1/emailDelivery/resend/{id}` (admin) — `failed`/`bounced` → `queued` (retry). Просмотр: `getList` (фильтр-whitelist `recipient`/`status`/`event`/`source`/`festival_id`/`aggregate_id` + пагинация), `getItem/{id}` (письмо + таймлайн `history`) — всё admin-only (содержит ПДн).
- `source` письма ∈ `qr_pipeline` (выдача qr-заказа) / `qr_intake` (S2S-приём от витрины) / `org_event` (внутренние события org). В истории `actor_type = qr` для qr-источников, `system` — для системных.

### Пиксель прочтения и 152-ФЗ

- Трекинг прочтения — прозрачный 1×1 GIF `GET /api/v1/mail/open/{token}.gif` (**публичный**, `throttle:120,1`). Загрузка картинки в почтовом клиенте → статус `opened` (идемпотентно, только из `sent`/`delivered`). Токен случайный (≠ `id` заказа) — заказы по нему не перебрать.
- **По умолчанию ВЫКЛЮЧЕН** за флагом `config('mail_delivery.open_tracking')` (env `MAIL_OPEN_TRACKING`, default `false`). Факт прочтения письма — обработка ПДн, поэтому включается только после ревью security-engineer (152-ФЗ).
- `delivered`/`bounced` требуют транзакционного email-провайдера с вебхуками (фича **AF-6**) — текущий «слепой SMTP» даёт статус только до `sent`. Пиксель — единственный текущий способ подтвердить «дошло».

> **ОТЛОЖЕНО:** старые org-письма (отмена/изменение через `ProcessUserNotification*`) пока идут **мимо** диспетчера (без трекинга) — будут подключены к `MailDispatcher` отдельно.

---

## История изменений статусов

| Дата | Что изменено | Файл |
|------|--------------|------|
| 2026-04-12 | Разделены матрицы переходов для обычных и живых билетов. `NEW_FOR_LIVE` → `PAID_FOR_LIVE` (не `PAID`) | `Shared/Domain/ValueObject/Status.php` |
| 2026-04-12 | Добавлен `PAID_FOR_LIVE` в описание правил смены статуса | `.qwen/docs/BUSINESS_RULES.md` |
| 2026-05-04 | Добавлены статусы списков: `NEW_LIST`, `APPROVE_LIST`, `CANCEL_LIST`, `DIFFICULTIES_AROSE_LIST` + матрица переходов. Роль `curator`. Сущность `Location`. | `Shared/Domain/ValueObject/Status.php`, `AccountRoleHelper.php`, миграции |
| 2026-06-14 | Добавлен канал приёма qr-заказов (таблица `qr_orders`, `actor_type = qr`, выдача билетов при `оплачен`). | `Backend/src/QrOrder/`, `History/Domain/ActorType.php` |
| 2026-06-17 | Система отправки писем по шаблонам: каталог событий `EmailEvent`, привязка шаблона по событию (ось `event` в `template_bindings`), машина статусов `EmailStatus` + контроль доставки (`email_messages`, история `aggregate_type=email`), qr-канал писем (S2S), пиксель прочтения за флагом (152-ФЗ), шаги пайплайна qr (`step_*` в истории). | `Backend/src/EmailDelivery/`, `template_bindings.event`, миграции `2026_06_17_*` |

---

## ⚠️ Кандидаты на вынос (org → admin-only, переезд на qr.spaceofjoy.ru)

> **Контекст:** org становится **внутренней admin-only системой**. Публичная покупка билетов (оформление заказа, оплата/биллинг, публичное заполнение анкет гостями) переезжает на витрину **qr.spaceofjoy.ru**. Правила ниже **НЕ удалять сейчас** — удалить в релизе после cutover на qr. Консервативно: спорное помечено как «уточнить».

| Раздел бизнес-правил | Статус | Примечание |
|----------------------|--------|------------|
| §1 «Авто-одобрение (без авторизации)» через `AutoPayment` | ⚠️ Кандидат на вынос (уточнить) | Привязано к публичному `POST /api/v1/order/create`. После переезда оформления на qr — приём идёт через `qrOrder/create` (S2S). Уточнить, остаётся ли auto-payment flow на org. |
| §3 «Оплата (Billing)» — СБП, webhook | ⚠️ Кандидат на вынос (уточнить) | Биллинг/оплата — ценность витрины qr. Вероятно уезжает на qr. Уточнить перед удалением. |
| §6 «Анкеты гостей» — публичное заполнение (рассылка ссылок, публичные формы) | ⚠️ Кандидат на вынос (уточнить) | Публичный flow заполнения может уехать на qr вместе с оформлением заказа. Одобрение анкет (admin) остаётся на org. |
| §4 «Волны ценообразования / типы билетов» — отображение на публичной витрине | ⚠️ Кандидат на вынос (уточнить) | Каталог (фестивали/типы/цены/опции) остаётся **мастером на org** (см. pivot 2026-06-13). Уезжает только публичное **отображение** на витрине qr. Сами правила ценообразования остаются. |

**Остаются на org** (admin-only): §1 матрица переходов и смена статуса админом/менеджером, §2 промокоды (CRUD), §7 роли, §12 заказы-списки (создание куратором + одобрение), QR-заказы (приём + admin-просмотр).
