# Комментарии к заказу — полноценный тред (кто/когда/что), онлайн

**Статус:** план (2026-06-21). Запрос владельца + ответы на уточнения (сессия PM 2026-06-21).
**Связано:** `.claude/specs/baza-shift-scheduling.md` (PR-E история заказа), org↔Baza S2S каналы (Ф3 ingest, Ф4 webhook), поиск без QR.

## Цель
Комментарий к заказу — **операционная заметка** (напр. «VIP, проводить до сцены»), которую читает персонал на КПП. Превращаем из одного поля в **полноценную переписку**: несколько комментариев на заказ, у каждого **кто** оставил, **когда**, **из какого источника**; видна **вся история** треда.

## Решения владельца
- **Полноценный тред** с авторством (кто/когда). 
- **Только онлайн** (пока). Офлайн-снимок/`ticket_search` комментариями НЕ грузим.
- **Вся история переписки** видна.
- **История заказа нужна** (комментарии — часть таймлайна заказа).
- **Писать можно:** админка org (admin/manager), КПП Baza (**любой персонал смены**), авто от витрины qr.

## Текущее состояние (что уже есть)
- org: таблица `comment` (id, **user_id** FK users, order_tickets_id FK, comment, **is_checkin**, **timestamps**) — уже тред с автором/временем, но строки создаются **только** при статусе `DIFFICULTIES_AROSE` (`AddComment::send`). Письмо «трудности» комментарий показывает ✅.
- qr: комментарий в `payload.order_data.comment`.
- Baza online-поиск по `el_tickets.comment` (классика) ✅.

## Архитектура
**org `comment` = единственный источник правды** (тред заказа). Baza/qr пишут и читают его **онлайн** (S2S), без дублирующего хранилища в Baza (online-only → синк не нужен).

### Эволюция таблицы `comment` (миграция, backward-compatible)
- `user_id` → **nullable** (автор-неorg: baza/qr/system).
- `+ author_name` string nullable — отображаемое имя автора (org-юзер / ФИО персонала Baza / «qr»).
- `+ author_source` string — `org_user` / `baza` / `qr` / `system`. Бэкафилл существующих строк → `org_user`.
- `is_checkin` сохраняем (существующая семантика). Тред — append-only (правок/удалений нет).

### Поверхности
| Источник | Запись | Чтение | Автор |
|----------|--------|--------|-------|
| Админка org (admin/manager) | `POST /api/v1/order/{id}/comment` | `GET /api/v1/order/{id}/comments` | `org_user`, user_id=Auth, author_name=имя |
| Витрина qr | при приёме/выдаче заказа `order_data.comment` → строка треда | (через org) | `qr`, user_id=null |
| КПП Baza (любой персонал смены) | S2S → org `POST /api/v1/baza/order/{id}/comment` (X-Baza-Token) | S2S → org `GET` + показ в карточке скан/поиск | `baza`, author_name=ФИО персонала (из payload) |

### История
Каждый комментарий → событие `comment_added` в `domain_history` (`aggregate_type=order` — как все события заказа: `OrderStatusChangedEvent`/`OrderCreatedEvent`/…; `getHistory` читает по `aggregateId=orderId`), чтобы тред был виден в таймлайне заказа (PR-E). Payload без ПДн (`{source, has_text, length}` — текст комментария НЕ кладётся).

### Поиск (online-only)
- Классика: `el_tickets.comment` LIKE — уже работает.
- qr: довести комментарий заказа до `el_tickets.comment` (online-поиск qr-билетов по комментарию). Офлайн-индекс `ticket_search` комментариями **не** грузим (решение «online-only»).

## Фазы (PR)
| PR | Содержимое | Зависимости |
|----|-----------|-------------|
| **C1 — Ядро org** | Миграция `comment` (nullable user_id + author_name + author_source + backfill); обновить `CommentForOrder`/DTO/репозиторий; CQRS `AddComment` обобщить (не только difficulties) + `ListOrderComments` (query); `POST /api/v1/order/{id}/comment` (admin/manager) + `GET /api/v1/order/{id}/comments` (admin); событие истории `comment_added`; тесты. Существующий difficulties-флоу не ломаем. | — |
| **C2 — qr** | Комментарий заказа qr → строка треда (author_source=qr) при приёме/выдаче; довести qr-comment до `el_tickets.comment` (online-поиск). | C1 |
| **C3 — КПП чтение** | S2S `GET` треда org←Baza (online) + показ треда в карточке скан/поиск Baza (охранник читает заметку). | C1 |
| **C4 — КПП запись** | S2S `POST` комментария из КПП (любой персонал смены; Baza гейтит локально) → org. author_source=baza, author_name=ФИО. | C1, C3 |
| **C5 — UI org** | Тред в карточке заказа админки (список кто/когда/что + поле «добавить»). | C1 |

## Открытые вопросы (не блокируют C1)
- qr может оставлять НЕСКОЛЬКО комментариев (S2S от витрины) или только один из заказа? (C2 — пока один из `order_data.comment`).
- Гейтить ли чтение треда на КПП правом (тред — операционная инфа, не ПДн гостя; вероятно видно всем на смене). 
- Редактирование/удаление комментариев — НЕ предусмотрено (append-only); ок?
