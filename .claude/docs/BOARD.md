# 📋 Доска задач (Project Board)

Визуализация текущих проблем, задач и прогресса проекта Systo.

**Подробный roadmap версий 2.5.0 → 3.0.0-alpha** — в `.claude/docs/process/RELEASES.md`
**Технический долг** — в `.claude/docs/TECH_DEBT.md`

---

## 🗺️ Roadmap версий

| Версия | Срок | Главное | Статус |
|--------|------|---------|--------|
| **2.5.0** | 2026-05-29 | Старт версионирования, базовый CI, новые агенты, healthcheck воркера, очистка от Friendly-приложения | ✅ Выпущена |
| **2.5.1** | патч | Починка PHPUnit | ⏳ Запланировано |
| **2.6.0** | июнь 2026 | SSL для ноутбука-сканера, offline docker-compose, CD staging | ⏳ Запланировано |
| **2.7.0** | июль 2026 | Loki + Grafana, audit + воронка покупки, CD prod tag-based | ⏳ Запланировано |
| **2.8.0** | август 2026 | Laravel 11 part 1 (staging), упаковка SPA для офлайн-сканера | ⏳ Запланировано |
| **2.9.0** | август–сентябрь 2026 | OrderKind VO + 3 Application-сервиса, Laravel 11 part 2 (прод) | ⏳ Запланировано |
| **3.0.0-α** | сентябрь 2026 | После встречи с бизнесом + новый scope | 🟡 Открытый |

**Встреча с бизнесом:** 2026-05-30 (суббота). Материалы — в `.claude/meetings/2026-05-30/`.

---

## 🔄 Текущая работа

_Сейчас нет активного спринта. Следующий — v2.5.1 (патч с продолжением починки PHPUnit для Baza Risky-теста и созданием сидеров)._

---

## 🔴 Критично (High Priority)

| Задача | Ответственный | Куда |
|--------|---------------|------|
| **Race Condition воркера** — внедрить Healthcheck в docker-compose | devops-engineer | v2.5.0 |
| **Починка Unit-тестов** (PDO/Connection ошибки) | auto-tester | v2.5.1 |
| **Очистка документации от Friendly-приложения** | technical-writer | v2.5.0 |
| **Обновление Laravel 9 → 11** (заблокировано до 1 июня) | tech-lead | v2.8.0 + v2.9.0 |

## 🟡 Важно (Medium Priority)

| Задача | Ответственный | Куда |
|--------|---------------|------|
| **Sentry filter** — фильтрация шума («Промокод пустой», «DB disconnect») | devops-engineer | TECH_DEBT |
| **Альтернатива Telegram-каналу** — SMTP + чат-виджет | business-analyst | TECH_DEBT |
| **152-ФЗ compliance** — Политика+согласие уже есть, ждём помощь с РКН | security-engineer | TECH_DEBT |
| **Очистка места на VPS** — Docker логи + PDF билетов | devops-engineer | TECH_DEBT |
| **Рефакторинг Shared в Baza** — бардак в коде | tech-lead | TECH_DEBT |
| **Логирование всех действий + воронка покупки** (единый поток) | devops-engineer | v2.7.0 |

## 🟢 Low Priority

| Задача | Ответственный | Куда |
|--------|---------------|------|
| **Mobile UX — отдельная вёрстка** (Android+iPhone) | ux-ui-designer | TECH_DEBT |
| **Автоматизация бэкапов** (сейчас ручные mysqldump → к себе) | devops-engineer | TECH_DEBT |

---

## ✅ Сделано

### 2026-05-29 — v2.5.0 «Старт версионирования» 🎉

**Первая официальная версия проекта. Фундамент процесса релизов и инфраструктуры.**

- 🏷️ Тег: `v2.5.0`
- 📜 [CHANGELOG.md §2.5.0](../../CHANGELOG.md)
- 🌿 PR: feat/v2.5.0-foundation → master (#35), 16 коммитов
- 📦 Релиз: GitHub Release (см. https://github.com/ShaevMV/systo/releases/tag/v2.5.0)

**Что вошло:**
- Процесс релизов: `RELEASES.md` (SemVer + branching + DoD + roadmap), `CHANGELOG.md`
- CI на GitHub Actions: 7 параллельных job (lint Backend/Baza/Frontend, build, audit, PHPUnit Backend/Baza), MySQL service, кэш, триггеры push + PR
- husky + commitlint (мягкий warning, conventional commits)
- Makefile: 30+ целей с группами + `make help`
- Агенты команды: `scrum-master`, `security-engineer`
- Материалы к встрече с организаторами 2026-05-30
- Очистка документации от Friendly-приложения (и косвенно от List)
- TD-1 закрыт (Race Condition воркера на проде через healthcheck)
- TD-2 закрыт частично (Backend 55 тестов 0 ошибок, Baza 7 тестов 0 ошибок 2 skipped)
- TD-3 закрыт (cleanup от Friendly + List)
- Починка docker-compose.prod.yml (удалены мёртвые phpFriendly, phpList, database)
- Починка Baza/composer.lock (endroid/qr-code добавлен)

### 2026-05-15 — Авто-одобрение заказа

**Заголовок `AutoPayment` на `POST /api/v1/order/create`.**
- Конфиг: `AUTO_PAYMENT_TOKEN` в `.env.example` + `config/services.php → auto_payment.token`
- `ActorType::AUTO_PAYMENT` (`auto_payment`) добавлен в `Backend/src/History/Domain/ActorType.php`
- `TypesOfPaymentDto::isBilling()` — геттер для проверки биллингового способа оплаты
- `OrderTickets::create()` — проверка заголовка `AutoPayment` до создания: невалидный токен → `403`, валидный + не-биллинговый способ оплаты → после `createAndSave` вызывается `ChangeStatus` с `Status::PAID`
- Сравнение токенов через `hash_equals` (защита от timing-attack)
- Документация: `API.md`, `BUSINESS_RULES.md`, `DOMAIN.md`
- 🌿 ветка `feat/auto-payment-token`

### 2026-05-10 — CRUD для волн цен типа билета

**`ticket_type_price`.**
- Backend модуль `Backend/src/TicketTypePrice/` (DTO, Repository + Interface, Application, Create/Edit/Delete/GetList/GetItem handlers)
- Контроллер `TicketTypePriceController` с FormRequest-валидацией
- Роуты `/api/v1/ticketTypePrice/*` — read публичный, write только `auth:api + admin`
- SoftDeletes в `TicketTypesPriceModel` + кастинг `price`/`before_date`
- Защита от дурака: `price > 0` и `< 1 000 000`, `before_date` не в прошлом, `ticket_type_id` exists
- Frontend: Vuex `TicketTypePriceModule` + компонент `TicketTypePriceList.vue`, встроен в форму редактирования типа билета
- 🌿 ветка `feat/ticket-type-price-crud`

### 2026-05-04 — Заказы-списки + сущность Локации

**Третий тип заказа (для куратора).**
- 3 миграции (`locations` table, `location_id`/`curator_id` в `order_tickets`, nullable полей)
- 4 новых статуса + матрица переходов в `Status.php`, роль `curator` в `AccountRoleHelper`
- Модуль `Backend/src/Location/` (DTO, Repository, Application, CRUD контроллер, роуты)
- Расширение OrderTicket: `createList`/`toApproveList`/`toCancelList`/`toDifficultiesAroseList`, `OrderTicketDto::fromStateForList`, фильтры репозитория
- 3 новых Mailable + blade: `OrderListApproved`, `OrderListCancel`, `OrderListDifficultiesArose`
- Frontend: Vuex `LocationModule`, компоненты CRUD локаций, форма `BuyTicketLists`, `OrderLists/OrderList+FilterOrder`, views для куратора и admin/manager, роуты + меню, флаг `isCurator`

### Более ранние работы

- ✅ Настройка команды агентов (10 агентов, политики, память)
- ✅ Анкета нового пользователя (тип по коду `new_user`)
- ✅ Исправление опечаток (Friendly, ChangeStatus/Role)
- ✅ Создание Project Memory (контекст и предпочтения)
- ✅ Создание Project Board (визуализация задач)
- ✅ История заказов (модуль History, Event Sourcing, UI для admin)
- ✅ **Friendly-приложение удалено** — осталось 3 приложения в монорепо

---

## 📝 Заметки

- Пользователь признался что не читает BOARD.md напрямую, но соглашается что доска нужна для агентов и истории. **Главное — поддерживать актуальность.**
- Подробные правила версионирования и план каждой версии — в `RELEASES.md` (этот файл — только верхнеуровневая сводка).
- Технический долг с детализацией — в `TECH_DEBT.md`.
