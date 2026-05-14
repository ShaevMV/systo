# 📋 Доска задач (Project Board)

Визуализация текущих проблем, задач и прогресса проекта Systo.

---

| 🔴 Критично (High) | 🟡 Важно (Medium) | ✅ Готово (Done) |
| :--- | :--- | :--- |
| **Починить Unit-тесты** <br>*(Ошибка подключения PDO/Driver)* <br>👤 *Auto-Tester* | **Очистка места на VPS** <br>*(Логи Docker, старые образы)* <br>👤 *DevOps* | **Настройка Команды Агентов** <br>*(10 агентов, политики, память)* |
| **Фикс Race Condition Воркера** <br>*(Внедрить Healthchecks вместо sleep)* <br>👤 *DevOps* | **Фильтрация шума в Sentry** <br>*(Экономия лимита 5000/мес)* <br>👤 *DevOps* | **Анкета нового пользователя** <br>*(Тип по коду `new_user`)* |
| **Обновление Laravel 11** <br>*(ЗАБЛОКИРОВАНО до 1 ИЮНЯ)* <br>👤 *Tech Lead* | **Настройка CI/CD (GitHub Actions)** <br>*(Автотесты и деплой)* <br>👤 *DevOps* | **Исправление опечаток** <br>*(Friendly, ChangeStatus/Role)* |
| | **Рефакторинг Shared в Baza** <br>*(Бардак в коде приложения)* <br>👤 *Tech Lead* | **Создание Project Memory** <br>*(Контекст и предпочтения)* |
| | **Логирование действий админов** <br>*(Безопасность)* <br>👤 *DevOps* | **Создание Project Board** <br>*(Визуализация задач)* |
| **🧸 ЗАВТРА: Детский билет** | | **История заказов** <br>*(модуль History, Event Sourcing, UI для admin)* |
| | **📧 Friendly email при смене статуса** <br>*(ChangeStatusCommandHandler → friendly email)* <br>👤 *Business Analyst* | **📋 Заказы-списки + Локации** <br>*(новый тип заказа для куратора, сущность Location, статусы NEW_LIST/APPROVE_LIST/CANCEL_LIST/DIFFICULTIES_AROSE_LIST, роль curator, OrderListApproved Mailable)* <br>🌿 ветка `feat/order-lists-with-locations` |

---

### 📝 Статус
- **Всего задач в бэклоге:** 6
- **В работе:** 0
- **Критичных проблем:** 2 (Тесты, Воркер)

### ✅ Сделано 2026-05-15
**Авто-одобрение заказа по заголовку `AutoPayment` на `POST /api/v1/order/create`.**
- Конфиг: `AUTO_PAYMENT_TOKEN` в `.env.example` + `config/services.php → auto_payment.token`
- `ActorType::AUTO_PAYMENT` (`auto_payment`) добавлен в `Backend/src/History/Domain/ActorType.php`
- `TypesOfPaymentDto::isBilling()` — геттер для проверки биллингового способа оплаты
- `OrderTickets::create()` — проверка заголовка `AutoPayment` до создания: невалидный токен → `403`, валидный + не-биллинговый способ оплаты → после `createAndSave` вызывается `ChangeStatus` с `Status::PAID` (запускается `ProcessCreateTicket` + email с PDF); биллинговый способ — заголовок игнорируется
- Сравнение токенов через `hash_equals` (защита от timing-attack)
- Документация: `API.md` (раздел `/api/v1/order/create` — заголовки и поведение), `BUSINESS_RULES.md` (§1 Роли для смены статуса), `DOMAIN.md` (ActorType)
- 🌿 ветка `feat/auto-payment-token`

### ✅ Сделано 2026-05-10
**CRUD для волн цен типа билета (`ticket_type_price`).**
- Backend модуль `Backend/src/TicketTypePrice/` (DTO, Repository + Interface, Application, Create/Edit/Delete/GetList/GetItem handlers)
- Контроллер `TicketTypePriceController` с FormRequest-валидацией
- Роуты `/api/v1/ticketTypePrice/*` — read публичный, write только `auth:api + admin`
- SoftDeletes в `TicketTypesPriceModel` + кастинг `price`/`before_date`
- Защита от дурака: `price > 0` и `< 1 000 000`, `before_date` не в прошлом, `ticket_type_id` exists; на фронте — disable кнопки, `confirm()` на удаление
- Frontend: Vuex `TicketTypePriceModule` + компонент `TicketTypePriceList.vue`, встроен в форму редактирования типа билета (`TicketTypeItem.vue`)
- 🌿 ветка `feat/ticket-type-price-crud`

### ✅ Сделано 2026-05-04
**Заказы-списки + сущность Локации.**
- 3 миграции (`locations` table, `location_id`/`curator_id` в `order_tickets`, nullable полей)
- 4 новых статуса + матрица переходов в `Status.php`, роль `curator` в `AccountRoleHelper`
- Модуль `Backend/src/Location/` (DTO, Repository, Application, CRUD контроллер, роуты)
- Расширение OrderTicket: `createList`/`toApproveList`/`toCancelList`/`toDifficultiesAroseList`, `OrderTicketDto::fromStateForList`, фильтры репозитория
- 3 новых Mailable + blade: `OrderListApproved`, `OrderListCancel`, `OrderListDifficultiesArose`
- Frontend: Vuex `LocationModule`, компоненты CRUD локаций, форма `BuyTicketLists`, `OrderLists/OrderList+FilterOrder`, views для куратора и admin/manager, роуты + меню, флаг `isCurator`
