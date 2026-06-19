# Ф2 — Роли в рамках смены + RBAC-матрица через интерфейс (Baza)

> **Статус:** ЧЕРНОВИК на утверждение (2026-06-19). Часть плана `baza-update-implementation-plan.md` (Фаза 2).
> **Источник:** многоагентное исследование + adversarial-вердикт (must-fixes ниже учтены).
> **Решение владельца:** «делай как считаешь нужным, потом перепишем» + **через интерфейс** + **подготовить почву под незаконченную org-матрицу**.

---

## 0. Контекст и связь с org

Незаконченная «матричная» задача в org, про которую говорил владелец, — это **`.claude/specs/admin-rbac.md`** (черновик 2026-06-18, НЕ реализован): матрица прав `роль × элемент × действие` на 16 admin-экранов, таблица `role_permissions`, middleware `permission:`, эндпоинт `myPermissions`, редактор матрицы в AdminFront. Висит с 6 открытыми вопросами владельца.

**Стратегия:** Baza Ф2 реализует ту же ИДЕЮ (редактируемая из UI матрица прав в БД + middleware по праву + суперроль короткозамкнута в коде), доказывает её на практике, и готовит **дизайн-контракт** для org Phase 2. Код/схему НЕ шарим (Baza автономна — отдельная схема `baza`, без FK, без cross-schema). Подробности переносимости — §5.

---

## 1. Модель данных

### 1.1 VO 5 ролей смены — `scr/Shared/Domain/ValueObject/ShiftRole.php`
Пассивный enum-хелпер (как `EmailEvent` в org), без БД. Коды латиницей (для middleware/матрицы), русские метки для UI.

| Код | Метка | Семантика |
|-----|-------|-----------|
| `administrator` | Администратор | полный доступ + `/sync` + матрица прав (суперроль) |
| `shift_chief` | Начальник смены | главный смены (инвариант), правит отчёт/состав, финансы |
| `ticketer` | Билетёр | впуск/поиск/сканер |
| `kpp_commandant` | Комендант КПП | впуск + приём смены (под Ф6/Ф7) |
| `guard` | Охранник | впуск + поиск владельца по браслету (под Ф6) |

Методы: `all()`, `isValid()`, `label()`, `catalog()` (`[{value,label}]`), `fromUser(bool $isAdmin, ?string $role)` — мягкий маппинг `role ?? (isAdmin ? administrator : ticketer)`.

### 1.2 Таблица `change_user` (состав смены с ролями)
Миграция (с `Schema::hasTable()` guard — паттерн Baza, см. must-fix): `id`, `change_id` (index), `user_id` (index), `role` string(40), timestamps, **UNIQUE(change_id, user_id)**. Без FK (целостность на уровне приложения, как везде в Baza). Модель `App\Models\ChangeUserModel` (`$fillable=['change_id','user_id','role']` — **role сразу в fillable**, иначе молча не сохранится).

### 1.3 `users.role` (глобальная роль-дефолт)
Миграция `string('role')->nullable()->after('is_admin')` **в `Schema::hasColumn()` guard** (на проде Baza колонки добавляют руками — иначе Duplicate column). `is_admin` НЕ удаляем (вход не ломаем). `role` вне `$fillable` → в репозитории ставится напрямую (`$model->role=…; save()`, как `is_admin` в `createList`).

### 1.4 Таблица прав `baza_role_permissions` (редактируемая из UI) — **2 оси**
Миграция (`Schema::hasTable()` guard): `role` string(40), `action` string(60), **PK(role, action)**. Наличие строки = право есть; нет строки = запрет. Подход из org `admin-rbac.md §2.4`, но **2 оси** (`роль×действие`) вместо 3 (Baza проще — экраны = действия). ⚠️ Оси 2-vs-3 — гейт владельца (§7 Q1, влияет на унификацию с org).

Каталог действий — `scr/Shared/Domain/ValueObject/ShiftPermission.php`:
`ticket.scan` · `ticket.search` · `ticket.enter` · `report.view` · `shift.compose` · `shift.close` · `shift.remove` · `sync.manage` · `finance.view` (зарезервировано под Ф7) · `rbac.manage`.
`profile.*`/`login`/`logout` — вне матрицы (любой авторизованный).

**Дефолтная матрица** (сидер `BazaRolePermissionsSeeder`, идемпотентно `updateOrCreate`):

| действие \ роль | admin | shift_chief | ticketer | kpp_commandant | guard |
|---|:--:|:--:|:--:|:--:|:--:|
| ticket.scan/search/enter | ✅ | ✅ | ✅ | ✅ | ✅ |
| report.view | ✅ | ✅ | — | — | — |
| shift.compose / shift.close | ✅ | ✅ | — | — | — |
| shift.remove | ✅ | — | — | — | — |
| sync.manage | ✅ | — | — | — | — |
| finance.view | ✅ | ✅ | — | ✅ | — |
| rbac.manage | ✅ | — | — | — | — |

`administrator` — **короткозамкнут в коде** (`can()` всегда `true`, в таблицу не пишем; защита от «закрыл себе доступ»).

---

## 2. Двойная запись состава + инвариант «главный смены»

- **Линчпин (подтверждён кодом):** активная смена и отчёт резолвятся ИСКЛЮЧИТЕЛЬНО через `changes.user_id` JSON (`getChangeId` → `whereJsonContains`, `getAllReport` → `JSON_CONTAINS`). Поэтому **JSON-запись сохраняем параллельно** `change_user` — вход/счётчики НЕ ломаются, читателей НЕ переключаем (это поздняя фаза после PWA).
- `SaveChangeCommand`: контракт `array $userIdList` → `[{user_id, role}]` + `chief_user_id`. Запись обоих представлений **в одном методе репозитория** (`InMemoryMySqlChangesRepository`, в транзакции): JSON `changes.user_id` (плоский список id) + пересбор `change_user` (delete по change_id → insert строк).
- **MUST-FIX (рассинхрон):** `remove()` делает `delete` строки changes → **обязан также удалить `change_user where change_id=?`** (нет каскада). Тест «после remove нет осиротевших change_user».
- **Инвариант главного:** в `SaveChangeCommandHandler` — `DomainException`, если в составе не ровно один `shift_chief`. Источник правды — хендлер (UI-валидация — лишь UX).
- **MUST-FIX (зелёный CI):** смена контракта + throw сломают `ChangesTestDataSeeder`/`ChangesFactory` и flat-вызовы → **обновить их в ТОМ ЖЕ PR-2** (или сделать хендлер толерантным к старому формату с дефолтной ролью). Иначе падает PHPUnit.
- Старые смены (только `changes.user_id`, без `change_user`) не бэкфилим в Ф2 (§7 Q4).

---

## 3. RBAC-enforcement

- Модуль `scr/Permission/` (пассивная сущность, БД только в репозитории): `RolePermissionRepositoryInterface` (`can(role,action)`, `getMatrix()`, `setMatrix(role,actions)`), Application `CanAccess` (через свой Bus, как `GetCurrentChanges`). `administrator` → короткое замыкание `true`. Bind в `BazaServiceProvider`.
- Middleware `CheckPermission` (alias `permission` в `Kernel.php`): `permission:<action>`. Берёт `ShiftRole::fromUser(is_admin, role)` (глобальная роль — §7 Q2), спрашивает `can()`. `is_admin=true → administrator → пропуск` (идентично текущему `IsAdmin`).
- **Фикс UX-бага:** при отказе для web — `abort(403)` (HTML), для `/api/*` — JSON по `expectsJson()` (текущий `IsAdmin` всегда отдаёт JSON, ломая web-UI).
- Роуты `/report`,`/change/*`,`/sync/*`: `middleware('admin')` → `permission:report.view|shift.compose|shift.close|shift.remove|sync.manage`. `IsAdmin` не удаляем.
- **Уточнение к ложному critical вердикта:** точки впуска `/`,`/scan`,`/search`,`/enterForTable` УЖЕ под `auth` (в конструкторах `ScanController`/`SearchController` — подтверждено кодом + staging `/search→302`, `enterForTable→419`). Это НЕ дыра. `ticket.*` в матрице — для полноты модели; навешивание `permission:ticket.*` на эти контроллеры **опционально** (все 5 ролей и так имеют ticket.*), можно отложить, чтобы не плодить точки.
- **MUST-FIX (lock-out):** гарантия «существует ≥1 пользователь с administrator»; `administrator` определяется через `is_admin` (короткозамкнут) → защита от потери доступа к `rbac.manage`. Запрет снять у себя/последнего админ-роль (§7 Q3/Q5).
- **MUST-FIX (порядок PR — hard dependency):** идемпотентный `BazaRolePermissionsSeeder` (PR-4) ОБЯЗАН примениться ДО перевода роутов на `permission:` (PR-5), иначе `rbac.manage` заперт у всех кроме is_admin.

---

## 4. UI (blade + Bootstrap4 + jQuery, паттерн `change/save` с `@csrf`)

- **Экран «состав смены + роли»** — расширить `change/add.blade.php`: на каждого участника `select` роли (`ShiftRole::catalog()`) + radio «главный» (ровно один). jQuery для динамических строк. `ChangesController::save` читает `[{user_id,role}]` + `chief_user_id`, передаёт в `SaveChange` (в БД не лезет). `viewAddChange` грузит состав из `change_user` с fallback на JSON (старые смены).
- **Экран «матрица прав роль×действие»** — новый `permission/matrix.blade.php`: таблица чекбоксов (строки=действия, колонки=5 ролей; колонка `administrator` задизейблена/всегда ✅). POST `/permissions` → `PermissionController::save` → `setMatrix`. Роуты под `permission:rbac.manage` (web-группа → CSRF).
- **Меню** (`sidebar.blade.php`): пункты по правам через `can_baza('<action>')` (blade-directive/фасад вокруг `CanAccess`) вместо `@if(is_admin)`. Пункт «Права доступа» под `can_baza('rbac.manage')`. is_admin видит всё.

---

## 5. Переносимость в org (готовим почву под `admin-rbac.md` Phase 2)

| Переиспользуем (дизайн-контракт) | Граница (НЕ шарим) |
|---|---|
| «строка = право, нет строки = запрет», PK из осей | Таблицы разные: Baza `baza_role_permissions` (схема `baza`), org `role_permissions` (схема `systo`). **Без cross-schema.** |
| Суперроль короткозамкнута в коде (не в таблице) | Оси: Baza 2 (`роль×действие`), org 3 (`роль×элемент×действие`, 16 экранов) — §7 Q1 |
| Паттерн `Permission` Application (`can/getMatrix/setMatrix`, БД в репозитории) | Роли: Baza 5 (`ShiftRole`), org 8 (`AccountRoleHelper`) |
| UX редактора (таблица чекбоксов, суперроль задизейблена) | Энфорсмент: Baza web+blade, org JWT+AdminFront (`v-can`, `myPermissions`) |

> Жёсткой связки нет — общий только дизайн-контракт. Финальное «2 vs 3 оси» свести вместе с org Q1/Q2 (§7 Q1).

---

## 6. Разбивка на PR (каждый не ломает вход; реальные юзеры = is_admin → administrator → проходят везде)

1. **PR-1** `VO ShiftRole + ShiftPermission` — только enum-хелперы + unit-тесты. Нулевой риск.
2. **PR-2** `change_user + двойная запись + инвариант главного` — миграция (hasTable), `ChangeUserModel`, `SaveChangeCommand(+Handler)`, репозиторий (двойная запись + **remove() чистит change_user**), `ChangesController::save`. **Обновить `ChangesTestDataSeeder`/`ChangesFactory`** (зелёный CI). Тесты: двойная запись, инвариант, remove чистит, getChangeId работает.
3. **PR-3** `users.role + мягкий маппинг` — миграция (hasColumn), `User`, `StaffUsersSeeder` (role из строки-списка), `UsersTableSeeder` (роли тестовым). Тесты маппинга.
4. **PR-4** `модуль Permission + матрица в БД` — миграции (hasTable), модель, репозиторий, `CanAccess`/`SetMatrix`, **идемпотентный `BazaRolePermissionsSeeder` (дефолт §1.4)**, bind. Тесты `can()`/суперправо/`setMatrix`. 🚪 **Гейт владельца (оси) до мержа.**
5. **PR-5** `middleware permission + перевод admin-роутов + lock-out защита` — `CheckPermission`, alias, роуты, фикс 403 HTML/JSON. **Hard-dep: PR-4 сидер применён.** Тесты: is_admin проходит (вход цел), ticketer→403 на /sync, гость→/login.
6. **PR-6** `UI: состав+роли смены и редактор матрицы` — `change/add.blade`, `permission/matrix.blade`, `PermissionController`, `can_baza()`, меню. Тесты рендера/доступа.

Порядок строгий 1→6. PR-1..3 безопасны всегда; enforcement реально включается в PR-5.

---

## 7. Открытые вопросы владельцу (дизайн делает предположение — «потом перепишем»)

1. **Оси матрицы: 2 (`роль×действие`, Baza-проще) vs 3 (`роль×элемент×действие`, как org `admin-rbac.md`)?** Предложено **2**. Влияет на унификацию с org — решать вместе с org Q1/Q2. ← главный гейт.
2. **Middleware по глобальной роли (`users.role`) или по роли в активной смене (`change_user.role`)?** Предложено: глобальная (проще; роль-в-смене точнее по смыслу КПП, но что если смена не открыта).
3. **Самозащита:** `administrator` короткозамкнут (права не редактируются из UI). Ок?
4. **Старые смены:** НЕ бэкфилим в `change_user` (инвариант — только на новых). Бэкфилить и кого назначать главным?
5. **`finance.view`** в матрице сейчас (зарезервировано) или ждём Ф7?
6. **Глобальная роль пользователя:** в Ф2 только роль-в-смене + сидер, или нужен экран смены `users.role` (полноценный CRUD юзеров — это org Phase 1 / поздняя фаза Baza)?
