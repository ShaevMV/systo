# FUNCTION_MAP.md — Карта функций qr / org (Systo) / BAZA

> **Статус:** DRAFT · **Дата:** 2026-06-13
> **Контекст:** гибридная целевая модель (см. `.claude/meetings/2026-06-13/RESULTS.md`).
> **Аудитория:** команда Systo (org/BAZA) и координация с командой `qr.spaceofjoy.ru` (отдельный репозиторий, Python).
> **Важно:** код `qr` (Python, сервер в РФ) **недоступен**. Всё, что относится к стороне `qr`, основано на решениях совещания 2026-06-13, а не на коде, и помечено как допущение/открытый вопрос.

---

## Executive Summary

Цель — зафиксировать **границы ответственности** трёх компонентов в гибридной модели и явно показать, что переезжает, что остаётся и что дублируется. Это «бумажная» фаза (боевой код — после релиза v2.6.0).

**Распределение ролей (гибрид):**

- **qr (Python, РФ) = мастер коммерции.** Витрина, UX заказа, расчёт цены витрины, применение промокода, оплата (биллинг-UX, СБП-ссылки, платёжный шлюз). qr публикует факт оплаты событием.
- **org (Systo, Backend) = мастер билетов и каталога.** Генерация PDF/QR, доставка email, НЕ-фискальный чек, каталог (фестивали/типы/волны цен/опции), жизненный цикл и матрица статусов заказа, анкеты гостей, внутренние продажи (пушер/куратор/список), IdP. org консьюмит событие `OrderPaid` от qr и **валидирует каждую команду qr**.
- **BAZA = мастер входа/кассы.** Сканирование билета, отметка прохода, кассы/смены/отчёты, офлайн-синхронизация. Автономна офлайн в день фестиваля.

**Три критичных направления доработки (🔴), которые определяют разговор с qr-стороной:**

1. Платёжный webhook `/v1/order/succes` сегодня — `Route::any` **без проверки источника, подписи и idempotency**. Любой POST может перевести заказ в `PAID` и выпустить билеты. Подтверждено в коде (`Backend/routes/order.php`).
2. Авто-одобрение заказа через статичный bearer-токен `AutoPayment` нужно заменить на подписанное событие `OrderPaid` с нейтральным конвертом.
3. Прямая запись org → БД BAZA (`DB::connection('mysqlBaza')` с хардкод-кредами `root/common404`, подтверждено в `Backend/config/database.php`) должна быть выведена на аутентифицированный API/событие.

Транспорт интеграции: **MySQL transactional outbox + подписанный HTTP-webhook (push) + idempotency**. RabbitMQ не используется (в проде отсутствует, в коде `QUEUE_CONNECTION` по умолчанию `sync`, рабочая очередь — `database`). Детали контракта и security — в `CONTRACT_RFC_v0.md`.

---

## Условные обозначения

- **СЕЙЧАС** — где функция живёт в коде сегодня.
- **ДОЛЖНО** — кто владеет в гибридной целевой модели.
- 🟢 остаётся на месте · 🔵 переезжает в qr · 🟡 дублируется/синхронизируется · 🔴 требует доработки (подпись / idempotency / API)

---

## Сводная таблица

| Функция | СЕЙЧАС (код, путь) | ДОЛЖНО (qr / org / BAZA) | Движение |
|---|---|---|---|
| **Витрина / каталог UI покупки** | org: `FrontEnd` SPA + `Festival/load` (`Backend/app/Http/Controllers/Festival/FestivalController.php`) | **qr** — витрина и UX заказа | 🔵 переезд UX в qr; org остаётся источником данных каталога через API |
| **Создание заказа (гость)** | org: `OrderTickets::create` (`Backend/app/Http/Controllers/TicketsOrder/OrderTickets.php`) → `CreateOrder::createAndSave` | **qr** создаёт заказ → событие в **org**; org валидирует и материализует заказ-билеты | 🔵 🔴 точка входа в qr; org консьюмит `OrderPaid`, валидирует каждую команду |
| **Создание заказа (Friendly / пушер)** | org: `OrderTickets::createFriendly`, роли `pusher` / `pusher_curator` | **org** (внутренняя продажа пушером, офлайн-устойчиво) | 🟢 остаётся в org (не витринный поток) |
| **Создание заказа-списка (куратор)** | org: `OrderTickets::createList`, роли `curator` / `pusher_curator` | **org** | 🟢 остаётся в org |
| **Расчёт цены / live-калькулятор** | org: `OrderPriceCalculator` (`Backend/src/Order/OrderTicket/Application/Pricing/OrderPriceCalculator.php`), `OrderTickets::calculatePrice` | **qr** считает цену витрины; **org** пересчитывает/валидирует при материализации (инварианты, лимиты) | 🟡 дублируется: qr — для UX, org — источник истины при приёме события |
| **Промокод (валидация / применение)** | org: `PromoCodeController` (`Backend/app/Http/Controllers/PromoCode/PromoCodeController.php`) + `OrderPriceCalculator` (per-guest, скидка только от базы) | **qr** (мастер коммерции — промо у заказа) | 🔵 переезд в qr; org валидирует факт скидки при приёме события; промокоды-агрегатор (план v2.7.0 в org) — пересмотреть |
| **Оплата / биллинг-UX (СБП-ссылки)** | org: код есть, но **route отсутствует** — `BillingService::createPayments`, `CreatingLinkForPayCommandHandler` НЕ подключены к маршруту (мёртвый код) | **qr** (полностью владеет биллинг-UX и платёжным шлюзом) | 🔵 переезд в qr; в org — удалить мёртвый Billing-link код после переноса |
| **Webhook оплаты → смена статуса** | org: `Route::any('/v1/order/succes', BillingController::webHook)` (`Backend/routes/order.php`) → `WebHookCommandHandler` (`Backend/src/Billing/Application/WebHook/WebHookCommandHandler.php`) | **qr публикует `OrderPaid`** → **org консьюмит** (подписанный webhook + idempotency) | 🔴 критично: сейчас `Route::any` без проверки источника, без подписи, без idempotency-ключа → ввести подпись + idempotency |
| **Авто-одобрение заказа (внешний источник)** | org: заголовок `AutoPayment` в `OrderTickets::create`, `hash_equals` со статичным токеном из конфига | **qr → org** через подписанное событие `OrderPaid` (вместо общего bearer-токена) | 🔴 заменить статичный токен на конверт события `{schema_version, trace_id, idempotency_key, occurred_at, payload}` + подпись |
| **Генерация билета (PDF + QR)** | org: `ProcessCreatingQRCode` (`Backend/src/Ticket/CreateTickets/Domain/ProcessCreatingQRCode.php`), `CreatingQrCodeService`, очередь `database` | **org** (мастер билетов) | 🟢 остаётся в org |
| **Доставка билетов (email)** | org: `app/Mail/OrderToPaid.php`, `OrderToPaidFriendly.php`, `OrderListApproved.php` и др. через Domain Events (async) | **org** | 🟢 остаётся в org |
| **Чек (PDF / email, НЕ фискальный)** | org: ссылка на чек кладётся в комментарий заказа — `WebHookCommandHandler::insertLink`; чек-письмо в `OrderToPaid` flow | **org** формирует и доставляет НЕ-фискальный чек; **qr** владеет фискальной частью (если она есть на стороне шлюза) | 🟢 НЕ-фискальный чек в org; 🔵 фискальный чек / ОФД — у qr (открытый вопрос) |
| **Каталог: фестивали** | org: `FestivalController` (`getFestivalList`, `load`), `Backend/src/Festival/` | **org** (мастер каталога) → отдаёт qr по API | 🟢 остаётся в org; 🟡 qr читает каталог через API |
| **Каталог: типы билетов + волны цен** | org: `TicketTypeController`, `TicketTypePriceController`, `Backend/src/TicketTypePrice/` | **org** | 🟢 остаётся в org; 🟡 публикуется в qr |
| **Каталог: опции** | org: `OptionController` (`Backend/app/Http/Controllers/Option/OptionController.php`), `Backend/src/Option/` | **org** | 🟢 остаётся в org; 🟡 публикуется в qr |
| **Способы оплаты (`types_of_payment`)** | org: `TypesOfPaymentController`, флаг `is_billing` (семантика «внешний заказ с авто-подтверждением») | **qr** владеет способами оплаты на витрине; **org** хранит справочник для внутренних потоков (пушер/список) | 🟡 расходится: на витрине — qr, во внутренних потоках org — org |
| **Вход / сканирование билета** | BAZA: `Baza/scr/Tickets/Applications/Scan/*` (El/Spisok/Live/Parking/Friendly), `Api/ScanController::search` (`Baza/routes/api.php`) | **BAZA** (автономно офлайн в день фестиваля) | 🟢 остаётся в BAZA |
| **Отметка прохода (enter)** | BAZA: `EnterTicket` (`Baza/scr/Tickets/Applications/Enter/*`), `Api/ScanController::enter` | **BAZA** | 🟢 остаётся в BAZA |
| **Касса / смены / отчёты** | BAZA: `Baza/scr/Changes/*` (`SaveChange`, `CloseChange`, `ReportForChanges`, `AddTicketsInReport`), `ChangesController` | **BAZA** | 🟢 остаётся в BAZA |
| **Анкеты гостей** | org: `QuestionnaireController` (`Backend/app/Http/Controllers/Questionnaire/`), события `ProcessGuestNotificationQuestionnaire` | **org** | 🟢 остаётся в org |
| **История / статусы заказа** | org: `Status` VO (`Shared/Domain/ValueObject/Status.php`), `domain_history`, `ChangeStatus` (`Backend/src/Order/OrderTicket/Application/ChangeStatus/`) | **org** (мастер жизненного цикла билета); смена статуса оплаты приходит событием от qr | 🟢 матрица статусов в org; 🔵 событие оплаты — от qr |
| **Синхронизация org → BAZA (онлайн)** | org: прямая запись в БД BAZA — `setInBaza` / `setInBazaList` / `setInBazaLive` (`Backend/src/Ticket/CreateTickets/Repositories/InMemoryMySqlTicketsRepository.php`), `setInBazaAuto` (`Backend/src/Auto/Repositories/InMemoryMySqlAutoRepository.php`), коннект `DB::connection('mysqlBaza')` | **org → BAZA через аутентифицированный API/событие** (без прямого доступа к чужой БД и хардкод-кредов) | 🔴 вынести прямую кросс-БД запись на API/событие с подписью |
| **Синхронизация org ↔ BAZA (офлайн)** | BAZA: NDJSON import/export — `Baza/scr/Sync/*`, `SyncController`, таблицы `changes` / `el_tickets` / `live_tickets` / `parking_tickets` / `spisok_tickets` / `autos` | **org → BAZA** офлайн-bundle (день фестиваля) | 🟢 остаётся; механизм офлайн-пакета сохраняем |
| **IdP / аутентификация (SSO)** | org/BAZA: `Backend/app/Http/Controllers/AuthController.php` (JWT), `Baza` (sanctum / web) | **org/BAZA = IdP**, **qr = PKCE-клиент** (по `.claude/specs/qr-sso-security.md`) | 🟢 IdP остаётся в org/BAZA; 🔵 qr — клиент |
| **Очереди / async-доставка событий** | org: `QUEUE_CONNECTION=database` (`Backend/config/queue.php`, `default=sync`, `failed.driver=database-uuids`); единственный `onConnection('sync')` в `ChangeStatusCommandHandler`; RabbitMQ в активном пути НЕ используется | **MySQL transactional outbox + подписанный HTTP-webhook + idempotency** | 🔴 RabbitMQ — мёртвый код (в проде нет); очереди оставить на MySQL, добавить outbox для интеграции с qr |

---

## Что переезжает (org → qr)

- Витрина и UX заказа, расчёт цены витрины, применение промокода, биллинг-UX (СБП-ссылки), способы оплаты на витрине.
- Мёртвый код биллинг-ссылок в org (`BillingService::createPayments`, `CreatingLinkForPayCommandHandler`) — удалить **после** переноса.

## Что остаётся

- **org:** генерация PDF/QR, доставка email, НЕ-фискальный чек, каталог (фестивали / типы / волны / опции), жизненный цикл и матрица статусов, анкеты, внутренние продажи (пушер / куратор / список), IdP.
- **BAZA:** вход / скан, отметка прохода, касса / смены / отчёты, офлайн-sync — автономно в день фестиваля.

## Что дублируется / синхронизируется

- **Расчёт цены:** qr (для UX) + org (источник истины при приёме события — org валидирует каждую команду qr).
- **Каталог:** мастер в org, публикуется / читается в qr (нужен контракт публикации).
- **Способы оплаты:** на витрине — qr, во внутренних потоках — org.
- **org → BAZA:** онлайн (прямая запись сейчас → API/событие в цели) + офлайн (NDJSON, сохраняем).

---

## Критичные точки доработки (🔴) — основа разговора с qr-стороной

1. **Webhook `/v1/order/succes`** (`Route::any`, без подписи / idempotency) — ввести подпись источника + idempotency-ключ. Сейчас любой может перевести заказ в `PAID` и выпустить билеты. *(Подтверждено в `Backend/routes/order.php`.)*
2. **Авто-одобрение `AutoPayment`** — статичный bearer-токен заменить на подписанное событие `OrderPaid` с нейтральным конвертом `{schema_version, trace_id, idempotency_key, occurred_at, payload}`.
3. **Прямая запись org → БД BAZA** (`DB::connection('mysqlBaza')`, креды хардкодом) — вынести на аутентифицированный API/событие. *(Подтверждено в `Backend/config/database.php`: `username='root'`, `password='common404'`.)*
4. **Интеграция qr → org** — направление push (qr публикует `OrderPaid`, org консьюмит) через MySQL transactional outbox + подписанный webhook + idempotency. НЕ RabbitMQ.

---

## Допущения (DRAFT)

1. Код qr (Python, РФ) недоступен — все утверждения про qr-сторону основаны только на RESULTS.md 2026-06-13, не на коде.
2. Биллинг-UX (создание СБП-ссылок) считается переезжающим в qr, потому что в org соответствующий код (`BillingService::createPayments`, `CreatingLinkForPayCommandHandler`) существует, но НЕ подключён ни к одному маршруту — фактически уже не используется в org.
3. Флаг `types_of_payment.is_billing` в org означает «внешний заказ с авто-подтверждением, не запрашивать данные оплаты» (см. project memory `project_billing_semantics`), а не реальный платёжный шлюз.
4. org остаётся мастером каталога (фестивали / типы / опции), т.к. весь CRUD каталога и логика волн цен живут в org; qr-сторона потребляет каталог. **Не подтверждено qr-стороной.**
5. «Чек» в org сейчас = ссылка на чек в комментарии заказа + чек-письмо в `OrderToPaid` flow, и это **НЕ** фискальный документ — фискальная часть (если есть) на стороне платёжного шлюза / qr.
6. RabbitMQ в проде отсутствует и является мёртвым кодом (подтверждается `QUEUE_CONNECTION=database` и единственным `onConnection('sync')` в коде) — целевой транспорт интеграции выбран MySQL outbox + webhook.
7. Friendly-заказы и заказы-списки (пушер / куратор) остаются в org как внутренние, не-витринные потоки продаж — qr-сторона их не касается. **Не подтверждено.**
8. Анкеты гостей и генерация PDF/QR остаются в org, т.к. привязаны к жизненному циклу билета (мастер билетов = org + BAZA).
9. *(Уточнение ведущего по коду)* В текущем `Baza/app/Models/ElTicketsModel.php` поле `festival_id` **отсутствует** в `$fillable` (добавлено отдельной миграцией синхронизации со схемой прода, см. v2.5.1). При проектировании контракта org → BAZA это учитывать.

---

*Связанный документ: `CONTRACT_RFC_v0.md` — контракт события `OrderPaid`, конверт, консьюмер, контракт org → BAZA, транспорт и security-предусловия.*