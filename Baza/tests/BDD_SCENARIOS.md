# BDD-сценарии Baza (КПП) — карта «сценарий → тест»

> BDD-фреймворка (Codeception/Behat) в проекте нет, установка требует сети (недоступно).
> Решение: сценарии Given/When/Then реализованы как **Feature-тесты** (Arrange/Act/Assert).
> Прогон: `docker exec php-baza ./vendor/bin/phpunit` (БД `baza_test`). Итог: **159 тестов зелёных**
> (Unit 20 + Feature 139).

## Эпик: Поиск без QR
- **Given** парковка с нулём в номере / live с kilter=0 — **When** поиск «test» — **Then** они НЕ в выдаче
  (нет ложного `(int)"test"=0`). → `Search/SearchRelevanceTest`
- **Given** запрос «1234» (число) — **Then** поиск по номеру билета работает. → `SearchRelevanceTest`
- **Given** слово в comment/email/ФИО — **When** поиск — **Then** находит (поиск по ВСЕМ полям). → `SearchRelevanceTest`
- **Given** запрос без сессии — **Then** 401; с сессией — список групп. → `Search/SearchApiTest`

## Эпик: Карточка билета и ПДн (152-ФЗ)
- **Given** билетёр — **When** /api/scan — **Then** в ответе НЕТ телефон/email/коммент. → `Whoami/WhoamiPiiTest`
- **Given** администратор / начальник смены — **When** /api/scan — **Then** ПДн присутствуют. → `WhoamiPiiTest`
- **Given** не авторизован — **When** /api/whoami — **Then** 401; админ → роль+права+can_view_pii. → `WhoamiPiiTest`

## Эпик: Права доступа (RBAC роль×действие)
- **Given** администратор (rbac.manage) — **When** GET матрицы — **Then** роли+действия+матрица. → `Permission/PermissionApiTest`
- **When** POST матрицы {ticketer:[scan]} — **Then** ticketer теряет enter (форма=источник правды). → `PermissionApiTest`
- **Given** не-rbac.manage — **Then** 403; administrator не редактируется (суперроль). → `PermissionApiTest`, `RolePermissionTest`, `PermissionMiddlewareTest`

## Эпик: Регистрация персонала
- **Given** администратор (staff.manage) — **When** POST /api/staff — **Then** сотрудник создан, пароль bcrypt. → `Staff/StaffApiTest`
- **When** повтор по тому же email — **Then** updateOrCreate (без дубля). → `StaffApiTest`
- **Given** не-staff.manage — **Then** 403; кривой email/роль — **Then** 422. → `StaffApiTest`

## Эпик: Смены (создание / назначение / изоляция / закрытие)
- **Given** два начальника разных смен — **When** начальник A смотрит /api/shifts — **Then** видит ТОЛЬКО свою; admin — все. → `Shift/ShiftApiTest`
- **Given** администратор — **When** POST смены без начальника — **Then** 422; с начальником — создана. → `ShiftApiTest`
- **Given** начальник смены — **When** POST смены — **Then** начальник = он сам (изоляция). → `ShiftApiTest`
- **Given** начальник — **When** закрывает чужую смену — **Then** 403; свою — закрыта. → `ShiftApiTest`
- **Given** билетёр — **Then** доступ к сменам 403. → `ShiftApiTest`, `PermissionMiddlewareTest`

## Эпик: Впуск на КПП (регресс)
- **Given** билет уже впущен — **When** повторный впуск — **Then** отклонён, счётчик не растёт. → `EnterTicketSecurityTest`
- **Given** /api/scan, /api/enter — **Then** требуют auth; смена по Auth::id() (не из тела). → `EnterTicketSecurityTest`
- **Given** отозванный билет (blacklist) — **When** /api/enter — **Then** заблокирован. → `Blacklist/BlacklistApiTest`

## Эпик: Доставка org→Baza (ingest, Ф3)
- **Given** валидный X-Baza-Token — **When** POST /api/baza/ingest/ticket — **Then** билет записан (идемпотентно). → `Ingest/IngestTicketApiTest`
- **Given** без/с кривым токеном — **Then** 401. → `IngestTicketApiTest`
- **Given** отзыв билета — **When** POST /api/baza/ingest/revoke — **Then** в blacklist. → `BlacklistApiTest`

## Эпик: Офлайн-вход (Ф5)
- **Given** сотрудник — **When** GET /api/snapshot — **Then** минимизированный снимок (B5, без ПДн-контактов), дельта. → `Snapshot/SnapshotApiTest`
- **Given** дренаж намерений — **When** POST /api/entry-events — **Then** дедуп client_op_id, «первый впуск побеждает», revoked не впускается. → `EntryEvents/EntryEventsApiTest`

## Эпик: Демо/сидеры (проверка RBAC вживую)
- **Given** прогон сидеров — **Then** 2 открытые смены с разными начальниками, роли в change_user, чистая демо-почта, идемпотентность. → `MultiShiftDemoSeederTest`, `StagingSeedersTest`
