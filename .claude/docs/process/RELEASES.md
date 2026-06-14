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

## 4. Roadmap 2.5.0 → 3.0.0 (обновлён 2026-05-30 после встречи с организаторами)

| Версия | Срок | Содержимое | Статус |
|--------|------|------------|--------|
| **2.5.0** | 2026-05-29 | Старт версионирования: SemVer-инфра, базовый CI, healthcheck воркера, новые агенты | ✅ Выпущена |
| **2.5.1** | 2026-05-29 | Baza-сидеры, чистый PHPUnit, spec истории билета | ✅ Выпущена |
| **2.6.0** | дедлайн был 2026-06-12 | Опции к билетам + новый формат заказа (BREAKING) + миграция + фронт + тесты. **Код в master, тег НЕ поставлен — см. B-0.** | 🟠 Не затеган |
| **2.7.0-alpha.1** | 2026-06-14 (готов к тегу, ждём go) | **Staging-preview:** разворот org → admin-only, qr-заказы (read-only API `qrOrder/getList` + UI), новая админка Vite+PrimeVue Sakai (PoC, 1 экран), инфра `/new-admin/` на staging | 🟡 Готов к тегу |
| **2.7.0** | июль 2026 | Промокоды-агрегатор (история + права на роли кроме guest) + qr.spaceofjoy.ru SSO (Passport hybrid с JWT, роль `qr_service`) + продолжение переезда админки (фазы 0–3 спеки AdminFront) | Запланировано |
| **2.8.0** | начало сентября 2026 | SSL mkcert для ноутбука-сканера, offline docker-compose bundle, CD staging + **новая роль org** (доставка билета в baza + retry, реестр билетов, дашборды AF-2, генератор шаблонов AF-3) | Запланировано |
| **2.9.0** | сентябрь–октябрь 2026 | Loki + Promtail + Grafana, audit + воронка покупки, CD prod tag-based + release-please + **cutover на новую админку** | Запланировано |
| **3.0.0** | TBD | Laravel 11 + OrderKind VO + 3 Application-сервиса + (возможно) full Passport migration. Апгрейд 9→11 в работе на `feature/laravel-11` | Открытый |

> **Дедлайн 2026-06-12** — критичный. До этой даты v2.6.0 должен быть в продакшене (с тестами). Это окно перед запуском продаж осеннего фестиваля.
>
> **Источник:** встреча с организаторами 2026-05-30 (см. `.claude/meetings/2026-05-30/RESULTS.md`).

---

### Recap «что у нас по планам, по релизам» (2026-06-14)

Краткая сводка для владельца — где мы и куда движемся.

**Выпущено и затегано:** `v2.5.0`, `v2.5.1` (2026-05-29).

**Главная аномалия — B-0:** `v2.6.0` (опции к билетам + новый формат заказа BREAKING + миграция + фронт) **функционально готов и влит в master, но тег `v2.6.0` НЕ поставлен**. В `CHANGELOG.md` секция `[Unreleased]` помечена пустой, хотя по факту весь scope v2.6.0 уже в коде. Перед любым следующим тегом это надо разрулить (см. §5.1 → B-0).

**Что готовится сейчас — `v2.7.0-alpha.1` (staging-preview, ждёт go владельца):**
Не путать с полным `v2.7.0` (июль). Это alpha-срез под разворот системы: org → admin-only, публичная витрина уезжает на `qr.spaceofjoy.ru`. В состав входит уже сделанное: backend `qrOrder/getList` (read-only, 12 PHPUnit), экран qr-заказов в старом фронте, PoC новой админки на Vite+Sakai (`AdminFront/`), инфра staging `/new-admin/`. Цель тега — зафиксировать точку для staging-превью, НЕ продакшен.

**Дальше по версиям:**
- **`v2.7.0` (июль)** — промокоды-агрегатор + Passport SSO с qr.spaceofjoy.ru + первые фазы (0–3) переезда админки на Vite+Sakai.
- **`v2.8.0` (сентябрь)** — фестивальная инфра (SSL для сканера, offline bundle, CD staging) + новая роль org: доставка билета в baza с retry (**AF-4**, нужен бэкенд-эндпоинт), реестр билетов, дашборды (**AF-2**), генератор шаблонов письма/PDF (**AF-3**), S3-хранилище билетов (**AF-5**, зависит от devops).
- **`v2.9.0` (сентябрь–октябрь)** — логирование/audit + полный CD + **cutover** прода на новую админку (старый `FrontEnd/` уходит в fallback).
- **`v3.0.0` (TBD)** — Laravel 11 (апгрейд уже в работе на `feature/laravel-11`, инкрементально, staging-only до фестиваля) + OrderKind VO.

**Новые фичи расширения админки (AF-1..AF-5)** разложены по версиям выше и детализированы в `BOARD.md` (раздел «Новые планируемые фичи») + `.claude/specs/admin-frontend-vite-sakai.md`.


### Детализация версий

#### v2.5.0 — Старт версионирования ✅ Выпущена 2026-05-29

См. `CHANGELOG.md §2.5.0`. Тег `v2.5.0`.

#### v2.5.1 — Baza-сидеры и чистый PHPUnit ✅ Выпущена 2026-05-29

См. `CHANGELOG.md §2.5.1`. Тег `v2.5.1`. Закрыт TD-2 (PHPUnit).

#### v2.7.0-alpha.1 — Staging-preview: разворот org → admin-only 🟡 Готов к тегу (ждёт go)

**Дата готовности:** 2026-06-14. **Тип:** pre-release (alpha). **Назначение:** только staging-превью, НЕ продакшен.

Pre-release-срез под стратегический разворот системы (память `project_qr_pivot_2026_06_13`): org становится внутренней admin-only системой (создание билетов + контроль их доставки в baza), публичная витрина и продажи уезжают на отдельный сервис `qr.spaceofjoy.ru`. Спека: `.claude/specs/admin-frontend-vite-sakai.md`.

**Ветка:** `feat/admin-qr-orders` (на staging, +24 коммита к master).

**Состав (уже сделано):**
- **Backend** `POST /api/v1/qrOrder/getList` — admin, read-only, фильтры + пагинация + total. 12 PHPUnit.
- **Старый FrontEnd** — экран `/admin/qr-orders` (PrimeVue 4: server-side DataTable + фильтры + Dialog + Timeline) + пункт меню.
- **Новая админка `AdminFront/`** (greenfield) — Vite + PrimeVue Sakai, фирменный стиль Solar Systo, светлая тема. PoC: 1 экран (qr-заказы) против живого API. Превью: `https://staging.spaceofjoy.ru/new-admin/`.
- **Инфра staging** — сервис `node-admin-staging` + nginx `/new-admin/` + Vite-сборка в `deploy-staging.yml`.

**Почему alpha, а не сразу v2.7.0:** это срез под разворот, а не полный scope v2.7.0 (промокоды-агрегатор + Passport SSO ещё впереди). Фиксируем staging-точку, не трогая прод.

##### Блокеры (B-0..B-6) и что нужно от владельца для go

| ID | Блокер | Тип | Что нужно |
|----|--------|-----|-----------|
| **B-0** | **v2.6.0 не затеган.** Код v2.6.0 в master, но тега `v2.6.0` нет; `CHANGELOG.md [Unreleased]` помечен пустым, хотя scope влит. Нельзя тегать v2.7.0-alpha поверх «дыры» в истории релизов. | релиз-гигиена | **Решение владельца:** (а) поставить ретро-тег `v2.6.0` на нужный коммит master + заполнить `CHANGELOG.md §2.6.0`, ИЛИ (б) согласиться включить scope v2.6.0 в release notes alpha как «накопленное». Рекомендация scrum-master — вариант (а). |
| **B-1** | Состав staging-preview не подтверждён владельцем (что именно входит в alpha-тег). | согласование | Подтвердить состав (см. «Состав» выше). |
| **B-2** | Формат версии pre-release: `v2.7.0-alpha.1` vs иное. | согласование | Подтвердить нотацию (scrum-master предлагает `v2.7.0-alpha.1` по §1 Pre-release). |
| **B-3** | Регресс на staging не оформлен как чек-лист приёмки (6 критериев PoC — см. спека §5). | QA | Прогнать 6 критериев приёмки PoC на staging перед тегом. |
| **B-4** | Пред-существующий баг staging: `auth:api`-роуты без токена → 500 (`Route [login] not defined`). Не блокирует залогиненного админа, но мешает чистой 401. | баг (TD-33) | Принять как known-issue alpha ИЛИ пофиксить отдельной веткой до тега. |
| **B-5** | CI (`ci.yml`) не собирает `AdminFront/` — сборка только в staging-деплое. Регрессии новой админки CI не ловит. | CI (TD-32) | Принять для alpha (staging-сборка есть) ИЛИ добавить job `build-admin` до тега. |
| **B-6** | Два фронта в репо (`FrontEnd/` + `AdminFront/`) до cutover — дубль CI/конфигов. | архитектура (TD-34) | Принять как цена greenfield (по спеке §7); держать окно параллельной жизни коротким. |

**Минимум для go (рекомендация scrum-master):** закрыть **B-0** (тег v2.6.0 + CHANGELOG), подтвердить **B-1/B-2**, прогнать **B-3**. B-4/B-5/B-6 — допустимо принять как known-issues alpha (зафиксированы в TECH_DEBT как TD-31..TD-34).

**Definition of Done для alpha (облегчённый, см. §1 «когда -alpha»):** scope зафиксирован, staging-preview работает, breaking changes alpha-уровня НЕ требуют migration guide (прод не трогаем). CHANGELOG секция `[2.7.0-alpha.1]` — после решения по B-0.

#### v2.6.0 — 🔥 Минимум для старта продаж осеннего фестиваля

**Дедлайн: 2026-06-12.** Без этого не запустить продажи осеннего фестиваля.

**Scope сокращён** после анализа 3 спецификаций (`.claude/specs/ticket-options.md`, `order-format-architecture.md`, `qr-sso-security.md`). Промокоды-агрегатор и Passport SSO перенесены в v2.7.0 (июль).

Делается через интеграционную ветку `feat/v2.6.0-fall-festival` + 6 sub-веток.

**Включено:**

1. **Опции к билетам** (`feat/v2.6.0-option-entity`)
   - Новая сущность `Option` (модуль `Backend/src/Option/`)
   - Поля: `id`, `name`, `price`, `active`, `description?`, `image_url?`
   - Связь many-to-many с `ticket_type` через pivot `option_ticket_type`
   - Snapshot цены/имени в `order_ticket_options` (на момент покупки)
   - Миграция + CRUD endpoints + админ UI

2. **Новый формат заказа BREAKING** (`feat/v2.6.0-order-domain-rewrite`)
   - `ticket_type_id` → переезжает на уровень гостя (`guests[].ticket_type_id`)
   - `promo_code` → переезжает на уровень гостя (`guests[].promo_code`)
   - Новое поле `guests[].options[]` — массив `{option_id, qty}` (кратность через `qty`)
   - Перестройка домена `OrderTicket` — новые VO `OrderGuestLine`, `Money`, `MoneySnapshot`
   - Новый Application-сервис `OrderPriceCalculator` (расчёт цены)

3. **Миграция существующих данных** (`feat/v2.6.0-order-data-migration`)
   - Бэкап всех заказов перед миграцией (5 копий)
   - Идемпотентный SQL-скрипт: для каждого `order_ticket` распределить `ticket_type_id` и `promo_code` родителя на каждого гостя в JSON
   - Валидация после миграции
   - Rollback план (старые колонки nullable для безопасного отката)

4. **Контроллеры под новый формат** (`feat/v2.6.0-controllers-rewrite`)
   - `POST /order/create`, `POST /order/createFriendly` — новый формат
   - `POST /order/createList` — БЕЗ опций (списки бесплатные)
   - Legacy формат — НЕ поддерживаем, сразу BREAKING (все клиенты обновляются одновременно)
   - Валидация: live + non-live в одном заказе — нельзя

5. **Frontend `BuyTicket.vue`** (`feat/v2.6.0-buy-form-rewrite`)
   - Каждый гость как карточка с выбором типа/опций/промокода
   - Live-расчёт цены (обновление при изменении опций)
   - Multi-select опций с qty

6. **Тесты + регресс** (`feat/v2.6.0-tests`)
   - PHPUnit для `OrderGuestLine::calculatePrice()` (критично)
   - PHPUnit для `OrderTicket::totalPrice()`
   - Регресс на staging
   - E2E проверка форм покупки

**Финальные технические решения:**
- Промокод процентный — только от базового билета (не от опций)
- Кратность опций — через `qty` (`options: [{option_id, qty: 2}]`)
- Опции в `createList` — НЕ нужны
- Live + non-live в одном заказе — нельзя

**Не входит (перенесено в v2.7.0):**
- Промокоды-агрегатор (история, привязка к создателю, права на роли)
- Passport SSO с qr.spaceofjoy.ru

#### v2.7.0 — Промокоды-агрегатор + qr.spaceofjoy.ru SSO

**Срок:** июль 2026 (после релиза v2.6.0 и периода стабилизации).

**Перенесено из бывшего v2.6.0 (XL → разрезано на v2.6.0 + v2.7.0).**

**Блок A — Промокоды-агрегатор:**
- История использования: новая таблица `promo_code_usage` (`promo_code_id, order_ticket_id, user_id, applied_at, discount_amount`)
- Привязка к создателю: поле `created_by_user_id` в `promo_code`
- Расширение прав: эндпоинты CRUD промокодов доступны всем ролям **кроме `guest`** (admin, manager, pusher, curator, seller, pusher_curator)
- UI «мои промокоды» для не-админских ролей

**Блок B — qr.spaceofjoy.ru SSO (Hybrid):**
- Внедрение `laravel/passport` (зафиксировано на встрече, не Sanctum)
- **Hybrid подход** (по рекомендации security-engineer):
  - Существующий JWT остаётся для основного auth (`api` guard, tickets.spaceofjoy.ru)
  - Passport добавляется отдельным guard'ом (`api-oauth`) только для qr.spaceofjoy.ru
  - Чёткое разделение в `config/auth.php` — без collision'а
- Authorization Code grant с **PKCE** (для публичного SPA-клиента qr.spaceofjoy.ru)
- httpOnly secure cookies для access token на qr.spaceofjoy.ru (не localStorage!)
- Выделенная роль `qr_service` в `AccountRoleHelper` (service-account, не user-роль)
- SSO flow: пользователь авторизуется в Systo → бесшовно работает в qr.spaceofjoy.ru
- Consent screen для 152-ФЗ (явное согласие на передачу персданных)
- Документация по интеграции в `.claude/docs/INTEGRATION_QR.md`
- Full Passport migration — отложена в v3.0.0

#### v2.8.0 — SSL + Offline-ноутбук (сентябрь)

Сдвинут с прежнего v2.6.0 → v2.7.0 → v2.8.0 (после пересмотра scope под v2.7.0). Делается ближе к фестивалю.

- SSL mkcert + локальный CA для ноутбука-сканера (через `/etc/hosts`, свежий сертификат каждый фестиваль)
- Offline docker-compose bundle (до 15 ГБ, дамп БД файлом, для фестивальной машины)
- CD staging автодеплой через GitHub Actions
- Проверка сканера + поиска на стенде после SSL-фикса
- Упаковка SPA для офлайн-работы на ноутбуке-сканере

#### v2.9.0 — Логи + полный CD (сентябрь–октябрь)

Бывший v2.7.0 scope, сдвинут после фестиваля.

- Loki + Promtail + Grafana на отдельной RU VPS, retention 1+ год
- Audit-канал в Laravel: все действия всех пользователей, JSON + человеко-читаемый формат
- Логирование воронки покупки/заказа (этапы) — единый поток с audit (Ф1.A)
- Переключение Pint и PHPUnit в CI на required (снять `continue-on-error`)
- CD prod tag-based deploy + release-please
- 5 бэкапов БД перед каждым деплоем

#### v3.0.0 — Laravel 11 + OrderKind VO (TBD)

Открытый scope. Делается **после** v2.8.0 (после фестиваля), окно для major-изменений.

- Laravel 11 update (Backend, Baza, FrontEnd)
- OrderKind VO + 3 Application-сервиса (`GuestOrderApplication`, `FriendlyOrderApplication`, `ListOrderApplication`)
- Поэтапная миграция данных (без жёсткого простоя)

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

## 9. Staging deploy pipeline

`.github/workflows/deploy-staging.yml` — auto-deploy на сервер `77.222.32.244` при push в ветку `staging`. Подробности — в `infra/staging/README.md`.

### Автоматически генерируемые секреты (first deploy)

При первом запуске workflow на чистом сервере следующие секреты создаются автоматически и пишутся в `.env`:

| Секрет | Где | Как генерируется |
|--------|-----|-------------------|
| `MYSQL_ROOT_PASSWORD`, `MYSQL_PASSWORD` | `.env.staging` | `openssl rand -hex 16` в шаге Bootstrap |
| `APP_KEY` (Backend, Baza) | `Backend/.env`, `Baza/.env` | `php artisan key:generate --force` |
| `JWT_SECRET` (Backend) | `Backend/.env` | `php artisan jwt:secret --force` |

Все шаги идемпотентны: если значение уже задано в `.env` (не пустое и формат корректный) — шаг пропускается. **Никогда не перезаписываем существующие секреты.**

### Ротация

Чтобы пересоздать секрет — удалить строку из `.env` на сервере и перезапустить workflow:

```bash
ssh deploy@77.222.32.244 'sed -i "s/^JWT_SECRET=.*//g" /var/www/systo/Backend/.env'
gh workflow run deploy-staging.yml -f ref=staging
```

Workflow увидит пустой `JWT_SECRET`, сгенерирует новый, и сделает `up -d --force-recreate php-staging worker-staging` чтобы контейнеры подхватили новое значение (`env_file` читается только при создании контейнера, restart не помогает).

### Проверка применённого секрета

```bash
ssh deploy@77.222.32.244 'docker exec php-staging php -r "echo config(\"jwt.secret\");"'
```

---

## История изменений документа

| Дата | Изменение |
|------|-----------|
| 2026-05-28 | Создан документ. Roadmap 2.5.0 → 3.0.0-alpha, SemVer-правила, branching, DoD, шаблоны |
| 2026-05-30 | **Roadmap полностью переработан после встречи с организаторами.** v2.6.0 = XL-релиз (опции к билетам + новый формат заказа BREAKING + миграция + промокоды-агрегатор + qr.spaceofjoy.ru SSO Passport). Дедлайн 2026-06-12 — старт продаж осеннего фестиваля. Бывший v2.6.0 (SSL+offline) сдвинут в v2.7.0 (сентябрь). Источник: `.claude/meetings/2026-05-30/RESULTS.md`. |
| 2026-05-30 | **Scope v2.6.0 сокращён** после анализа 3 спецификаций в `.claude/specs/` (ticket-options, order-format-architecture, qr-sso-security). Все три агента независимо рекомендовали резать — объём ~29 дней не уложится в 13. **Промокоды-агрегатор и Passport SSO перенесены в v2.7.0** (июль 2026). v2.6.0 теперь L-релиз: только опции + новый формат заказа + миграция. Зафиксированы решения по 6 открытым вопросам (промокод от билета, кратность через qty, опций в createList нет, live + non-live нельзя, legacy API сразу убираем, Passport hybrid с JWT). |
| 2026-06-14 | **Разворот org → admin-only.** Roadmap §4 обновлён: добавлен `v2.7.0-alpha.1` (staging-preview, разворот org, qr-заказы, новая админка Vite+Sakai), отмечена аномалия **B-0** (v2.6.0 не затеган). Добавлен recap планов по релизам и детализация v2.7.0-alpha.1 с блокерами B-0..B-6. Новые фичи AF-1..AF-5 (дашборды, генератор шаблонов, доставка в baza, S3) разложены по v2.7.0–v2.9.0. Источник: спека `.claude/specs/admin-frontend-vite-sakai.md`. |
