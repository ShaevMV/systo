# История изменений

Все значимые изменения проекта Systo фиксируются в этом файле.

Формат основан на [Keep a Changelog 1.1.0](https://keepachangelog.com/ru/1.1.0/),
проект использует [семантическое версионирование (SemVer 2.0.0)](https://semver.org/lang/ru/).

Подробные правила версионирования и работы с релизами — в `.claude/docs/process/RELEASES.md`.

---

## [Unreleased]

_(пусто — текущие изменения вошли в [2.7.0-alpha.2])_

---

## [2.7.0-alpha.2] — 2026-06-15

**Staging-preview.** Срез работ поверх `v2.7.0-alpha.1` под разворот org → admin-only: дашборд продаж, брокер RabbitMQ на стенде и — главное — полностью рабочая **система шаблонов (AF-3)**: admin меняет письма и PDF-билеты без деплоя. Прод не затронут (всё на стенде; реальный рендер пока на blade-fallback, переключение пошаговое). Тег на `feat/admin-qr-orders`.

### Added (Добавлено)

- **Система шаблонов (AF-3), фазы 1–5** — единый редактор писем и PDF-билетов. Спека: `.claude/specs/template-system.md`.
  - Движок **Mustache** (`mustache/mustache ^3.2`), logic-less → RCE-безопасен by design: исполнение PHP из пользовательского шаблона невозможно архитектурно. Кастомный escape `ENT_QUOTES`, raw `{{{ }}}` только для QR-data-URI.
  - Сущность `Template` (модуль `Backend/src/Template/`) + таблицы `templates` / `template_versions`. `slug` = имени blade-файла → нулевая миграция привязки.
  - Рендер **писем и PDF-билетов из БД** с fallback на blade-файл (нет активной записи → старый blade, нулевой риск). Точки интеграции: `CreatingQrCodeService::createPdf` (PDF) и трейт `RendersDbTemplate` на всех 11 order/list-Mailable (оба канала: legacy + qr).
  - **admin-CRUD API** `/api/v1/template/*` (все `auth:api + admin`): getList/getItem/create/activate, черновик/публикация (`saveDraft`/`publish` со снапшотом версии), версии/откат (`versions`/`rollback`, append-only), палитра (`variables`), **предпросмотр** (`preview`, email → HTML, PDF → DomPDF, синтаксис → 422, только фикстуры без ПДн).
  - **Экран редактора в новой админке** (`AdminFront`): список + редактор (исходник + вставка плейсхолдеров/сниппетов кликом + iframe-превью + черновик/публикация + история версий).
  - Импорт текущих blade в БД (`artisan templates:import-blade`, неактивные черновики) + конвертация в Mustache (`artisan templates:sync-converted`, фаза 5 — конвертирован основной билет `pdf`).
- **Дашборд продаж** — эндпоинт `POST /api/v1/qrOrder/getStats` (admin, агрегаты заказов/выручки по статусу/типу/дням) + экран дашборда в новой админке (карточки + графики chart.js).
- **RabbitMQ на стенде** — брокер (`rabbitmq:3.13-management-alpine`, лимиты под маленький сервер) + management UI на `/rabbitmq/` за basic-auth (транспорт под будущую интеграцию qr→org). AMQP наружу не торчит.

### Fixed (Исправлено)

- **Деплой блокировался EACCES** — шаг сборки старого фронта (`rm -rf dist` под root, `npm build` под дефолтным юзером) падал и не давал собраться новой админке. Теперь одной командой под root.
- **Тёмная тема в новой админке** — на экранах с canvas/iframe (дашборд, редактор) снимок View-Transition залипал и оставлял светлый кадр поверх тёмной страницы до перезагрузки. Убрана обёртка `startViewTransition` — переключение мгновенное и надёжное.

### Security (Безопасность)

- Рендер шаблонов logic-less (Mustache) — никакого RCE из пользовательского ввода. Все эндпоинты шаблонов — `auth:api + admin`, `preview` ещё под `throttle:20,1`. Превью рендерится только на тестовых фикстурах (без ПДн реальных заказов). Sandbox на iframe превью письма.

### Breaking Changes

Нет (pre-release, прод не затрагивается).

---

## [2.5.1] — 2026-05-29

**Патч после v2.5.0.** Починка PHPUnit Baza — теперь 0 errors / 0 skipped / 0 risky. Чистый тестовый прогон через `baza_test` БД с фикстурами. Заодно — фикс расхождения миграций со схемой прода (две колонки добавлены руками без миграций). Подготовлена спецификация фичи «История билета» как кандидат в backlog.

### Added (Добавлено)

- **Спецификация фичи «История билета»** (`.claude/specs/ticket-history.md`, 465 строк) — кандидат в backlog v2.7.0–v2.8.0. 10 типов событий, 10 user stories, рекомендация по архитектуре (общий `domain_history` с агрегатом `ticket`), гибридная синхронизация Baza↔Backend для офлайн-режима. 10 открытых вопросов для пользователя.
- **ChangesFactory** (`Baza/database/factories/`) — factory для модели `ChangesModel` с состояниями `closed()` и `forUsers(array)`
- **ChangesTestDataSeeder** (`Baza/database/seeders/`) — сидер для интеграционных тестов: вызывает `UsersTableSeeder` + создаёт открытую смену для admin (user_id=1)
- **Отдельная тестовая БД `baza_test`** через `phpunit.xml` (`DB_DATABASE=baza_test`) — `RefreshDatabase` пересоздаёт схему на каждом прогоне, не трогая локальную `baza`
- **Шаг `php artisan migrate --force`** в CI job `test-baza` (до прогона PHPUnit) — на свежем CI runner'е миграций раньше не было
- `Baza/tests/Feature/.gitkeep` — пустая папка для будущих feature-тестов (phpunit.xml требует существования директории)
- **HasFactory trait + `newFactory()`** в `App\Models\ChangesModel`

### Changed (Изменено)

- **`Baza/tests/Unit/ChangesTest`** — переписан под интеграционный режим:
  - 3 теста с реальной БД: `test_get_changes_id_returns_open_shift_for_admin`, `test_get_changes_id_throws_for_user_without_shift`, `test_get_report_returns_response_with_seeded_shift`
  - `RefreshDatabase` trait + `$this->seed(ChangesTestDataSeeder::class)` в setUp
  - Убран `markTestSkipped` с обоих исходных тестов
- **`Baza/tests/Unit/SearchServiceTest`** — добавлен `assertInstanceOf(SearchResponse::class)` (раньше был Risky из-за отсутствия ассертов)

### Fixed (Исправлено)

- **Синхронизация миграций со схемой прода (TD-2 follow-up)** — добавлены 2 миграции для колонок, которые на проде/локалке заведены руками:
  - `2026_05_11_125000_add_festival_id_to_el_tickets_table` — `festival_id` (uuid, nullable) в `el_tickets`. Без неё backfill-миграция `2026_05_11_130000_backfill_festival_id_in_el_tickets` падала на чистой БД с `Column not found`
  - `2026_05_29_180000_add_festival_id_and_count_auto_to_changes_table` — `festival_id` и `count_auto_tickets` в `changes`
  - Обе миграции используют `Schema::hasColumn()` — на проде ничего не сломают, лишь зарегистрируются в таблице `migrations`
- **PHPUnit Baza:** было 2 skipped + 1 risky → стало **8 тестов, 10 ассертов, 0 проблем**. TD-2 закрыт.

### Breaking Changes

Нет. Патч-релиз.

### Migration Guide

Для разработчиков:

```bash
# Создать тестовую БД (один раз)
docker exec php-baza php -r "
\$pdo = new PDO('mysql:host=database', 'root', 'common404');
\$pdo->exec('CREATE DATABASE IF NOT EXISTS baza_test CHARACTER SET utf8mb4');
"

# Прогон миграций на тест-БД
docker exec -e DB_DATABASE=baza_test php-baza php artisan migrate --force

# Прогон тестов
docker exec php-baza ./vendor/bin/phpunit --testdox
```

Для прода:
- Миграции безопасны (используют `Schema::hasColumn()`), но фактически не добавят ничего — колонки уже существуют. Запись о миграции в `migrations` появится при следующем `php artisan migrate`.

### Заметки по релизу

Из-за неправильно смерженного PR #36 (вместо backport-PR #37 пользователь сначала смержил feat/v2.5.1-baza-seeders с WIP-коммитом) в истории `master` оказался коммит `29ea36e4 wip: v2.5.1 baza-seeders draft (will be squashed)`. Это разовая аномалия, для следующих релизов будет жёстко следить за порядком: backport-ветка после релиза идёт ПЕРВОЙ.

---

## [2.5.0] — 2026-05-29

**Старт версионирования проекта Systo.** Первая официальная версия. Запущен полноценный процесс релизов: SemVer, ветки, Definition of Done, CI на GitHub Actions, CHANGELOG. Команда расширена двумя новыми агентами. Починены тесты Backend и Baza. Очищены мёртвые сервисы в prod-инфраструктуре. Подготовлены материалы к встрече с организаторами фестиваля.

### Added (Добавлено)

- **Процесс релизов:** `.claude/docs/process/RELEASES.md` — SemVer, branching, Definition of Done, roadmap версий 2.5.0 → 3.0.0-alpha, шаблон release notes, чек-лист релиза, привязка к фестивалям
- **CHANGELOG.md** — формат Keep a Changelog 1.1.0, RU
- **CI на GitHub Actions** (`.github/workflows/ci.yml`): 7 параллельных job — lint Backend/Baza/Frontend, build Frontend, audit, PHPUnit Backend, PHPUnit Baza. MySQL service в test-job-ах, кэш composer и npm. Триггеры: push в master + feat/** + fix/**, PR на master
- **husky + commitlint** в корне монорепо (мягкий warning, conventional commits)
- **Makefile** — расширен с 3 до 30+ целей с группами и `make help` (цветной вывод, описания)
- **Новые агенты команды:**
  - `scrum-master` — Scrum Master + Release Manager, Scrum-of-One
  - `security-engineer` — 152-ФЗ compliance, audit-логи, аудит JWT/middleware, аудит секретов, pentesting публичных API
- **Материалы к встрече с бизнесом 2026-05-30** в `.claude/meetings/2026-05-30/`:
  - Отчёт о фестивале
  - Roadmap для бизнеса на 6 этапов
  - 50+ вопросов в 10 блоках
- В `CLAUDE.md` добавлен раздел «Процессы» и импорт `RELEASES.md`

### Changed (Изменено)

- **CLAUDE.md** — Friendly убран из таблицы компонентов (3 приложения вместо 4)
- **BOARD.md** — добавлен Roadmap-блок версий 2.5.0 → 3.0.0-α + текущая работа
- **TECH_DEBT.md** — расставлены приоритеты с ID (TD-1 … TD-22), привязка к версиям
- **Makefile семантика:** `up` теперь только поднимает, `build` делает docker build (раньше `up` делал `--build`); `build` фронта переименован в `build-frontend`. TTY fix для не-интерактивных команд
- **docker-compose.prod.yml** — добавлены healthcheck mysql + worker `depends_on: service_healthy` + `restart: unless-stopped`

### Removed (Удалено)

- `.github/workflows/laravel.yml` — старый сломанный workflow (PHP 8.0, ветка dev, sqlite, неправильные пути)
- Мёртвые сервисы в `docker-compose.prod.yml`: `phpFriendly`, `phpList`, `database` (mysql 5.7 для удалённого Friendly)
- Volume mounts `./Friendly` и `./List` в nginx и worker
- ENV переменные `ENV_DRUG_*`, `ENV_SPISOK_*` в `.env.example`
- Baza `Feature/ExampleTest` — auto-generated welcome page тест

### Fixed (Исправлено)

- **Race Condition воркера на проде (TD-1)** — воркер ждёт healthy mysql через healthcheck + `depends_on: service_healthy`
- **PHPUnit Backend (TD-2 часть 1):**
  - `ChangeOrderPriceTest` — добавлен `HistoryRepositoryInterface` mock (конструктор handler требовал 2 аргумента, тест передавал 1)
  - Тест переведён на `Tests\TestCase` (Laravel) — для работы `DB::transaction`
  - Было: 4 errors. Стало: **0 errors, 55 тестов, 4 skipped (намеренно — ChangeStatusTest требует view-шаблонов для PDF)**
- **PHPUnit Baza (TD-2 часть 2):**
  - `DefineServiceTest` — переписан под актуальные форматы ссылок (старые `'0020'`, `'sS50065'`, `'ff30049'` больше не поддерживаются в `DefineService`)
  - `ChangesTest` — методы помечены `markTestSkipped` с пояснением (требуют сидеров для Baza)
  - Было: 2 errors + 1 failure. Стало: **0 errors, 7 тестов, 2 skipped**
- **Документация очищена от Friendly-приложения (TD-3)** — Friendly как тип заказа сохранён в Backend
- **Baza/composer.lock** — синхронизирован с composer.json (endroid/qr-code был в .json, но не в .lock)

### Security (Безопасность)

- Введён агент `security-engineer` с активным режимом — даёт советы по безопасности при изменениях в коде

### Breaking Changes

Нет. Это MINOR-релиз, все изменения обратно совместимы.

Изменение семантики `make up` (раньше делал `--build`, теперь нет) задокументировано в Makefile help, для старого поведения есть `make up-build`.

### Migration Guide

Для разработчиков после `git pull master`:

```bash
# Активировать husky-хуки для commitlint
npm install

# Использовать новые make-цели
make help          # увидишь все цели с описанием
make up            # поднять dev-окружение
make build         # билд образов
make test          # все тесты
```

---

[Unreleased]: https://github.com/ShaevMV/systo/compare/v2.5.1...HEAD
[2.5.1]: https://github.com/ShaevMV/systo/releases/tag/v2.5.1
[2.5.0]: https://github.com/ShaevMV/systo/releases/tag/v2.5.0
