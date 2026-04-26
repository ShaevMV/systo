# Соглашения по разработке Systo

## 1. Commit Message Convention

Используем **Conventional Commits**:

```
<type>(<scope>): <description>

[optional body]

[optional footer(s)]
```

### Types

| Type | Описание | Пример |
|------|----------|--------|
| `feat` | Новая функциональность | `feat(order): добавить комментарий к заказу` |
| `fix` | Исправление бага | `fix(promocode): исправить проверку лимита` |
| `refactor` | Рефакторинг без изменения поведения | `refactor(ticket): вынести генерацию QR в сервис` |
| `docs` | Изменение документации | `docs: обновить API спецификацию` |
| `style` | Форматирование, без изменения логики | `style: применить Laravel Pint` |
| `test` | Добавление/изменение тестов | `test(order): добавить тест смены статуса` |
| `chore` | Рутинные задачи, зависимости | `chore: обновить composer зависимости` |
| `perf` | Оптимизация производительности | `perf: добавить индекс на email в заказах` |

### Scope (обязательный)

| Scope | Когда использовать |
|-------|-------------------|
| `order` | Заказы, смена статуса, список |
| `ticket` | Билеты, QR, PDF, live-номера |
| `promocode` | Промокоды, скидки |
| `festival` | Фестивали, типы билетов, цены |
| `questionnaire` | Анкеты гостей |
| `user` / `account` | Пользователи, роли, аутентификация |
| `billing` | Оплата, webhook |
| `frontend` | Vue.js компоненты, Vuex |
| `shared` | Общая библиотека |
| `infra` | Docker, CI/CD, конфиги |

### Именование веток

```
<type>/<scope>-<короткое-описание>
```

**Примеры:**
- `feat/order-add-comment`
- `fix/promocode-limit-check`
- `refactor/ticket-qr-service`
- `docs/api-specification`

---

## 2. Backend — структура модулей

### Стандартная структура модуля

```
src/ModuleName/
├── Application/
│   ├── Create/
│   │   ├── CreateModuleCommand.php
│   │   └── CreateModuleCommandHandler.php
│   ├── GetList/
│   │   ├── GetListModuleQuery.php
│   │   └── GetListModuleQueryHandler.php
│   └── ...
├── Domain/
│   └── ModuleEntity.php              # AggregateRoot
├── Dto/
│   └── ModuleDto.php                 # extends AbstractionEntity
├── Repositories/
│   ├── ModuleRepositoryInterface.php
│   └── InMemoryMySqlModuleRepository.php
├── Responses/
│   └── ModuleResponse.php            # extends AbstractionEntity, implements Response
└── Services/
    └── ModuleService.php
```

**Примечание:** Не все модули следуют стандартной структуре. Например:
- `Questionnaire/Application/Questionnaire/` — содержит поддиректории `Approve`, `Create`, `GetItem` и т.д. вместо плоской структуры
- `PromoCode/Application/` — содержит `CreatePromoCode`, `GetPromoCodes`, `SearchPromoCode`, `ExternalPromocode`
- `Ticket/Live/` — содержит `Aplication` и `Service` (без Domain)
- `Festival/` — содержит `Application`, `DTO`, `Repositories`, `Response` (без Domain)

### Именование классов

| Тип | Паттерн | Пример |
|-----|---------|--------|
| Command | `<Action><Entity>Command` | `CreateTicketCommand`, `CancelOrderCommand` |
| CommandHandler | `<Action><Entity>CommandHandler` | `CreateTicketCommandHandler` |
| Query | `<Action><Entity>Query` | `GetOrderListQuery`, `FindPromoCodeQuery` |
| QueryHandler | `<Action><Entity>QueryHandler` | `GetOrderListQueryHandler` |
| DTO | `<Entity>Dto` | `OrderTicketDto`, `GuestsDto` |
| Response | `<Entity>Response` | `OrderTicketItemResponse` |
| Repository Interface | `<Entity>RepositoryInterface` | `OrderTicketRepositoryInterface` |
| Repository Impl | `InMemoryMySql<Entity>Repository` или `<Entity>Repository` | `InMemoryMySqlOrderTicketRepository` |
| Domain Event | `Process<Action><Entity>` | `ProcessCreateTicket`, `ProcessCancelTicket` |
| Value Object | `<Name>ValueObject` | `StatusForBillingValueObject` |
| Service | `<Name>Service` | `BillingService`, `PriceService` |
| Controller | `<Name>Controller` | `OrderTickets`, `FestivalController` |

### Правила CQRS

1. **Command** — изменяет состояние (Create, Update, Delete)
2. **Query** — только читает данные
3. **CommandHandler** — принимает Command, вызывает Repository, возвращает CommandResponse
4. **QueryHandler** — принимает Query, возвращает Response
5. Команды и квери — **immutable DTO** (только свойства, без логики)
6. Handlers — **один метод `__invoke()`** с типизированным параметром

### Правила DDD

1. **AggregateRoot** — единственный entry point в агрегат
2. **Value Objects** — неизменяемы, без идентификатора
3. **Domain Events** — генерируются через `record()`, извлекаются через `pullDomainEvents()`
4. **Repository** — интерфейс в Domain, реализация в Repositories/
5. **DTO** — наследуют `AbstractionEntity`, сериализуются автоматически

### ⚠️ Сериализация коллекций в репозиториях (ОБЯЗАТЕЛЬНО)

**Проблема:** `Collection::toArray()` вызывает `toArray()` только на объектах, реализующих `Arrayable`. `AbstractionEntity` не реализует `Arrayable` — при `json_encode` DTO-объекты сериализуются как `{}`.

**Правило:** В методах `getList()` репозитория использовать `each()`, а не `map()`:

```php
// ✅ ПРАВИЛЬНО — коллекция содержит Eloquent-модели, которые реализуют Arrayable
return $build->get()
    ->each(fn(LocationModel $model) => LocationDto::fromState($model->toArray()));

// ❌ НЕПРАВИЛЬНО — коллекция содержит DTO без Arrayable → json_encode выдаёт {}
return $build->get()->map(
    fn(LocationModel $model) => LocationDto::fromState($model->toArray())
);
```

**Эталонная реализация:** `InMemoryTicketTypeRepository::getList()`

### Регистрация в DI (TicketsProvider)

```php
// app/Providers/TicketsProvider.php
$this->app->bind(
    OrderTicketRepositoryInterface::class,
    InMemoryMySqlOrderTicketRepository::class
);
```

---

## 3. Frontend — структура модулей

### Vuex модуль

```
src/store/modules/ModuleNameModule/
├── index.js          # Экспорт state, getters, actions, mutations
├── getters.js
├── actions.js
└── mutations.js
```

### Именование

| Тип | Паттерн | Пример |
|-----|---------|--------|
| Vuex модуль | `app<Name>` | `appOrder`, `appTicketType` |
| Action | camelCase | `loadList`, `create`, `edit`, `setFilter` |
| Mutation | snake_case (caps) | `SET_LIST`, `SET_ITEM`, `SET_LOADING`, `SET_ERROR` |
| Getter | camelCase | `getList`, `getItem`, `getError`, `isLoading` |
| Компонент | PascalCase | `OrderList.vue`, `TicketTypeItem.vue` |
| View | PascalCase + View | `OrderView.vue`, `TicketTypeListView.vue` |

### Паттерн CRUD-модуля (Vuex)

**State:**
```js
state: {
    list: [],
    item: {},
    filter: {},
    orderBy: {},
    isLoading: false,
    dataError: [],
    message: null,
}
```

**Actions:** `loadList`, `loadItem`, `create`, `edit`, `remove`, `setFilter`, `setOrderBy`, `clearError`

**Callback-паттерн:**
```js
// action
actions: {
    loadList(context, payload) {
        axios.post('/api/v1/.../getList', context.state.filter)
            .then(response => {
                context.commit('SET_LIST', response.data.list);
                if (payload.callback) payload.callback();
            })
            .catch(error => context.commit('setError', error.response.data.errors));
    }
}
```

---

## 4. Код-стайл

### Backend (PHP)

- **PSR-12** — стандарт кодирования
- **Laravel Pint** — автоформатирование (`./vendor/bin/pint`)
- **Strict typing** — `declare(strict_types=1);` в начале файлов
- **Сообщения валидации** — все сообщения об ошибках валидации пишутся **на русском языке** (в `validate()`, `FormRequest`, `$messages`, `throw new DomainException()`)
- **Type hints** — все параметры и возвращаемые значения типизированы
- **Constructor property promotion** — PHP 8 (где применимо)
- **Использование Shared** — Value Objects, Criteria, AggregateRoot из Shared

### Frontend (Vue/JS)

- **ESLint** — `plugin:vue/vue3-essential` + `eslint:recommended`
- **2 пробела** — отступы
- **PascalCase** — компоненты Vue
- **camelCase** — переменные, функции, computed
- **kebab-case** — имена файлов компонентов
- **Одна зона ответственности** — один компонент = одна задача

---

## 5. Миграции БД

### Именование

```
YYYY_MM_DD_HHMMSS_create_<table_name>_table.php
YYYY_MM_DD_HHMMSS_add_<column>_to_<table>_table.php
```

### Правила

1. UUID в качестве первичного ключа (не auto-increment)
2. Использовать trait `HasUuid` из Shared для моделей
3. `incrementing = false`, `keyType = 'string'` в модели
4. Индексы на полях фильтрации (`email`, `status`, `festival_id`)
5. Foreign keys — где логически необходимо

---

## 6. API Response Format

### Успешный ответ

```json
{
  "success": true,
  "data": { ... },
  "message": "Опциональное сообщение"
}
```

### Ошибка валидации

```json
{
  "success": false,
  "errors": {
    "email": ["Неверный формат email"],
    "name": ["Поле обязательно"]
  }
}
```

### Ошибка общая

```json
{
  "success": false,
  "message": "Описание ошибки"
}
```

---

## 7. Исключения и ошибки

### Backend

- **DomainError** (абстрактный) — базовый для доменных ошибок
- `errorCode()` + `errorMessage()` — обязательные методы
- Бросать в Value Objects при невалидных данных

### Frontend

- Ошибки API — в `state.dataError` через mutation `setError`
- Общий геттер: `getError('fieldName')` — возвращает первую ошибку поля

---

## 8. Единый формат данных (Eloquent)

**Правило: каждый тип данных обрабатывается ровно в одном месте. Не дублировать преобразования.**

### Eloquent касты — единственный источник форматирования

| Тип поля | Как хранить | Как читать |
|----------|-------------|------------|
| JSON-поле (`payload`, `data`) | Передавать **сырой массив** в `create()`/`update()` | Получать **массив** напрямую — Eloquent декодирует |
| Временны́е метки (`occurred_at`, `created_at`) | **Не задавать в PHP** — пусть DB `DEFAULT` устанавливает | Получать **Carbon-объект** напрямую из модели |
| `datetime`-каст | — | Не оборачивать в `Carbon::parse()` — каст уже вернул Carbon |

### Запрещено при работе с Eloquent

```php
// ❌ Двойное кодирование — Eloquent уже делает json_encode через каст 'array'
'payload' => json_encode($array),

// ✅ Правильно — передаём массив, Eloquent сам кодирует
'payload' => $array,

// ❌ Дублирование — DB уже имеет DEFAULT CURRENT_TIMESTAMP
'occurred_at' => Carbon::now()->toDateTimeString(),

// ✅ Правильно — убираем поле, DB сам ставит время

// ❌ Лишнее — каст 'datetime' уже вернул Carbon
occurredAt: Carbon::parse($row->occurred_at),

// ✅ Правильно — используем напрямую
occurredAt: $row->occurred_at,
```

---

## 9. Запрещено

| Что | Почему |
|-----|--------|
| Изменять Shared без анализа влияния на все приложения | Shared используют 4 приложения |
| Добавлять бизнес-логику в контроллеры | Нарушает CQRS, логика — в Application слое |
| Дублировать код вместо переиспользования | Есть Shared и устоявшиеся паттерны |
| Хардкодить URL-ы, секреты, токены | Использовать `.env` |
| Игнорировать матрицу переходов статусов | Бизнес-критичная логика |
| Создавать мутации с побочными эффектами | Мутации Vuex только синхронные изменения |
| Вручную кодировать/декодировать поля с Eloquent-кастами | Каст — единственный источник форматирования |
| Дублировать установку `occurred_at`/`created_at` в PHP когда есть DB DEFAULT | Две точки управления одним полем |
| Сообщения об ошибках валидации на английском | Пользователи видят эти сообщения — они должны быть на русском |
