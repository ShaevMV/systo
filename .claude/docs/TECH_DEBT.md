# Технический долг (Tech Debt)

Список улучшений, которые не критичны сейчас, но важны для будущего.

---

| Приоритет | Описание | Кто предложил | Дата | Срок |
|-----------|----------|---------------|------|------|
| 🔴 High | Обновление Laravel 9 → 11 | Tech Lead | 2026-04-04 | **После 1 июня** |
| 🔴 High | Реальная фикс Race Condition воркера (Healthchecks) | Tech Lead | 2026-04-04 | **До фестиваля** |
| 🔴 High | Восстановление и починка Unit-тестов (PDO/Connection) | Auto-Tester | 2026-04-04 | ASAP |
| 🔴 High | Исправить фильтрацию `festivalId` в Vuex store OrderModule | Frontend Helper | 2026-04-12 | Сделано |
| Medium | Рефакторинг использования Shared кода в Baza | Tech Lead | 2026-04-04 | Когда будет время |
| Medium | Покрыть тестами контроллеры (HTTP слой) | Auto-Tester | 2026-04-04 | — |
| Medium | Логирование действий админов (смена статусов, удаление) | DevOps | 2026-04-04 | — |
| Medium | Настроить CI/CD пайплайн (GitHub Actions) | DevOps | 2026-04-04 | — |
| Medium | Миграция Bootstrap 4 → 5 (модалки через vanilla JS) | Frontend Helper | 2026-04-12 | — |
| Low | Миграция на Vue Router lazy loading | Frontend Helper | 2026-04-04 | — |
| Low | Заменить jQuery модалки на нативные Vue | Tester | 2026-04-04 | — |
| Low | Единый Response Interceptor для Axios | Frontend Helper | 2026-04-04 | — |
| Low | Тёмная тема (хотят пользователи) | UX/UI Designer | 2026-04-04 | — |
| Low | TypeScript для фронтенда (отложено) | Tech Lead | 2026-04-04 | Когда-нибудь |
| Medium | **Абстракция шаблонов писем и PDF-билетов через типы заказов** — см. детали ниже | shaevmv | 2026-05-01 | Финальный этап order-abstraction |

---

---

## TODO: Абстракция шаблонов писем и PDF-билетов через типы заказов

**Текущее состояние (проблема):**
- Логика выбора шаблона письма сейчас разрознена:
  - `OrderToPaid` Mailable: проверяет `$ticket->getEmailView()` (из `TicketResponse`)
  - `ProcessCreatingQRCode`: проверяет `$ticket->getFestivalView()` (null = без PDF)
  - Специальный кейс детского билета (`CHILD_TICKET_TYPE_ID`) — проверяется в каждом типе заказа отдельно
  - Какой шаблон → определяется типом билета + типом оплаты, но это **не декларируется явно**

**Предлагаемое решение:**
Вынести декларацию шаблонов в абстрактный тип заказа. Добавить в `BaseOrder`:

```php
// Какой email-шаблон отправлять при оплате (view из resources/views/email/)
abstract public function getEmailTemplate(): string;

// Какой PDF-шаблон использовать для генерации билета (null = без PDF)
abstract public function getPdfTemplate(): ?string;

// Нужно ли отправлять анкеты гостям (false = детский/parking)
abstract public function shouldSendQuestionnaire(): bool;
```

**Что даёт:**
- Единое место решения: «гостевой заказ использует шаблон `orderToPaid`, live — без PDF»
- Убирает хардкод `CHILD_TICKET_TYPE_ID` из проверок `isChildTicket()` — заменяется на `shouldSendQuestionnaire()`
- Возможность менять шаблоны без поиска по всему коду

**Заблокировано:** Требует рефакторинга `ProcessUserNotificationOrderPaid` и всех Mailable-классов. Делать после стабилизации Application слоя новых типов заказов.

---

**Правило:** PM напоминает об этом файле раз в 5-10 коммитов.

**Критично до 1 июня:** Только фиксы багов, никаких Laravel-апдейтов.
