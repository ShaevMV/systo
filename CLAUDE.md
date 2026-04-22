# Systo Project — Claude Code Instructions

## Обязательные правила разработки

### 1. Чистая многоуровневая архитектура
**Обращение к базе данных происходит ТОЛЬКО внутри репозитория.**
- QueryHandler, CommandHandler, контроллеры, сервисы — НЕ обращаются к моделям/БД напрямую.
- Все обращения к БД — через методы репозитория.
- Источник: Роберт Мартин — «Чистая архитектура», глава «Зависимости» (Dependency Rule).

### 2. Коммиты только после одобрения пользователя
**Без явной проверки кода и подтверждения от пользователя — коммит НЕ делаем.**
- Сначала показываешь изменения пользователю.
- Ждёшь явного одобрения.
- Только потом делаешь коммит.

### 3. Главное правило: смотреть как было реализовано ранее
**Перед написанием нового кода — изучи существующую реализацию.**
- Смотри на схожий, уже написанный код в проекте.
- Если новая реализация НЕ соответствует существующей — **СПРОСИ у пользователя** как именно реализовывать.
- Не изобретай паттерны — используй те, что уже есть в проекте.

### 4. При неопределённости — задавай вопросы
Не принимай решения за пользователя, если что-то неоднозначно. Уточняй детали перед тем, как писать код.

### 5. Пользователь может допускать ошибки в словах (дислексия)
Каждый раз уточняй — правильно ли ты понял задачу. Старайся понять суть и детали написанного, а не только поверхностное описание.

### 6. Отвечай на русском языке
Все комментарии, объяснения и общение — на русском.

### 7. CQRS и SOLID
Если есть сомнения в правильности реализации — код написан в парадигме CQRS с поддержанием принципов SOLID.

### 8. Актуализируй документацию при глобальных изменениях
Файлы в `.claude/docs/` должны отражать актуальное состояние проекта.

---

## Обзор проекта

**Systo** — монорепозиторий с микросервисной архитектурой на базе **Laravel** (backend) и **Vue.js 3** (frontend), система продажи и управления билетами на фестивали.

### Компоненты

| Компонент | Описание | Технологии |
|-----------|----------|------------|
| **Backend** | Основное приложение SolarSysto | Laravel 9, PHP 8.2 |
| **Baza** | Аутентификация | Laravel 9, PHP 8.2 |
| **Friendly** | Дружественные заказы | Laravel 8, PHP 8.2 |
| **FrontEnd** | Vue.js SPA | Vue 3, Vuex, Vue Router |
| **Shared** | Общий код (CQRS, DDD, VO) | PHP PSR-4 |

### Архитектурные паттерны

- **CQRS** через Symfony Messenger: `Command → CommandBus → CommandHandler → Repository`
- **DDD**: AggregateRoot, Value Objects, Domain Events, Repository Pattern
- **Event Sourcing** (частичный): RabbitMQ (primary) → MySQL Outbox (failover)

### Структура модуля Backend

```
src/ModuleName/
├── Application/        # Command/Query + Handlers
├── Domain/             # AggregateRoot, Value Objects, Events
├── Dto/                # Data Transfer Objects
├── Repositories/       # Interface + MySQL Implementation
├── Responses/          # Response DTOs
└── Services/           # Сервисы модуля
```

### Роли пользователей

| Роль | Описание |
|------|----------|
| `guest` | Гость фестиваля |
| `admin` | Администратор |
| `seller` | Продавец живых билетов |
| `pusher` | Продавец Friendly-билетов |
| `manager` | Менеджер |

### Машина состояний заказов

`new` → `paid` / `cancel` / `difficulties_arose` / `live_ticket_issued`
Живые билеты: `new_for_live` → `paid_for_live` / `live_ticket_issued` / `cancel_for_live`

---

## Команды Docker

```bash
# Запустить контейнеры
docker-compose up -d

# PHP контейнер
docker exec -it php-solarSysto bash

# Сборка фронтенда
docker exec -it -u0 node-solarSysto npm run build

# Сброс кеша Laravel
docker exec -it php-solarSysto php artisan optimize:clear

# Запуск тестов (ТОЛЬКО через Docker!)
docker exec -it php-solarSysto ./vendor/bin/phpunit
```

---

## Документация проекта

@.claude/docs/RULES.md
@.claude/docs/BUSINESS_RULES.md
@.claude/docs/API.md
@.claude/docs/DOMAIN.md
@.claude/docs/CONVENTIONS.md
@.claude/docs/TECH_DEBT.md
@.claude/docs/PROJECT_MEMORY.md
@.claude/docs/BOARD.md
