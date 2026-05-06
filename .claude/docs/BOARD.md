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

### ✅ Сделано 2026-05-04
**Заказы-списки + сущность Локации.**
- 3 миграции (`locations` table, `location_id`/`curator_id` в `order_tickets`, nullable полей)
- 4 новых статуса + матрица переходов в `Status.php`, роль `curator` в `AccountRoleHelper`
- Модуль `Backend/src/Location/` (DTO, Repository, Application, CRUD контроллер, роуты)
- Расширение OrderTicket: `createList`/`toApproveList`/`toCancelList`/`toDifficultiesAroseList`, `OrderTicketDto::fromStateForList`, фильтры репозитория
- 3 новых Mailable + blade: `OrderListApproved`, `OrderListCancel`, `OrderListDifficultiesArose`
- Frontend: Vuex `LocationModule`, компоненты CRUD локаций, форма `BuyTicketLists`, `OrderLists/OrderList+FilterOrder`, views для куратора и admin/manager, роуты + меню, флаг `isCurator`
