# История изменений

Все значимые изменения проекта Systo фиксируются в этом файле.

Формат основан на [Keep a Changelog 1.1.0](https://keepachangelog.com/ru/1.1.0/),
проект использует [семантическое версионирование (SemVer 2.0.0)](https://semver.org/lang/ru/).

Подробные правила версионирования и работы с релизами — в `.claude/docs/process/RELEASES.md`.

---

## [Unreleased]

### Added (Добавлено)

- Введён `CHANGELOG.md` (формат Keep a Changelog 1.1.0, русский язык)
- Создана папка `.claude/docs/process/` с документом `RELEASES.md` — правила версионирования, branching, Definition of Done, roadmap 2.5.0 → 3.0.0-alpha
- Создан агент `scrum-master` — отвечает за релизы, SemVer-теги, CHANGELOG, GitHub Releases, sprint planning, backlog grooming, retrospective, definition of done
- Создан агент `security-engineer` — отвечает за 152-ФЗ compliance, audit-логи, аудит JWT/middleware, аудит секретов, pentesting публичных API
- Добавлен импорт `@.claude/docs/process/RELEASES.md` в `CLAUDE.md`

### Changed (Изменено)

- Документация очищена от упоминаний приложения Friendly (Friendly как отдельный микросервис удалён из монорепо, осталось 3 приложения: Backend, Baza, FrontEnd). Friendly как тип заказа (роль `pusher`, поле `friendly_id`) сохранён внутри Backend.

### Запланировано в [2.5.0]

- husky + commitlint (мягкий режим, warning) на Backend / Baza / FrontEnd
- Healthcheck воркера в `docker-compose.yml` (фикс известного Race Condition)
- Базовый CI на GitHub Actions: Pint + ESLint + npm build + composer/npm audit + PHPUnit с `continue-on-error`
- Подсчёт сломанных PHPUnit-тестов, фиксация в `TECH_DEBT.md`
- Тег `v2.5.0` + GitHub Release

---

[Unreleased]: https://github.com/shaevmv/systo/compare/HEAD...HEAD
