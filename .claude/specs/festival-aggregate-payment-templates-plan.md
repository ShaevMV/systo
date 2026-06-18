# План: Фестиваль-агрегат + перенос CRUD в админку + шаблоны по типу оплаты

**Статус:** черновик-план (ждёт подтверждения владельца по открытым вопросам)
**Создан:** 2026-06-18
**Источник:** сессия добавления CRUD фестиваля + сообщения владельца в этой сессии.
**Связанные спеки:** `admin-frontend-vite-sakai.md` (AF-1), `template-system.md` / `template-aggregate-and-bindings.md` (AF-3), `email-delivery-system.md`.

---

## Контекст (что уже сделано в этой сессии)

Добавлен **полный CRUD каталога фестивалей** по образцу `Location` (БД только в репозитории, CQRS):
- `POST /api/v1/festival/getList` (публичный, фильтры name/year/active + orderBy)
- `GET /api/v1/festival/getItem/{id}` (публичный)
- `POST /api/v1/festival/create` (admin) — был ранее
- `POST /api/v1/festival/edit/{id}` (admin)
- `DELETE /api/v1/festival/delete/{id}` (admin, **soft delete** — `festivals.deleted_at`)

Код лежит под `Backend/src/Order/OrderTicket/` (рядом с существующим `create`), как **пассивная сущность** (без AggregateRoot). Тесты: `FestivalCrudApiTest` (12) + `FestivalCreateApiTest` (4), полный прогон Backend **400 зелёных**.

### Что уже есть в коде по «типам оплаты» (важно для T3/T4)

`types_of_payment` уже содержит (легаси-механизм, до новой системы шаблонов):
- `email` (string, comment «Шаблон письма») — per-payment-type slug письма, читается как `emailPayView` в `InMemoryMySqlTicketsRepository::getTicket`
- `pdf` (string) — per-payment-type шаблон PDF
- `user_external_id` (uuid, «Связь с продавцом или реализатором») — **ссылка на внешнего продавца/магазин**
- `ticket_type_id` (uuid), `card`, `is_billing`, `active`, `sort`

То есть «особое письмо/PDF на тип оплаты» частично существует, но **мимо** новой системы `template_bindings` (AF-3). Задача — свести это в единый механизм привязок и добавить адрес магазина.

---

## Задачи

### T1 — Festival → AggregateRoot + история (backend) ✅ Сделано (2026-06-18)

> **Сделано:** агрегат `Festival` в новом модуле `Backend/src/Festival/Domain/Festival.php` + `HasHistory`; события `festival_created` (payload name/year/active) / `festival_edited` (payload changed-поля) / `festival_deleted` в `History/Domain/Event/`; запись в `domain_history` (`aggregate_type='festival'`, `actor_type=user`, `actor_id=Auth::id()`) на create/edit/delete; `GET /api/v1/festival/getHistory/{id}` (admin). Тест `FestivalHistoryApiTest` (5), полный прогон Backend **405 зелёных**.
> **Остаётся (отдельный рефакторинг, не входит в T1):** перенос CRUD/репозитория/DTO из `Order/OrderTicket/` в модуль `Festival/` — рискованно (репозиторий шарят заказы/письма/цены), делать отдельным верифицированным PR.

**Было:** festival CRUD — пассивная сущность под `Order/OrderTicket`.
**Сделали:** `Festival` — полноценный **AggregateRoot** с трейтом `HasHistory` (как `OrderTicket` / `Template`):
- Фабричные методы `create` / `edit` / `delete` (и, возможно, `activate/deactivate`) пишут события в `domain_history` (`aggregate_type = 'festival'`, `actor_type = user`, `actor_id = Auth::id()`).
- События `FestivalCreatedEvent` / `FestivalEditedEvent` / `FestivalDeletedEvent` (по образцу `Template*Event` в `History/Domain/Event/`).
- Эндпоинт `GET /api/v1/festival/getHistory/{id}` (admin) — журнал изменений (как `template/history`, `order/getHistory`).
- Решить: вынести в **отдельный модуль** `Backend/src/Festival/` (DDD-структура) или добавить AggregateRoot **на месте** под `Order/OrderTicket/`. → **Открытый вопрос 3.**

**Оценка:** M. **Зависит от:** CRUD этой сессии. **Версия (предложение):** v2.7.0/v2.8.0.

---

### T2 — Перенос CRUD-экранов в новую админку AdminFront (Vite + PrimeVue Sakai)

**Нужно:** перенести/создать в `AdminFront/` CRUD-экраны по образцу `DataTablePage` / `useCrud` (фаза AF-1):
- **Фестивали** (`festival/*` — бэкенд готов в этой сессии)
- **Типы билетов** (`ticketType/*` — бэкенд есть)
- **Опции** (`Option` / `option/*` — бэкенд есть, v2.6.0)
- **Типы оплаты** (`typesOfPayment/*` — бэкенд есть)

Преимущественно **фронтовая** работа. Где нужно — стандартизовать backend `getList` (фильтры/orderBy) по образцу `location`/`festival`.

**Оценка:** L (дробится по экранам). **Часть:** AF-1. **Версия:** v2.7.0–v2.9.0.

---

### T3 — Привязка шаблонов писем/PDF по типу оплаты (расширение AF-3)

**Нужно:** добавить ось **`types_of_payment_id`** в `template_bindings` (как ранее добавили ось `event`):
- Миграция: `template_bindings.types_of_payment_id` (uuid, nullable = wildcard).
- `TemplateBindingResolver`: учесть payment-type в специфичности. Текущая ось: `event` > `ticket_type` > `order_type` > `festival`. Определить вес `types_of_payment` (вероятно сразу после `event`/`ticket_type` — обсудить). → **Открытый вопрос 4.**
- `create`/`edit` привязки принимают `types_of_payment_id`; UI-селектор в экране «Привязки шаблонов».
- Точки применения:
  - выдача билета (`InMemoryMySqlTicketsRepository::getTicket`) — заменить/совместить с легаси `types_of_payment.email`/`pdf` (`emailPayView`).
  - выбор slug писем (`MailDispatcher` / `EmailContext`) — добавить `paymentTypeId` в контекст письма.
- **Миграция легаси:** перенести существующие `types_of_payment.email`/`pdf` в привязки `template_bindings` (или оставить как fallback). → **Открытый вопрос 5.**

**Оценка:** M. **Зависит от:** AF-3 (template_bindings, готов). **Версия:** v2.8.0.

---

### T4 — Спец-письмо внешних продавцов с адресом магазина

**Бизнес:** оплаты приходят от внешних продавцов/магазинов; для них нужно **особое письмо с адресом этого магазина**.
**Нужно:**
- **Данные магазина** (название/адрес/контакты/ссылка) — где хранить:
  - на внешнем продавце (`types_of_payment.user_external_id` → пользователь-продавец), или
  - прямо на `types_of_payment`, или
  - отдельная сущность «магазин/продавец». → **Открытый вопрос 1 и 2.**
- Спец **email-шаблон**, привязанный по типу оплаты (через T3).
- **Плейсхолдеры адреса магазина** в `PlaceholderCatalog` + проброс в `vars` письма (через `EmailContext`/`MailDispatcher`).

**Оценка:** M. **Зависит от:** T3. **Версия:** v2.8.0 (пара к AF-3/AF-6).

---

## Открытые вопросы (нужно подтверждение владельца)

1. **«Адрес магазина»** — это почтовый адрес, ссылка (URL) на магазин, или набор полей (название + адрес + телефон + ссылка)? Что выводить в письме?
2. **Привязка магазина** — к **типу оплаты** (`types_of_payment`) или к **продавцу** (`user_external_id` → пользователь)? Один тип оплаты = один магазин, или у типа оплаты несколько продавцов/магазинов?
3. ~~**Festival-агрегат** — новый модуль или на месте?~~ → **Решено (2026-06-18):** класс агрегата — в новом модуле `Backend/src/Festival/Domain/`. CRUD/репозиторий пока под `Order/OrderTicket/`; полный перенос — отдельный рефакторинг.
4. **Вес оси `types_of_payment`** в резолвере привязок относительно `event`/`ticket_type`/`order_type`/`festival`.
5. **Легаси `types_of_payment.email`/`pdf`** — мигрировать в `template_bindings` и удалить, или оставить как fallback до cutover?

---

## Сводка по версиям (предложение, требует go владельца)

| Задача | Что | Оценка | Версия |
|--------|-----|--------|--------|
| **T1** | Festival → AggregateRoot + история + getHistory ✅ **done (2026-06-18)** | M | — |
| **T2** | Перенос CRUD (festival/ticketType/option/typesOfPayment) в AdminFront | L | v2.7.0–v2.9.0 (AF-1) |
| **T3** | Ось `types_of_payment` в `template_bindings` + резолвер + UI | M | v2.8.0 (AF-3) |
| **T4** | Спец-письмо внешних продавцов с адресом магазина | M | v2.8.0 (AF-3/AF-6) |
