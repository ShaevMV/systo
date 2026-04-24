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
| | **📧 Friendly email при смене статуса** <br>*(ChangeStatusCommandHandler → friendly email)* <br>👤 *Business Analyst* | **История заказов** <br>*(модуль History, Event Sourcing, UI для admin)* |
| | | **Этап 1: Локации + Куратор** <br>*(Location CRUD, роли curator/curator_pusher, статусы new_for_list/pending_curator, is_list_ticket, createCurator API)* |
| | | **Этап 2: Анкета участника с фото** <br>*(questionnaire_type `curator_participant`, uploadPhoto endpoint, 10 тестов)* |

---

### 📝 Статус
- **Всего задач в бэклоге:** 6
- **В работе:** 0
- **Критичных проблем:** 2 (Тесты, Воркер)
