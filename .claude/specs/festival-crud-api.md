# ORG — контракт CRUD каталога фестивалей (`/api/v1/festival/*`)

> **Статус:** актуально на 2026-06-18. Отражает **реально реализованный код** (ветка `feat/qr-order-pipeline-view`, коммит `f16b3253`).
> **Для кого:** ИИ-агент, который **интегрируется** с этими эндпоинтами или **строит экран** управления фестивалями в новой админке (`AdminFront/`, Vite + PrimeVue Sakai, задача AF-8).
> **Verified:** контракт извлечён из кода и подтверждён тестами `FestivalCrudApiTest` (12) + `FestivalCreateApiTest` (4); полный прогон Backend 400 зелёных.

---

## 1. Модель и назначение

| | |
|---|---|
| **Что это** | Каталог фестивалей — **мастер на org** (org = внутренняя admin-only система). Фестиваль — корневая сущность: к нему привязаны заказы, билеты, типы билетов, промокоды, локации. |
| **Архитектура** | Чистый CQRS/DDD: **БД только в репозитории**. Контроллер → `FestivalApplication` → Query/Command + Handler → `FestivalRepositoryInterface`. Паттерн — копия `Location`. |
| **Доступ** | **Чтение** (`getList`, `getItem`) — публичное (как у `location`). **Запись** (`create`, `edit`, `delete`) — только `admin` (JWT). |
| **Удаление** | **Soft delete** (`festivals.deleted_at`). Запись не теряется (фестиваль связан с заказами/билетами). Удалённый фестиваль **не попадает** в `getList`/`getItem`/`getFestivalList`. |
| **Поля сущности** | `id` (uuid), `name` (string), `year` (int, 2000..2100), `active` (bool). Колонка `view` в БД есть, но **в API не выводится и не редактируется**. |

> **Не путать с `GET /api/v1/festival/getFestivalList`** — это старый эндпоинт витрины (формат `{festivalDto:[…]}`, без фильтров). Для админ-CRUD используй `POST /getList` (ниже).

---

## 2. Базовый адрес и заголовки

```
Префикс: /api/v1/festival   (/api добавляет RouteServiceProvider, группа api)
```

| Среда | База |
|---|---|
| **staging** | `https://api.staging.spaceofjoy.ru` |
| **prod** | `https://api.spaceofjoy.ru` |
| **local** | `http://api.tickets.loc` |

| Заголовок | Когда | Зачем |
|---|---|---|
| `Content-Type: application/json` | POST | тело читается как JSON |
| `Accept: application/json` | всегда (реком.) | ошибки приходят JSON-ом, а не HTML |
| `Authorization: Bearer <JWT>` | `create`/`edit`/`delete` | админ-токен (middleware `auth:api` + `admin`). Нет → `401`, не админ → `403` |

---

## 3. Эндпоинты (сводка)

| Метод | Путь | Доступ | Назначение |
|---|---|---|---|
| `POST` | `/api/v1/festival/getList` | публичный | список с фильтрами + сортировкой |
| `GET` | `/api/v1/festival/getItem/{id}` | публичный | один фестиваль |
| `POST` | `/api/v1/festival/create` | `auth:api` + `admin` | создать |
| `POST` | `/api/v1/festival/edit/{id}` | `auth:api` + `admin` | редактировать |
| `DELETE` | `/api/v1/festival/delete/{id}` | `auth:api` + `admin` | удалить (soft) |
| `GET` | `/api/v1/festival/getHistory/{id}` | `auth:api` + `admin` | журнал изменений (AF-7) |

---

## 4. `POST /getList` — список

**Тело (всё опционально):**
```jsonc
{
  "filter": {
    "name":   "string?",   // LIKE  (подстрока, регистрозависимо как в БД)
    "year":   2026,        // EQUAL (int)
    "active": true         // EQUAL (bool) — см. примечание ниже
  },
  "orderBy": { "name": "asc" }   // ключ = поле, значение = asc|desc
}
```

**Правила:**
- Фильтр — **whitelist**: в `WHERE` попадают только `name` / `year` / `active`. Прочие ключи игнорируются.
- `orderBy.*` допускает только `asc` / `desc`. Кривое значение → запрос **не падает** (fallback `Order::none()`).
- Soft-deleted фестивали **исключены**.
- ⚠️ Фильтр строит значения через `!empty()`: `active: false` и `year: 0` будут **проигнорированы** (особенность общего `FilterBuilder`, как у `location`). Чтобы получить неактивные — фильтруй на клиенте или запрашивай без фильтра.

**Ответ 200:**
```json
{
  "success": true,
  "list": [
    { "id": "uuid", "name": "Систо-Осень", "year": 2026, "active": true }
  ]
}
```

---

## 5. `GET /getItem/{id}` — один фестиваль

**Ответ 200 (найден):**
```json
{ "success": true, "item": { "id": "uuid", "name": "Систо-Осень", "year": 2026, "active": true } }
```

**Ответ 200 (не найден):**
```json
{ "success": false, "message": "Фестиваль не найден" }
```
> «Не найден» отдаётся как **HTTP 200** с `success:false` (а не 404). ⚠️ Невалидный UUID в `{id}` → `500` (валидация формата в `Uuid`). Передавай корректный uuid.

---

## 6. `POST /create` — создать (admin)

**Тело:**
```json
{ "data": { "name": "Систо-Осень", "year": 2026, "active": true } }
```

**Валидация:**
| Поле | Правила |
|---|---|
| `data.name` | `required, string, max:255` |
| `data.year` | `required, integer, min:2000, max:2100` |
| `data.active` | `boolean` (необязательно, default `false`) |

**Ответ 200:**
```json
{ "success": true, "item": { "id": "uuid", "name": "Систо-Осень", "year": 2026, "active": true }, "message": "Фестиваль создан" }
```
`id` генерируется сервером (`Uuid::random()`), в запросе его передавать **не нужно**.

**Ошибки:** `401` (нет токена), `403` (не админ), `422` (валидация: `{ "errors": { "data.name": [...], "data.year": [...] } }`).

---

## 7. `POST /edit/{id}` — редактировать (admin)

**Тело:** идентично `create` (`data.name` / `data.year` / `data.active`). `id` берётся из URL, в теле не нужен.

**Ответ 200 (успех):**
```json
{ "success": true, "item": { "id": "uuid", "name": "...", "year": 2027, "active": true }, "message": "Фестиваль отредактирован" }
```

**Ответ 200 (не найден):** `{ "success": false, "message": "Фестиваль не найден" }`
**Ошибки:** `401` / `403` / `422` (как в `create`).

---

## 8. `DELETE /delete/{id}` — удалить (admin, soft)

**Ответ 200:**
```json
{ "success": true }
```
`success:false`, если фестиваля с таким `id` нет. Удаление **мягкое** — строка остаётся с заполненным `deleted_at`, перестаёт отдаваться в чтении. Восстановления через API нет (только в БД).

---

## 8a. `GET /getHistory/{id}` — журнал изменений (admin, AF-7)

`Festival` — **AggregateRoot** с историей: `create`/`edit`/`delete` пишут события в `domain_history` (`aggregate_type='festival'`, `actor_type=user`, `actor_id=Auth::id()`).

| Событие | Когда | Payload |
|---|---|---|
| `festival_created` | create | `{name, year, active}` |
| `festival_edited` | edit (если есть изменения) | `{changed: ["name","year","active"]}` |
| `festival_deleted` | delete | `{}` |

**Ответ 200:**
```json
{
  "success": true,
  "history": [
    { "event_name": "festival_created", "aggregate_type": "festival",
      "payload": { "name": "...", "year": 2026, "active": true },
      "actor_id": "uuid", "actor_type": "user", "actor_name": "...", "actor_email": "...",
      "occurred_at": "ISO8601" }
  ]
}
```
> `edit` без реальных изменений полей (name/year/active) события **не пишет**.

---

## 9. Краевые случаи (что заложить в клиента/агента)

| Случай | Поведение |
|---|---|
| Чтение без токена | Разрешено (публичные `getList`/`getItem`) |
| Запись без токена | `401` |
| Запись не-админом | `403` |
| `getItem`/`edit` несуществующего id | `200 { success:false, message:"Фестиваль не найден" }` |
| Невалидный UUID в URL | `500` (передавай корректный uuid) |
| `delete` несуществующего id | `200 { success:false }` |
| Невалидное тело `create`/`edit` | `422 { errors:{...} }` |
| `orderBy` с мусором | игнорируется, список отдаётся |
| Фильтр `active:false` / `year:0` | игнорируется (`!empty()`-особенность) |

---

## 10. Примеры (curl)

**Список с фильтром:**
```bash
curl -sS -X POST https://api.staging.spaceofjoy.ru/api/v1/festival/getList \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"filter":{"year":2026},"orderBy":{"name":"asc"}}'
```

**Создать (admin):**
```bash
curl -sS -X POST https://api.staging.spaceofjoy.ru/api/v1/festival/create \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -H 'Authorization: Bearer <ADMIN_JWT>' \
  -d '{"data":{"name":"Систо-Осень","year":2026,"active":true}}'
```

**Редактировать (admin):**
```bash
curl -sS -X POST https://api.staging.spaceofjoy.ru/api/v1/festival/edit/<id> \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -H 'Authorization: Bearer <ADMIN_JWT>' \
  -d '{"data":{"name":"Систо-Осень (правка)","year":2027,"active":false}}'
```

**Удалить (admin, soft):**
```bash
curl -sS -X DELETE https://api.staging.spaceofjoy.ru/api/v1/festival/delete/<id> \
  -H 'Accept: application/json' -H 'Authorization: Bearer <ADMIN_JWT>'
```

---

## 11. Провенанс (файлы кода)

| Слой | Файл |
|---|---|
| Роуты | `Backend/routes/festival.php` |
| Контроллер | `Backend/app/Http/Controllers/Festival/FestivalController.php` |
| Application | `Backend/src/Order/OrderTicket/Application/GetFestivalList/FestivalApplication.php` |
| Query/Command | `Backend/src/Order/OrderTicket/Application/{GetList,GetItem,Edit,Delete,CreateFestival}/*` |
| Repository | `Backend/src/Order/OrderTicket/Repositories/FestivalRepositoryInterface.php` + `InMemoryMySqlFestivalRepository.php` |
| DTO / Response | `Backend/src/Order/OrderTicket/Dto/Festival/FestivalDto.php` (implements `Response`), `Responses/FestivalGetListResponse.php` |
| Модель | `Backend/app/Models/Festival/FestivalModel.php` (+ `SoftDeletes`) |
| Миграция | `Backend/database/migrations/2026_06_18_120000_add_deleted_at_to_festivals.php` |
| Тесты | `Backend/tests/Feature/Festival/FestivalCrudApiTest.php`, `FestivalCreateApiTest.php` |

> **AF-7 реализовано:** `Festival` поднят в **AggregateRoot** (`Backend/src/Festival/Domain/Festival.php`) + история (`GET /getHistory/{id}`, события `festival_created/edited/deleted` в `domain_history`). CRUD/репозиторий пока остаются под `Order/OrderTicket/` — полный перенос в модуль `Festival/` отдельным рефакторингом (см. `.claude/specs/festival-aggregate-payment-templates-plan.md`).
