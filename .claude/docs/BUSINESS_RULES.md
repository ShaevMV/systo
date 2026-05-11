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

---

## 2. Промокоды

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

## История изменений статусов

| Дата | Что изменено | Файл |
|------|--------------|------|
| 2026-04-12 | Разделены матрицы переходов для обычных и живых билетов. `NEW_FOR_LIVE` → `PAID_FOR_LIVE` (не `PAID`) | `Shared/Domain/ValueObject/Status.php` |
| 2026-04-12 | Добавлен `PAID_FOR_LIVE` в описание правил смены статуса | `.qwen/docs/BUSINESS_RULES.md` |
| 2026-05-04 | Добавлены статусы списков: `NEW_LIST`, `APPROVE_LIST`, `CANCEL_LIST`, `DIFFICULTIES_AROSE_LIST` + матрица переходов. Роль `curator`. Сущность `Location`. | `Shared/Domain/ValueObject/Status.php`, `AccountRoleHelper.php`, миграции |
