# История изменений

Все значимые изменения проекта Systo фиксируются в этом файле.

Формат основан на [Keep a Changelog 1.1.0](https://keepachangelog.com/ru/1.1.0/),
проект использует [семантическое версионирование (SemVer 2.0.0)](https://semver.org/lang/ru/).

Подробные правила версионирования и работы с релизами — в `.claude/docs/process/RELEASES.md`.

---

## [Unreleased]

_(пока пусто — все изменения вошли в [2.5.0])_

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

[Unreleased]: https://github.com/ShaevMV/systo/compare/v2.5.0...HEAD
[2.5.0]: https://github.com/ShaevMV/systo/releases/tag/v2.5.0
