# Релизы и версионирование

Этот документ — единый источник правды по релизам проекта Systo. Версионирование, ветки, Definition of Done, roadmap, шаблоны release notes.

**Ответственный агент:** [scrum-master](../../../.claude/agents/scrum-master.md)

---

## 1. SemVer для Systo

Проект Systo — монорепо с **3 приложениями**:
- **Backend** (Laravel 9, PHP 8.2) — основное приложение SolarSysto
- **Baza** (Laravel 9, PHP 8.2) — аутентификация
- **FrontEnd** (Vue 3) — SPA

**Единая версия на весь монорепо** — простота для одного разработчика и open-source аудитории.

### Что есть MAJOR / MINOR / PATCH

| Тип | Что попадает | Примеры |
|-----|--------------|---------|
| **MAJOR** (3.0.0) | Ломающие изменения API, схемы БД без обратной совместимости, смена контракта между приложениями, удаление публичных эндпоинтов | Переход на Laravel 11 с breaking changes, изменение JWT-формата, удаление `friendly_id` |
| **MINOR** (2.6.0) | Новые фичи, обратно совместимые миграции, новые эндпоинты, новые типы билетов/статусов | Заказы-списки, авто-одобрение через токен, OrderKind VO, новые анкеты |
| **PATCH** (2.5.1) | Багфиксы, фиксы UI, документация без изменения API, оптимизации без поведенческих изменений | Починка тестов, Sentry filter, мелкие правки вёрстки |

### Pre-release

Формат: `2.X.Y-alpha`, `2.X.Y-beta`, `2.X.Y-rc1`.

**Когда использовать `-alpha`:**
- Есть готовый scope, но не все breaking changes документированы
- Не прошёл регресс на staging
- Не написан migration guide

**Когда `-beta`:**
- Все breaking changes документированы
- Регресс пройден на staging
- Feature freeze (только багфиксы)

**Когда `-rc`:**
- Только багфиксы по результатам beta-тестирования
- Готов к выпуску в течение 1-2 недель

---

## 2. Branch strategy

**Trunk-based с release-ветками** (упрощённый GitFlow):

```
master           ─── основная линия разработки
├── feature/X    ─── для каждой задачи
├── fix/Y        ─── багфиксы
└── release/2.X  ─── ветка стабилизации перед тегом v2.X.0
```

### Правила веток

- `master` — рабочая ветка, всегда зелёная (CI проходит)
- `feature/<scope>-<description>` — новые фичи (см. CONVENTIONS.md)
- `fix/<scope>-<description>` — багфиксы
- `release/2.X.x` — стабилизация перед минорным релизом, бэкпорт хотфиксов
- Теги `v2.X.Y` ставятся **только** на release-ветках

### Защита master

- CI обязателен (lint + build), без него — нельзя смержить
- Force push запрещён
- Линейная история (rebase, не merge commits)
- Required reviewers — **нет** (один разработчик), self-merge ok

---

## 3. Definition of Done

Версия считается готовой к тегу, когда:

- [ ] Все запланированные задачи закрыты или явно вынесены в следующий релиз
- [ ] CI зелёный (lint + build, тесты — пока allow-failure до починки PHPUnit)
- [ ] `CHANGELOG.md` обновлён — секция версии с датой выпуска
- [ ] Документация в `.claude/docs/` отражает изменения (API.md, DOMAIN.md, BUSINESS_RULES.md)
- [ ] `BOARD.md` обновлён — задачи перенесены в «Сделано»
- [ ] `TECH_DEBT.md` обновлён — закрытые/новые техдолги отражены
- [ ] Если есть миграции БД — проверены на staging (или на копии прод-БД)
- [ ] Release notes готовы для GitHub Release
- [ ] Если ломающие изменения — есть migration guide

---

## 4. Roadmap 2.5.0 → 3.0.0-alpha

| Версия | Срок | Содержимое | Статус |
|--------|------|------------|--------|
| **2.5.0** | 2026-05-28 → 2026-06-05 | Старт версионирования: SemVer-инфра, базовый CI, healthcheck воркера, новые агенты (scrum-master, security-engineer), очистка документации от Friendly-приложения | В работе |
| **2.5.1** | patch, параллельно | Починка PHPUnit (auto-tester) | Запланировано |
| **2.6.0** | июнь 2026 | SSL mkcert для ноутбука-сканера, offline docker-compose bundle, CD staging | Запланировано |
| **2.7.0** | июль 2026 | Loki + Promtail + Grafana, единый поток логов (audit + воронка покупки), CD prod tag-based + release-please | Запланировано |
| **2.8.0** | август 2026 | Laravel 11 update part 1 (staging), упаковка SPA для офлайн-работы на ноутбуке-сканере | Запланировано |
| **2.9.0** | август–сентябрь 2026 | OrderKind VO + 3 Application-сервиса (поэтапная миграция), Laravel 11 part 2 (прод) | Запланировано |
| **3.0.0-alpha** | сентябрь 2026 после встречи с бизнесом | Новый scope от организаторов фестиваля | Открытый |

### Детализация версий — см. ниже

#### v2.5.0 — Старт версионирования

**Включено:**
- Папка `.claude/docs/process/` + импорты в CLAUDE.md
- `CHANGELOG.md` (русский, формат Keep a Changelog 1.1.0)
- Очистка документации от Friendly-приложения (CLAUDE.md, API.md, DOMAIN.md, CONVENTIONS.md, PROJECT_MEMORY.md)
- Описания агентов: `scrum-master`, `security-engineer`
- husky + commitlint (мягкий warning) на Backend, Baza, FrontEnd
- Healthcheck воркера в `docker-compose.yml` (прод)
- Базовый CI: Pint + ESLint + npm build + composer/npm audit + PHPUnit с `continue-on-error`
- Подсчёт сломанных PHPUnit-тестов, фиксация в TECH_DEBT
- Тег `v2.5.0` + первый GitHub Release

**Не входит:**
- Починка PHPUnit (→ v2.5.1)
- SSL / offline docker / CD (→ v2.6.0)

#### v2.6.0 — Локалка фестиваля + dev environment

- SSL mkcert + локальный CA для ноутбука-сканера (через `/etc/hosts`, свежий сертификат каждый фестиваль)
- Offline docker-compose bundle (до 15 ГБ, дамп БД файлом, для фестивальной машины)
- CD staging автодеплой через GitHub Actions (когда появится staging-сервер)
- Проверка сканера + поиска на стенде после SSL-фикса

#### v2.7.0 — Логи + полный CD

- Loki + Promtail + Grafana на отдельной RU VPS, retention 1+ год
- Audit-канал в Laravel: все действия всех пользователей, JSON + человеко-читаемый формат
- Логирование воронки покупки/заказа (этапы) — единый поток с audit (Ф1.A)
- Починка PHPUnit → переключение в CI на required
- CD prod tag-based deploy + release-please (автогенерация CHANGELOG)
- 5 бэкапов БД перед каждым деплоем

#### v2.8.0 — Laravel 11 + Offline-ноутбук

- Laravel 11 update part 1 — на staging, все 3 приложения (Backend, Baza, FrontEnd)
- Упаковка SPA для офлайн-работы на ноутбуке-сканере (не PWA — раз сканер ноутбук со встроенной камерой, нужна локальная установка + SSL)

#### v2.9.0 — OrderKind VO + Laravel 11 part 2

- Колонка `kind ENUM('guest','friendly','list')` в `order_tickets`
- Поэтапная миграция данных (без жёсткого простоя), Claude (агент) пишет миграцию
- 3 Application-сервиса: `GuestOrderApplication`, `FriendlyOrderApplication`, `ListOrderApplication`
- **Никакого Base-класса** — только VO-метка + полиморфизм через композицию
- Laravel 11 деплой на прод

#### v3.0.0-alpha — После встречи с бизнесом

Открытый scope. Зависит от итогов встречи с организаторами фестиваля.

---

## 5. Шаблон release notes (GitHub Release)

```markdown
## v2.X.Y — <Краткое название релиза>

**Дата:** YYYY-MM-DD
**Тип:** Major / Minor / Patch / Pre-release

### Что нового

- <Краткое описание ключевых изменений с точки зрения пользователя>

### Изменения по разделам

#### Added (Добавлено)
- ...

#### Changed (Изменено)
- ...

#### Deprecated (Помечено устаревшим)
- ...

#### Removed (Удалено)
- ...

#### Fixed (Исправлено)
- ...

#### Security (Безопасность)
- ...

### Breaking Changes (только для MAJOR)

- ...

### Migration Guide (для MAJOR/MINOR с миграциями)

См. отдельный файл `.claude/docs/process/migrations/v2.X.Y.md`

### Контрибьюторы

- @username
```

---

## 6. Процесс принятия решений

| Решение | Кто принимает |
|---------|---------------|
| Содержимое **PATCH** | scrum-master единолично |
| Содержимое **MINOR** | scrum-master + tech-lead согласовывают |
| Содержимое **MAJOR** | Обязательная встреча с пользователем + business-analyst |
| Имя версии и timing | scrum-master |
| Что отложить в следующий релиз | scrum-master с учётом мнения tech-lead и business-analyst |

---

## 7. Привязка к фестивалям

| Период | Режим работы |
|--------|--------------|
| **До 14 дней до фестиваля** | Любые релизы (даже MINOR) |
| **14–7 дней до фестиваля** | Только PATCH (багфиксы) |
| **7–0 дней до фестиваля** | Code freeze (только критичные хотфиксы) |
| **Во время продаж** | Только хотфиксы по согласованию с пользователем |
| **Сразу после фестиваля** | Окно для MAJOR-изменений (после ретроспективы) |

---

## 8. Чек-лист релиза (для scrum-master)

При выпуске любой версии:

1. [ ] Создать ветку `release/2.X.x` от `master`
2. [ ] Обновить `CHANGELOG.md` (перенести из `[Unreleased]` в `[2.X.Y]`)
3. [ ] Пройти Definition of Done (см. §3)
4. [ ] Поставить тег `v2.X.Y` на release-ветке
5. [ ] Создать GitHub Release с release notes по шаблону (см. §5)
6. [ ] Обновить `BOARD.md` — задачи перенести в «Сделано»
7. [ ] Уведомить пользователя о выпуске

---

## История изменений документа

| Дата | Изменение |
|------|-----------|
| 2026-05-28 | Создан документ. Roadmap 2.5.0 → 3.0.0-alpha, SemVer-правила, branching, DoD, шаблоны |
