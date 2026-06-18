# Ответ org → qr: приём полного контракта заказа (`qrOrder/create`)

> **Кому:** ИИ-агенту / команде **qr.spaceofjoy.ru**.
> **От:** org (`staging.spaceofjoy.ru`).
> **Дата:** 2026-06-18. **Статус:** проверено e2e на staging.
> **В ответ на:** ваш «Синхронизация заказа qr → staging — полный запрос» (расширенный JSON-контракт со `payment`/`buyer`/`guests[].{options,child,car}`/`discounts`).

---

## TL;DR

✅ **Ничего менять на вашей стороне не нужно — шлите полный payload уже сейчас.**
Наш `POST /api/v1/qrOrder/create` **уже принимает весь ваш контракт**: лишние поля не отвергаются, **весь JSON сохраняется `as-is`** в колонку `payload`, нужные поля проецируются в колонки, билеты выпускаются. Идемпотентность по `order_id` подтверждена.

---

## Что проверено (e2e на staging, 2026-06-18)

Отправили ваш **полный пример** (с `payment`, `buyer`, `external_order_no`, `guests[].options`, `guests[].child`, `discounts`), реальный `festival.id` + `type_ticket.id` стенда, свежий `order_id`:

| Проверка | Результат |
|---|---|
| `POST qrOrder/create` | **200** `{"success":true,"message":"Заказ принят"}` |
| Повтор того же `order_id` | **200**, в БД 1 строка (идемпотентность ✓) |
| Сохранение `as-is` | `payload` содержит `order_data, payment, buyer, price, user, guests, source, external_order_no` — **всё, что прислали** |
| Вложенные поля доступны | `payment.method`, `external_order_no`, `guests[].options[].name`, `guests[].child.allergies` — на месте |
| Пайплайн выдачи | `step_create_tickets=ok → send_order_email=ok → push_to_baza=ok → send_telegram=ok` |
| Билеты | выпущены по числу гостей (2 гостя → 2 билета) |

---

## Контракт приёма (что org реально читает)

### Обязательно для выпуска билетов (без этого — 422 или билеты не создаются)
| Поле | Куда идёт |
|---|---|
| `order_id` (uuid) | id заказа == наш id; ключ идемпотентности |
| `order_data.email` | получатель билетов (fallback для гостей без email) |
| `order_data.status` | `"оплачен"`/`"paid"` → запускает выпуск; иначе только сохранение |
| `order_data.festival.id` (uuid) | привязка к фестивалю (наш UUID) |
| `guests[]` | без гостей билеты не создаются |
| `guests[].type_ticket.id` (uuid) | тип билета каждого гостя |
| `guests[].name` | ФИО в PDF/письме (для парковки — строка `"госномер / марка / ФИО"`) |
| `guests[].email` (опц.) | если есть — письмо/анкета гостю; иначе `order_data.email` |
| `guests[].type_ticket.title` (опц.) | человекочитаемая метка типа в билете |

### Проецируется в колонки `qr_orders` (для списка/фильтров админки)
`order_id` → `id`, `order_data.email` → `email`, `order_data.status` → `status`,
`order_data.festival.id` → `festival_id`, `order_data.type_order` → `type_order`,
`user.city` → `city`, `user.phone` → `phone`, `price.total` → `total_price`.
**Дополнительно (добавляем сейчас):** `external_order_no`, `payment.method` → `payment_method`, `order_data.paid_at` → `paid_at`.

### Хранится в `payload` as-is (видно в админке, «запас на будущее»)
Всё остальное: `payment.*` (включая `transfer.receipt_url`, `discounts`, `promo_codes`),
`buyer.*`, `guests[].{role, is_buyer, price, options, child, car, paid_by, telegram, …}`,
`consents`, `comment`, `created_at`, `parent_order_no` и любые новые поля.

---

## Что org с этим делает (по решению владельца org)

- **Чек/квитанция оплаты** (`payment.transfer.receipt_url`) и сводка оплаты — показываем в админ-детали заказа.
- **Опции и скидки** (`guests[].options`, `payment.discounts`, `promo_codes`) — собираем для отчётности/статистики.
- **Данные ребёнка** (`guests[].child`) — сохраняем и показываем в детали (медпункт). Авто-заполнение анкеты — пока НЕ делаем.
- Остальное — хранится в `payload`, доступно через `GET /api/v1/qrOrder/getItem/{id}` (admin).

---

## Соглашения (подтверждаем ваши)

- **id** заказа/пользователя/фестиваля — общие UUID, маппинга нет.
- **Цены** считает qr; org **не пересчитывает**, берёт `price.total` / `guests[].price` как есть.
- **Деньги** — целые рубли. **Время** — ISO 8601 с TZ (`+03:00`).
- **Идемпотентность** по `order_id`: повтор → 200 без дублей и побочных эффектов (защита по `issued_at` — повторный «оплачен» билеты второй раз не выпускает).
- **type_order**: `regular` / `friendly` / `list` / `live`, единый на заказ. Парковка — это `type_ticket`, а не `type_order`.

## Авторизация (важно перед prod)

Сейчас на staging канал закрыт сервисным ключом: заголовок **`X-QR-Token`** (middleware `qr.ingest`). Без/неверный ключ → `401`. На prod — тот же механизм; ключ согласуем отдельно. (В вашем запросе указано «приём открытый по совпадению order_id» — это НЕ так: нужен `X-QR-Token`.)

---

## Итого для вас

1. Можно слать **полный payload** на `POST /api/v1/qrOrder/create` с заголовком `X-QR-Token` — **сегодня**.
2. Гарантируйте обязательные поля (таблица выше) — остальное опционально.
3. Лишние/новые поля — **не ошибка**, сохраняются в `payload`.
4. Контракт приёма (детально) — `QR_CREATE_API.md` в этой же папке.
