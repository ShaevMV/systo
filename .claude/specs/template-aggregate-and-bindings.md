# Спека: Template как DDD-агрегат с историей + привязка шаблонов к действиям

**Статус:** черновик на согласование (2026-06-15). Источник: запрос владельца.
**Связано:** `.claude/specs/template-system.md` (AF-3, фазы 1–5), `DOMAIN.md` (Template, OrderTicket+HasHistory).

Две независимые, но связанные задачи:
- **Часть A** — поднять `Template` из пассивной сущности в **AggregateRoot** с записью истории изменений в `domain_history` (как `OrderTicket`).
- **Часть B** — **привязка шаблонов к действиям**: настройка «какое письмо и какой PDF-билет использовать» для пары **(тип заказа + тип билета)** с **шаблоном по умолчанию**.

---

## Часть A — Template → AggregateRoot + история

### Зачем
Сейчас `Template` — пассивная сущность (DTO + репозиторий), история тела ведётся только в `template_versions` (снапшоты при publish/rollback). Нет **аудита действий** (кто и когда создал/отредактировал/активировал/опубликовал/откатил шаблон) — а это редактируемое из админки оформление писем/билетов, изменения важно отслеживать (как у заказов).

### Целевой дизайн (по образцу `OrderTicket`)

**Домен — новый агрегат** `Backend/src/Template/Domain/Template.php`:
```php
final class Template extends AggregateRoot
{
    use HasHistory;

    // Фабрики (каждая пишет recordHistory(...)):
    public static function create(TemplateDto $dto, ?string $actorId): self        // → TemplateCreatedEvent
    public function edit(TemplateDto $changes): void                                // → TemplateEditedEvent (что менялось)
    public function activate(bool $active): void                                    // → TemplateActivatedEvent (on/off)
    public function publish(string $body, ?string $comment): void                   // → TemplatePublishedEvent
    public function rollback(Uuid $versionId, Carbon $versionDate): void            // → TemplateRolledBackEvent
}
```
> `saveDraft` — **без** истории (черновик, прод не трогается). `edit` метаданных и `publish` тела — с историей.

**События истории** `Backend/src/History/Domain/Event/Template*Event.php` (реализуют `HistoryEventInterface`, `getAggregateType() = 'template'`):

| Событие | event_name | payload |
|---------|-----------|---------|
| `TemplateCreatedEvent` | `template_created` | `{slug, kind, title}` |
| `TemplateEditedEvent` | `template_edited` | `{changed: [поля]}` (без тел — они тяжёлые) |
| `TemplateActivatedEvent` | `template_activated` | `{active: true|false}` |
| `TemplatePublishedEvent` | `template_published` | `{comment, version_id}` |
| `TemplateRolledBackEvent` | `template_rolled_back` | `{to_version_id, to_date}` |

**Actor:** админ — `Auth::id()` (Uuid VO → `->value()`), `ActorType::USER`. Из artisan (`templates:import-blade`/`sync-converted`) — `ActorType::ARTISAN`, `actor_id = null`.

**Интеграция (Application).** Текущие методы `TemplateApplication`/хендлеры тонкие (зовут репозиторий). Переводим на паттерн `OrderTicket`:
```php
// пример: publish
$template = Template::fromDto($repo->getItem($id));   // восстановили агрегат
$template->publish($body, $comment);                  // записал TemplatePublishedEvent
$repo->publish($id, $body, $actorId, $comment);       // персист тела + снапшот версии (как сейчас)
foreach ($template->pullHistoryEvents() as $e) {      // как ChangeStatusCommandHandler
    $historyRepo->save(new SaveHistoryDto($id->value(), $e, $actorId, ActorType::USER));
}
```
БД-доступ остаётся **только в репозитории** (Dependency Rule). История — через существующий `HistoryRepositoryInterface` → таблица `domain_history` (новый `aggregate_type='template'`, миграций не нужно — таблица общая).

**Новый эндпоинт чтения истории:** `GET /api/v1/template/history/{id}` (`auth:api`+`admin`) → таймлайн событий шаблона (как `order/getHistory`). Экран: вкладка «История» в редакторе шаблона (рядом с «Версии»).

> **Версии vs история — не дублируются:** `template_versions` = снапшоты **тела** (для отката/diff), `domain_history` = **журнал действий** (кто/что/когда, аудит). Обе остаются.

### Тест-план — Часть A
**Unit (домен, без БД):**
- `Template::create()` → ровно 1 `TemplateCreatedEvent`, payload `{slug,kind,title}`, `pullHistoryEvents()` очищает буфер.
- `edit()` со сменой title+body → `TemplateEditedEvent.payload.changed` = `['title','body']`; без изменений → событие не пишется (как `toChangeTicket` при `empty($changes)`).
- `activate(true/false)` → `TemplateActivatedEvent.payload.active` соответствует.
- `publish()` → `TemplatePublishedEvent`; `rollback()` → `TemplateRolledBackEvent` с `to_version_id`.
- `getAggregateType()==='template'` у всех событий.

**Feature (через Application + БД, `RefreshDatabase`):**
- `publish` пишет строку в `domain_history` (`aggregate_type=template`, `event_name=template_published`, `actor_id`=мок-admin, `actor_type=user`).
- `activate`/`create`/`rollback` — по одной корректной записи истории каждое.
- `saveDraft` **не** пишет историю.
- `GET /template/history/{id}` (admin) → события по возрастанию `occurred_at`; non-admin → 403; auth нет → 401.
- artisan-импорт пишет `actor_type=artisan`, `actor_id=null`.

---

## Часть B — Привязка шаблонов к (тип заказа + тип билета) + дефолт

### Идея владельца (дословно)
> Выбирается **тип заказа** и **тип билета** под него → выбирается **письмо** и сам **шаблон билета** (PDF). Должен учитываться **шаблон по умолчанию**.

### Что есть сейчас (и проблема)
Привязка «зашита» в `ticket_type_festival.email/.pdf` (slug по festival+ticket_type) + хардкод-развилки по типу заказа (friendly/ list/ live — разные Mailable и slug-логика). Менять — только через правку БД/кода. Нет единого админ-экрана «для такого заказа такого билета — вот это письмо и этот билет», нет настраиваемого дефолта.

### Целевой дизайн

**Новая сущность `TemplateBinding`** (модуль `Backend/src/TemplateBinding/`, пассивная — как `Location`; БД только в репозитории):

| Поле | Тип | Смысл |
|------|-----|-------|
| `id` | uuid | PK |
| `festival_id` | uuid **NULL** | фестиваль или «любой» (wildcard) |
| `order_type` | string **NULL** | `regular`/`friendly`/`list`/`live` или «любой» |
| `ticket_type_id` | uuid **NULL** | тип билета или «любой» |
| `email_template_id` | uuid **NULL** | какой шаблон письма (FK `templates`, kind=email) |
| `pdf_template_id` | uuid **NULL** | какой шаблон PDF (FK `templates`, kind=pdf) |
| `is_default` | bool | дефолт-fallback (см. ниже) |
| `active` | bool | |

> `NULL` в ключевых полях = **wildcard** («подходит под любой»). Это даёт гибкость без комбинаторного взрыва строк.

**Алгоритм резолва** (заменяет «slug из ticket_type_festival» как ПЕРВЫЙ источник, со страховкой):
```
resolve(kind, festival_id, order_type, ticket_type_id) -> ?slug:
  1. Берём active-привязки, где каждое из (festival_id, order_type, ticket_type_id)
     либо совпадает, либо NULL (wildcard).
  2. Сортируем по СПЕЦИФИЧНОСТИ (точное совпадение важнее wildcard:
     ticket_type > order_type > festival), берём самую специфичную с непустым *_template_id для kind.
  3. Нет совпадения → берём is_default-привязку (её *_template_id).
  4. Нет дефолта → СТАРОЕ поведение: slug из ticket_type_festival/location (полная обратная совместимость).
  5. По slug → findActive(slug,kind) → Mustache или blade-fallback (как сейчас).
```
**Точки интеграции (минимальные):** там, где сейчас берётся `getEmailView()`/`getFestivalView()` —
`InMemoryMySqlTicketsRepository.getTicket()` (заполняет `TicketResponse`) ИЛИ перед рендером в
`CreatingQrCodeService::createPdf()` и `RendersDbTemplate::renderDbOrView()`. Резолвер привязок
вызывается ПЕРВЫМ; пусто → текущая логика. Это сохраняет blade-safety-net.

> `order_type` для legacy-заказа выводится как сейчас (`friendly_id`→friendly, `curator_id`→list, live-флаг→live, иначе regular), для qr — из `qr_orders.type_order`. Передаём в резолвер.

**CQRS-модуль** (как `Location`): `TemplateBindingDto`, `TemplateBindingRepositoryInterface` + `InMemoryMySql…`, `Application` (`getList` через QueryBus, `create`/`edit`/`delete`), `TemplateBindingController`, роуты `/api/v1/templateBinding/*` (read — `admin`, write — `auth:api`+`admin`). Резолвер — отдельный доменный сервис `TemplateBindingResolver` (чистая логика выбора по специфичности, тестируется юнитом без БД).

**Frontend (AdminFront):** экран «Привязки шаблонов» — таблица + форма: селекты Фестиваль / Тип заказа / Тип билета (любой = пусто) → селекты Письмо / PDF-шаблон (из активных `templates` нужного kind) + чекбокс «По умолчанию». Vuex `appTemplateBinding` + `useCrud`.

### Защита от дурака / валидация
- `email_template_id` обязан ссылаться на `templates` с `kind=email` (и `pdf_template_id` — `kind=pdf`); проверка в FormRequest/Application.
- Не больше **одной** `is_default`-привязки на kind (или одна общая дефолт-строка) — уникальность на уровне сервиса.
- Циклов/дублей по одинаковому ключу (festival,order_type,ticket_type) — не плодить (уникальный составной индекс с учётом NULL).

### Тест-план — Часть B
**Unit (`TemplateBindingResolver`, без БД — главный тест):**
- Точное совпадение (festival+order_type+ticket_type) выигрывает у wildcard.
- Частичный wildcard: задан только `order_type=friendly` (festival/ticket = NULL) → подходит любому friendly-заказу.
- Специфичность: при двух подходящих (одна точная по ticket_type, другая wildcard) выбирается точная.
- Нет совпадений → возвращается `is_default`.
- Нет дефолта → возвращается `null` (вызывающий уходит на старую логику).
- Отдельно email и pdf: привязка с `email_template_id` задан, `pdf_template_id=null` → для kind=pdf эта строка игнорируется, идём дальше/в дефолт.
- Неактивные привязки (`active=false`) не участвуют.

**Feature (CRUD + резолв с БД):**
- `create`/`edit`/`delete` привязки (admin); read — admin, иначе 403.
- Валидация: `email_template_id` указывает на pdf-шаблон → 422; несуществующий template → 422.
- Две `is_default` на один kind → 422.
- **E2E резолва:** создаём привязку (order_type=friendly, ticket_type=X) → письмо `A`, pdf `B`; заказ friendly с билетом X резолвит `A`/`B`; заказ regular того же билета → дефолт; без дефолта → старый slug из `ticket_type_festival`.
- Обратная совместимость: при пустой таблице привязок поведение рендера = текущее (тест, что blade/Mustache по slug всё ещё выбирается).

**Регресс:** существующий `TemplateConversionRenderTest` + `EmailRendersDbTemplate` + PDF-тесты — зелёные (резолвер не ломает рендер, только выбирает slug).

---

## Открытые вопросы (нужно решение перед реализацией)

1. **Фестиваль в привязке.** Шаблоны сейчас различаются по фестивалю (`pdf` vs `TypeTicketPdf1`). Оставляем `festival_id` в привязке (как предложено, с wildcard NULL=любой) — да/нет? *(Рекомендую: да, NULL=любой.)*
2. **Заменяем или дополняем `ticket_type_festival`.** Предложено: привязки — ПЕРВЫЙ источник, старое (`ticket_type_festival.email/pdf`) — fallback (шаг 4). Так ничего не ломается. ОК? *(Рекомендую: дополняем, не удаляем сейчас.)*
3. **Дефолт — гранулярность.** Один общий дефолт на kind (email/pdf)? Или дефолт на каждый `order_type`? *(Рекомендую: один общий `is_default` на kind — проще; позже расширим.)*
4. **Часть A — объём истории.** Логируем create/edit/activate/publish/rollback (предложено). `saveDraft` — без истории. ОК?
5. **Очередь работ.** Делать Часть A (агрегат+история) и Часть B (привязки) **отдельными ветками/PR** (меньше риск) — ОК? Часть A меньше и самостоятельна, могу начать с неё.

---

## Порядок реализации (после согласования)
1. **Часть A** (1 ветка): домен-агрегат `Template` + 5 history-событий + интеграция в Application + эндпоинт истории + вкладка в редакторе. Тесты: unit домена + feature истории.
2. **Часть B** (2 ветка): сущность `TemplateBinding` + `TemplateBindingResolver` (юнит) + CRUD API + точка интеграции в резолв шаблона + экран AdminFront. Тесты: unit резолвера + feature CRUD/e2e.
3. Доки (`DOMAIN.md`, `API.md`, `template-system.md`), активация на стенде, регресс.
