# Доменная модель Systo

## Aggregate Roots

### OrderTicket

**Путь:** `Backend/src/Order/OrderTicket/Domain/OrderTicket.php`

```php
class OrderTicket extends AggregateRoot {
    use HasHistory; // запись истории изменений агрегата

    public const CHILD_TICKET_TYPE_ID = 'c3d4e5f6-a7b8-9012-cdef-345678901235';

    Uuid  $festival_id;
    Uuid  $user_id;          // получатель билетов
    ?Uuid $types_of_payment_id;  // null для заказов-списков
    PriceDto $price;          // priceItem, count, discount, totalPrice
    Status $status;            // из Shared/Domain/ValueObject/Status
    array $ticket;             // GuestsDto[]
    Uuid $id;
    ?string $promo_code;
    ?Uuid $location_id;       // только для заказов-списков
    ?Uuid $curator_id;        // только для заказов-списков
}
```

**Фабричные методы (команды):**

| Метод | Статус | Domain Events | Описание |
|-------|--------|---------------|----------|
| `create(dto, kilter)` | NEW | `ProcessUserNotificationNewOrderTicket` | Создание обычного заказа |
| `toPaid(dto, comment?, externalPromoCode?)` | PAID | `ProcessCreateTicket`, `ProcessUserNotificationOrderPaid`, `ProcessGuestNotificationQuestionnaire[]`, `ProcessTelegramByQuestionnaireSend` | Подтверждение |
| `toPaidFriendly(dto, comment?, externalPromoCode?)` | PAID | `ProcessCreateTicket`, `ProcessUserNotificationOrderPaidFriendly`, `ProcessGuestNotificationQuestionnaire[]`, `ProcessTelegramByQuestionnaireSend` | Подтверждение Friendly |
| `toPaidInLiveTicket(dto, kilter)` | PAID_FOR_LIVE | `ProcessCreateTicket`, `ProcessUserNotificationOrderPaidLiveTicket`, `ProcessGuestNotificationQuestionnaire[]` | Подтверждение live |
| `toCancel(dto)` | CANCEL | `ProcessCancelTicket`, `ProcessUserNotificationOrderCancel` | Отмена |
| `toCancelLive(dto)` | CANCEL_FOR_LIVE | `ProcessCancelTicket`, `ProcessCancelLiveTicket` | Отмена live |
| `toLiveIssued(dto, liveNumber[])` | LIVE_TICKET_ISSUED | `ProcessCreateTicket`, `ProcessGuestNotificationQuestionnaire[]`, `ProcessPushLiveTicket[]` | Выдача живых |
| `toDifficultiesArose(dto, comment)` | DIFFICULTIES_AROSE | `ProcessCancelTicket`, `ProcessUserNotificationOrderDifficultiesArose` | Трудности |
| `toChangeTicket(changes[])` | — | — | Изменение данных билета (записывает историю если `!empty($changes)`) |
| `toProcessGuestNotificationQuestionnaire(dto)` | — | `ProcessGuestNotificationQuestionnaire[]` | Только рассылка гостям |
| **`createList(dto, kilter)`** | **NEW_LIST** | — (только история, никаких писем) | Создание заказа-списка куратором |
| **`toApproveList(dto)`** | **APPROVE_LIST** | `ProcessCreateTicket`, `ProcessUserNotificationListApproved`, `ProcessGuestNotificationQuestionnaire[]` | Одобрение списка → PDF получателю + анкеты гостям |
| **`toCancelList(dto)`** | **CANCEL_LIST** | `ProcessCancelTicket`, `ProcessUserNotificationListCancel` | Отмена списка |
| **`toDifficultiesAroseList(dto, comment)`** | **DIFFICULTIES_AROSE_LIST** | `ProcessCancelTicket`, `ProcessUserNotificationListDifficultiesArose` | Трудности по списку (комментарий обязателен) |

`OrderTicketDto` имеет два фабричных метода:
- `fromState($data, $userId, $priceDto, $isLiveTicket, ?$pusherId)` — обычный/Friendly заказ
- `fromStateForList($data, $userId, $curatorId, $locationId)` — заказ-список (без `ticket_type_id`/`types_of_payment_id`/цены)

Все методы (кроме `toProcessGuestNotificationQuestionnaire`) вызывают `recordHistory()` через trait `HasHistory`.

---

### Ticket

**Путь:** `Backend/src/Ticket/CreateTickets/Domain/Ticket.php`

```php
class Ticket extends AggregateRoot {
    string $name;
    int $kilter;
    Uuid $aggregateId;
    string $email;
}
```

**Методы:**

| Метод | Domain Events | Описание |
|-------|---------------|----------|
| `newTicket(TicketResponse)` | `ProcessCreatingQRCode` | Создание билета + генерация QR |

---

### Account

**Путь:** `Backend/src/User/Account/Domain/Account.php`

```php
class Account extends AggregateRoot {
    Uuid $id;
    string $email;
    string $phone;
    string $city;
    ?string $name;
    string $role;
}
```

**Методы:**

| Метод | Domain Events | Описание |
|-------|---------------|----------|
| `creatingNewAccount(id, dto, password)` | `ProcessAccountNotification` | Создание аккаунта + email с паролем |

---

### PromoCode

**Путь:** `Backend/src/PromoCode/Domain/PromoCode.php`

```php
class PromoCode extends AggregateRoot {
    Uuid $id;
    string $name;
    float $discount;
    bool $is_percent;
    ?int $limit;
}
```

**Пассивный агрегат** — нет фабричных методов и Domain Events. Используется только для чтения/валидации.

---

### Location

**Путь:** `Backend/src/Location/` (модуль без AggregateRoot — пассивная сущность, как PromoCode)

```php
class LocationDto extends AbstractionEntity {
    Uuid    $id;
    string  $name;
    ?string $description;
    ?Uuid   $questionnaire_type_id;  // FK → questionnaire_type
    Uuid    $festival_id;             // FK → festivals
    ?string $email_template;          // имя blade-шаблона письма (по умолчанию orderListApproved)
    ?string $pdf_template;            // имя blade-шаблона PDF
    bool    $active;
}
```

**Application API (`LocationApplication`):**
- `getList(LocationGetListQuery)` — фильтрация по name/festival_id/active
- `getItem(Uuid)` — один
- `create(LocationDto)`, `edit(Uuid, LocationDto)`, `delete(Uuid)` — CRUD

**Repository:** `LocationRepositoryInterface` → `InMemoryMySqlLocationRepository`. Bind в `TicketsProvider`.

**Используется в:** `OrderTicket` (поле `location_id`), `OrderListApproved` Mailable (берёт `email_template` для письма-одобрения списка).

---

### Festival (AF-7)

**Путь:** `Backend/src/Festival/Domain/Festival.php`. **AggregateRoot** + trait `HasHistory` (как `Template`/`OrderTicket`). Каталог фестивалей — мастер на org.

Поднят из пассивной сущности (DTO + репозиторий) в AggregateRoot **ради записи истории**. Чистый домен: только идентичность (`Uuid $id`) + журнал доменных действий; состояние/персист — в репозитории (БД только там).

**Фабричные методы:** `created(id, name, year, active)` → `FestivalCreatedEvent`; `existing(id)` (точка входа для действий); `edited(array $changed)` → `FestivalEditedEvent` (пусто → история не пишется); `deleted()` → `FestivalDeletedEvent`. Все события — `HistoryEventInterface` в `History/Domain/Event/`, `aggregate_type = 'festival'`.

`FestivalApplication` (`Order/OrderTicket/Application/GetFestivalList/`) после каждого write (create/edit/delete через CommandBus) строит агрегат, `pullHistoryEvents()` → `HistoryRepositoryInterface::save` (`actor_type = user`, `actor_id = Auth::id()`). `getHistory(Uuid)` → `getByAggregateId`. CRUD/репозиторий пока остаются под `Order/OrderTicket/` (перенос в модуль `Festival/` — отдельный рефакторинг, см. `.claude/specs/festival-aggregate-payment-templates-plan.md`).

---

### Template (AF-3)

**Путь:** `Backend/src/Template/`. **AggregateRoot** `Template` (`Domain/Template.php`) + trait `HasHistory` (как `OrderTicket`): действия `create`/`edit`/`activate`/`publish`/`rollback` пишут события в `domain_history` (`aggregate_type='template'`, actor = админ/`Auth::id()`, `ActorType::USER`). 5 событий в `History/Domain/Event/Template*Event.php` (`template_created`/`edited`/`activated`/`published`/`rolled_back`); `saveDraft` и artisan-bulk (`import-blade`/`sync-converted`) историю НЕ пишут. Журнал — `GET /api/v1/template/history/{id}` + вкладка «Журнал» в редакторе. `template_versions` (снапшоты тела для отката) и `domain_history` (аудит действий) — разное. Спеки: `.claude/specs/template-system.md`, `.claude/specs/template-aggregate-and-bindings.md`.

**Привязки шаблонов (Часть B)** — модуль `Backend/src/TemplateBinding/` (пассивная сущность): таблица `template_bindings` маппит `(festival_id, order_type, ticket_type_id)` → `email_template_id`/`pdf_template_id` + `is_default`. `NULL`-поля = wildcard. Чистый `TemplateBindingResolver` (Domain, юнит-тестируемый) выбирает самую специфичную привязку (ticket>order>festival); нет совпадения → `is_default`; нет дефолта → старый slug (`ticket_type_festival`) — обратная совместимость. Интеграция: `InMemoryMySqlTicketsRepository::getTicket()` выводит `order_type` (curator→list / friendly_id→friendly / is_live_ticket→live / иначе regular) и подменяет `emailView`/`festivalView`. API `/api/v1/templateBinding/*` (admin, CRUD) + экран «Привязки шаблонов» в AdminFront. Тесты: резолвер 7 unit + CRUD/валидация 5 + e2e-резолв 2.

Редактируемые из админки шаблоны **писем и PDF-билетов**. Движок рендера — **Mustache** (logic-less, RCE-безопасен by design: нет PHP/blade-директив, `{{ x }}` авто-экранирует HTML, `{{{ raw }}}` — whitelist для QR data-URI). Рендер из БД с **fallback на blade**: пока активной записи нет — рендерится старый blade-файл (поведение без изменений). `slug` = имени blade-файла → нулевая миграция привязки.

```php
class TemplateDto extends AbstractionEntity implements Response {
    Uuid    $id;
    string  $slug;          // = имени blade ('pdf','orderToPaid','TypeTicketPdf1',...)
    string  $kind;          // email | pdf (TemplateKind)
    string  $engine;        // html | mjml (TemplateEngine, mjml только для email)
    string  $title;
    string  $body;          // опубликованный исходник
    ?string $draft_body;    // черновик (не идёт в прод)
    ?string $compiled_html; // кэш скомпилированного MJML (для html = body)
    bool    $active;        // false → откат на blade-fallback
    bool    $is_system;     // импортирован из blade
    ?Carbon $created_at;
    ?Carbon $updated_at;
    // getRenderBody() → compiled_html ?? body
}
class TemplateVersionDto extends AbstractionEntity implements Response {
    Uuid    $id;
    Uuid    $template_id;
    string  $body;          // снапшот опубликованного body
    ?string $comment;
    ?string $author_id;
    ?Carbon $created_at;
}
```

**Таблицы БД** (миграция `2026_06_15_120000_create_templates_table`):

| Таблица | Поля |
|---------|------|
| `templates` | `id`, `slug`, `kind` (enum email/pdf), `engine` (enum html/mjml, default html), `title`, `body` (mediumText), `draft_body` (nullable), `compiled_html` (nullable), `active` (default true), `is_system` (default false), `created_at`/`updated_at`. UNIQUE `(slug, kind)`, INDEX `(kind, active)` |
| `template_versions` | `id`, `template_id` (index), `body` (mediumText), `comment` (nullable), `author_id` (nullable), `created_at`. Append-only снапшоты при публикации/откате |

> `body`/`compiled_html`/`draft_body` — обычные строковые колонки (без JSON-кастов). Timestamps — DB DEFAULT.

**Application API (`TemplateApplication`):** `getList(TemplateGetListQuery)` / `getItem(Uuid)` / `create` / `edit` / `activate(Uuid, bool)` / `saveDraft(Uuid, body)` / `publish(Uuid, body, authorId, comment)` / `getVersions(Uuid)` / `rollback(Uuid, versionId, authorId)` / `getVariables(kind, slug)` / `getPreview(PreviewTemplateQuery)`. Чтение списка/превью — через QueryBus.

**Домен:** `TemplateKind` (Enum email/pdf), `TemplateEngine` (Enum html/mjml), `PlaceholderCatalog` — единственный источник плейсхолдеров per `(kind, slug)` + `sample()` (фикстуры для превью без ПДн).

**Сервисы:** `TemplateRenderer` (`Mustache::render(body, context)`), `TemplateService` (импорт/fallback). Импорт текущих blade в БД — artisan `templates:import-blade` (slug = имя файла, `updateOrCreate`, как **неактивные** системные черновики → нулевой риск).

**Repository:** `TemplateRepositoryInterface` → `InMemoryMySqlTemplateRepository`. Bind в `Tickets/TicketsProvider`.

**Точки интеграции рендера (2 точки, обе с fallback на blade):**
- **PDF** — `CreatingQrCodeService::createPdf()` (`Backend/src/Ticket/CreateTickets/Services/`): `findActive($slug, pdf)` → `Pdf::loadHTML(render())`, иначе `Pdf::loadView($slug)`.
- **Письма** — трейт `App\Mail\Concerns\RendersDbTemplate::renderDbOrView($slug, $vars)` на **11** order/list-Mailable: `findActive($slug, email)` → `$this->html(render())`, иначе `$this->view('email.'.$slug)`. Покрывает оба канала (legacy `order_tickets` + qr-пайплайн `QrOrder/Application/Step`), т.к. оба используют те же Mailable.

**Привязка по событию (поле `event`)** — добавлена ось `event` в `template_bindings` (миграция `2026_06_17_120000_add_event_to_template_bindings`, `string(40) nullable index after order_type`). Теперь привязка резолвится по `(event, festival, order_type, ticket_type)`. Каталог событий — `EmailEvent` (см. модуль EmailDelivery). `NULL` = wildcard «любое событие» → существующие привязки и резолв PDF/выдачи не меняются (обратная совместимость). В `TemplateBindingDto` появилось поле `event`; `specificity()` даёт `event` **вес 8** — сильнейший дискриминатор: **event > ticket_type > order_type > festival**. `create`/`edit` принимают `event` (валидируется `EmailEvent::isValid`); каталог для селектора — `GET /api/v1/templateBinding/events`.

**Привязка по типу оплаты (поле `types_of_payment_id`, AF-9)** — добавлена ось `types_of_payment_id` в `template_bindings` (миграция `2026_06_18_130000_add_types_of_payment_to_template_bindings`, `uuid nullable index after ticket_type_id`). Тип оплаты связан с внешним продавцом (`types_of_payment.user_external_id`), поэтому это даёт **«под каждого продавца определённый тип письма/PDF»** (AF-10). Теперь резолв по `(types_of_payment, event, festival, order_type, ticket_type)`. `NULL` = wildcard → обратная совместимость. В `TemplateBindingDto`/`matches()`/`specificity()` добавлена ось с **весом 16 — сильнейший override**: `types_of_payment > event > ticket_type > order_type > festival`. Интеграция: `InMemoryMySqlTicketsRepository::getTicket()` пробрасывает `order_tickets.types_of_payment_id` в резолвер для email/PDF (старый slug, в т.ч. легаси `types_of_payment.email` = `emailPayView`, остаётся fallback). `create`/`edit` принимают `types_of_payment_id`.

---

### EmailDelivery (Ф1–Ф5)

**Путь:** `Backend/src/EmailDelivery/` (модуль без AggregateRoot — пассивная сущность, как `QrOrder`/`Location`/`Template`; БД только в репозитории). Спека: `.claude/specs/email-delivery-system.md`.

Контроль полного пути письма «дошло / где застряло»: `queued → sending → sent (передано на SMTP) → [delivered → opened] / failed (+текст ошибки)`. Повтор из админки; пиксель прочтения = «точно дошло» (за флагом, 152-ФЗ). `delivered`/`bounced` требуют транзакционного провайдера с вебхуками (AF-6). Все 5 фаз в коде, PHPUnit зелёный.

**Домен:**

- **`EmailEvent`** (`Domain/EmailEvent.php`) — канонический справочник **16 событий** писем → `defaultSlug()` (= текущий зашитый в Mailable slug) + `label`. Источник правды «какое письмо за каким событием». Маппинг event → defaultSlug:

  | event | defaultSlug | label |
  |-------|-------------|-------|
  | `order_created` | `orderToCreate` | Заказ создан |
  | `order_paid` | `orderToPaid` | Заказ оплачен |
  | `order_paid_friendly` | `TypeTicketMailOrderToPaidFriendly1` | Заказ оплачен (Friendly) |
  | `order_paid_live` | `orderToPaidLiveTicket` | Живой билет оплачен |
  | `order_cancel` | `orderToCancel` | Заказ отменён |
  | `order_changed` | `orderToChangeTicket` | Данные заказа изменены |
  | `order_difficulties` | `orderToDifficultiesArose` | Трудности с заказом |
  | `order_live_issued` | `orderToLiveTicketIssued` | Живой билет выдан |
  | `list_approved` | `orderListApproved` | Список одобрен |
  | `list_cancel` | `orderListCancel` | Список отменён |
  | `list_difficulties` | `orderListDifficultiesArose` | Трудности со списком |
  | `user_registered` | `newUser` | Регистрация пользователя |
  | `password_reset` | `passwordResets` | Сброс пароля |
  | `invite` | `invate` | Приглашение |
  | `questionnaire` | `questionnaire` | Анкета гостя |
  | `questionnaire_approved` | `questionnaireApproved` | Анкета одобрена |

  Методы: `all()`, `isValid()`, `defaultSlug()`, `catalog()` (для селектора `[{value, label}]`).

- **`EmailStatus`** (`Domain/ValueObject/EmailStatus.php`) — VO статуса + машина переходов (provider-ready):

  ```
  queued ──► sending ──► sent ──► delivered ──► opened
     │          │          │          │
     └► failed ◄┘          └► bounced ◄┘     (failed/bounced ──► queued при ретрае)
  ```

  Переходы: `queued→{sending, failed}`; `sending→{sent, failed}`; `sent→{delivered, opened, bounced, failed}`; `delivered→{opened, bounced}`; `opened→{}` (финал); `failed→{queued}`; `bounced→{queued}`. `delivered`/`bounced` — только провайдер с вебхуками (AF-6); `opened` — пиксель. Методы: `value()`, `equals()`, `canTransitionTo()`, `isUnresolved()`, `all()`.

- **`EmailLifecycleEvent`** (`Domain/EmailLifecycleEvent.php`) — `HistoryEventInterface`: `aggregate_type = 'email'`, `event_name = 'email_' . <status>` (`email_queued`/`email_sending`/`email_sent`/`email_failed`/`email_opened`). Payload без ПДн (статус/событие/ошибка, не email/ФИО).

**Application:**

- **`MailDispatcher`** (`Application/MailDispatcher.php`) — единая точка отправки: `send(event, EmailContext, Mailable): Uuid`. Создаёт `email_messages` (`queued`) + сериализует Mailable в колонку `mailable` (`base64(serialize)`) + пишет историю `email_queued` + `SendEmailJob::dispatch(id)`. Slug в записи — информативный (`EmailEvent::defaultSlug`).
- **`EmailContext`** (`Application/EmailContext.php`) — контекст отправки: `recipient`, `festivalId?`, `orderType?`, `ticketTypeId?` (ключи привязки по событию), `source` (default `org_event`), `actorType` (default `system`), `aggregateType?`, `aggregateId?`, `meta[]`. vars/attachments не несёт — их уже несёт сам Mailable.
- **`SendEmailJob`** (`Application/Job/SendEmailJob.php`, `ShouldQueue`) — асинхронная отправка: `queued → sending → sent / failed`. `tries = 3`, `backoff = [30, 120, 600]` сек. Mailable читается из БД по id → повтор = re-dispatch той же задачи. Идемпотентность: `sent`/`delivered`/`opened` повторно не шлёт. `failed()` (tries исчерпаны) → финальный `failed`. За флагом `mail_delivery.open_tracking` дописывает пиксель прочтения в HTML.
- **`EmailDeliveryApplication`** (`Application/EmailDeliveryApplication.php`) — тонкий слой админ-чтения/управления: `getList(EmailMessageGetListQuery)` (через QueryBus, паттерн `Location`/`QrOrder`), `getItem(Uuid)`, `getByAggregate(type, id)`, `resend(Uuid, ?actorId)` (requeue + history `email_queued` action=resend + dispatch), `registerOpen(token)` (пиксель → `markOpened`, идемпотентно, только из `sent`/`delivered`).
- **`MailDeliveryLog`** (`Application/Support/`) — канал логов `mail_delivery` (`config/logging.php`, `storage/logs/mail_delivery.log`), маскировка email.

**Mailable:** `App\Mail\GenericTemplatedMail` (`slug` + `subject` + `vars[]`, трейт `RendersDbTemplate`) — для qr-канала уведомлений (S2S): не-заказные письма (регистрация/сброс пароля), инициированные на витрине qr.

**Repository:** `EmailMessageRepositoryInterface` → `InMemoryMySqlEmailMessageRepository` (таблица `email_messages`). См. §Repository.

**Источники писем (`source`):** `qr_pipeline` (выдача билетов в qr-пайплайне `QrOrder/Application/Step`), `qr_intake` (S2S-приём писем от витрины), `org_event` (старые org-события — по умолчанию).

> **ОТЛОЖЕНО:** старые org-письма (отмена/изменение через `ProcessUserNotification*`) пока идут мимо диспетчера — будут подключены отдельно.

---

### BazaDelivery (AF-4)

**Путь:** `Backend/src/BazaDelivery/` (модуль без AggregateRoot — пассивная сущность, как `QrOrder`/`EmailDelivery`; БД только в репозитории). Спека: `.claude/specs/baza-delivery-async-prompt.md`.

Контроль пути записи билета в **Baza** («система входа», сканирование на входе): `queued → sending → delivered / failed (+текст ошибки)`. **Запись в Baza теперь асинхронная и трекаемая** — сбой Baza больше НЕ роняет выдачу билета/смену статуса (доедет ретраем). Зеркало системы писем под билет.

**Домен:**

- **`BazaDeliveryStatus`** (`Domain/ValueObject/BazaDeliveryStatus.php`) — VO статуса + машина переходов:
  ```
  queued ──► sending ──► delivered
     │          │
     └► failed ◄┘          (failed ──► queued при ретрае/ручном повторе)
  ```
  Переходы: `queued→{sending, failed}`; `sending→{delivered, failed}`; `delivered→{}` (финал); `failed→{queued}`. Методы: `value()`, `equals()`, `canTransitionTo()`, `isUnresolved()` (queued/sending/failed), `all()`.

- **`BazaDeliveryLifecycleEvent`** (`Domain/BazaDeliveryLifecycleEvent.php`) — `HistoryEventInterface`: `aggregate_type = 'baza_delivery'`, `event_name = 'baza_' . <status>` (`baza_queued`/`baza_sending`/`baza_delivered`/`baza_failed`). Пишется на **каждую попытку** доставки. Payload без ПДн (статус/ошибка/target/attempt).

**Application:**

- **`BazaDeliveryDispatcher`** (`Application/BazaDeliveryDispatcher.php`) — единая точка постановки доставки в очередь (зеркало `MailDispatcher`): `dispatch(TicketResponse, BazaDeliveryContext)` (target el/spisok выводится из билета), `dispatchLive(ticketId, number, ctx)` (target `live_tickets`), `dispatchAuto(AutoDto, festivalId, ctx)` (target `auto`), `enqueue(ticketId, target, ctx)` (низкоуровневая, идемпотентно по `(ticket_id, target)`). Создаёт/возвращает в очередь запись `baza_deliveries(queued)` + пишет историю `baza_queued` + `DeliverTicketToBazaJob::dispatch`. Уже доставленное (delivered) повторно не доставляет; застрявшее (не delivered) — requeue.
- **`BazaDeliveryContext`** (`Application/BazaDeliveryContext.php`) — контекст: `orderId?`, `festivalId?`, `name?`, `email?`, `number?`, `source` (default `org_event`), `actorType` (default `system`). Заполняется из `TicketResponse`/`AutoDto`.
- **`DeliverTicketToBazaJob`** (`Application/Job/DeliverTicketToBazaJob.php`, `ShouldQueue`) — async-запись (зеркало `SendEmailJob`): `queued → sending → delivered / failed`, `backoff = [30, 120, 600]`. Маршрут по `target` (`setInBaza`/`setInBazaList`/`setInBazaLive`/`setInBazaAuto`). Субъект для el/spisok берётся из сохранённого `subject_blob` (готовый `TicketResponse`, т.к. `getTicket` НЕ пересоберёт qr-билет — заказ лежит в `qr_orders`, не `order_tickets`; fallback на `getTicket` для классических билетов); live — по `number`+`ticket_id`; auto — `AutoRepository::getById`. История на каждую попытку. **Кап = 10 попыток** (`MAX_ATTEMPTS`, авто-ретрай + ручной resend суммарно; счётчик не сбрасывается): после 10 неуспешных — терминальный `failed`, новых попыток нет. Идемпотентность: из `delivered` повторно не доставляет.
- **`BazaDeliveryApplication`** (`Application/BazaDeliveryApplication.php`) — admin-чтение/управление: `getList` (через QueryBus), `getItem`, `getByOrderId` (для «весь путь» qr), `resend(Uuid, ?actorId)` (`failed→queued` + history + dispatch), `countStuck(?festivalId)` / `getStats(?festivalId)` (счётчики по статусам, `stuck = failed` — для дашборда).
- **`BazaDeliveryGetListQuery(+Handler)`** (`Application/GetList/`) — whitelist фильтров: `status`/`target`/`ticket_id`/`order_id`/`festival_id`/`source` (EQUAL), `name`/`email` (LIKE). Пагинация `page`/`perPage`.
- **`BazaDeliveryLog`** (`Application/Support/`) — канал логов `baza_delivery` (`config/logging.php`).

**Источники доставки (`source`):** `qr_pipeline` (выдача qr-заказа в пайплайне — `PushToBazaStep`/`LinkLiveStep`), `org_event` (классический org-флоу — `PushTicketsCommandHandler`/`PushTicketsLiveCommandHandler`/`AutoApplication`).

**Цели (`target`):** `el_tickets` (обычный), `spisok_tickets` (заказ-список), `live_tickets` (живой билет, по номеру), `auto` (авто заказа-списка; `ticket_id` = id строки авто, гос-номер → в `name`).

**Repository:** `BazaDeliveryRepositoryInterface` → `InMemoryMySqlBazaDeliveryRepository` (таблица `baza_deliveries`). См. §Repository. Bind в `Tickets/TicketsProvider`.

**Точки интеграции (4 пути записи в Baza переведены на трекинг):** legacy `PushTicketsCommandHandler` (больше не кидает `DomainException` на сбой Baza), legacy live `PushTicketsLiveCommandHandler`, qr `PushToBazaStep` + `LinkLiveStep` (старые `PushTicketToBazaJob`/`LinkLiveTicketJob` удалены), `AutoApplication` (`setInBazaAuto` стал идемпотентным `updateOrInsert` + исправлен баг `finally{return true}`).

---

### Questionnaire

**Путь:** `Backend/src/Questionnaire/Domain/Questionnaire.php`

```php
class Questionnaire extends AggregateRoot {
    use HasHistory; // запись истории одобрения анкеты в domain_history
    // Состояние хранится в QuestionnaireTicketDto
}
```

**Методы:**

| Метод | Domain Events | Описание |
|-------|---------------|----------|
| `toApprove(dto)` | `ProcessQuestionnaireApprovedNotification` + история `QuestionnaireApprovedEvent` | Одобрение анкеты → письмо гостю «анкета одобрена» (если есть email) + событие истории |
| `toSendTelegram(dto)` | `ProcessTelegramSend` | Уведомление в Telegram |

**Одобрение анкеты (`toApprove`):** теперь `Questionnaire` использует trait `HasHistory`. При одобрении пишется доменное событие `ProcessQuestionnaireApprovedNotification` (письмо гостю «анкета одобрена», шлётся через `MailDispatcher`, событие `EmailEvent::QUESTIONNAIRE_APPROVED`, `source = org_event`) — **заменило** прежнее `ProcessInviteLinkQuestionnaire` в approve-флоу (то же письмо со ссылкой-приглашением, но под отдельным событием → видно в каталоге писем, привязке шаблонов и трекинге). Дополнительно `recordHistory(QuestionnaireApprovedEvent)` → факт одобрения пишется в `domain_history`.

---

## Value Objects

### Shared (базовые)

| VO | Путь | Описание |
|----|------|----------|
| **Uuid** | `Shared/Domain/ValueObject/Uuid.php` | UUID v4 (Ramsey), методы: `random()`, `value()`, `equals()` |
| **Status** | `Shared/Domain/ValueObject/Status.php` | Статусы заказов с матрицей переходов. **См. BUSINESS_RULES.md §1** |
| **Money** | `Shared/Domain/ValueObject/Money.php` | Денежная сумма в **целых рублях** (`int`), default `RUB`. Иммутабельный, арифметика: `add`, `subtract` (клампит к 0), `multiply`, `equals`, `isZero`, `isGreaterThan`. Фабрики: `zero()`, `fromFloat()` с banker's rounding (half-to-even). Защищён от NaN/INF и значений вне `int`. **Введён в v2.6.0** для нового формата заказа (см. `.claude/specs/order-format-architecture.md` §2.3) |
| **Enum** | `Shared/Domain/ValueObject/Enum.php` | Абстрактный Enum: `__callStatic()`, `fromString()`, `randomValue()` |
| **StringValueObject** | `Shared/Domain/ValueObject/StringValueObject.php` | Абстрактная обёртка `?string` |
| **IntValueObject** | `Shared/Domain/ValueObject/IntValueObject.php` | Абстрактная обёртка `int`, метод `isBiggerThan()` |
| **BoolValueObject** | `Shared/Domain/ValueObject/BoolValueObject.php` | Абстрактная обёртка `?bool` |
| **Second** | `Shared/Domain/Second.php` | VO для секунд (extends IntValueObject) |
| **SecondsInterval** | `Shared/Domain/SecondsInterval.php` | Интервал Second от/до с валидацией |

### Модуль-specific

| VO | Путь | Описание |
|----|------|----------|
| **CommentForOrder** | `Order/OrderTicket/ValueObject/` | `id`, `user_id`, `comment`, `is_checkin`, `created_at` |
| **QuestionnaireStatus** | `Questionnaire/Domain/ValueObject/` | Константы: `NEW`, `APPROVE` |
| **StatusForBillingValueObject** | `Billing/ValueObject/` | `payment.completed`, `payment.refund` |
| **DeviceValueObject** | `Billing/ValueObject/` | `android`, `ios`, `desktop` |

---

## DTO

### Order Module

| DTO | Файл | Поля |
|-----|------|------|
| **OrderTicketDto** | `Order/OrderTicket/Dto/OrderTicket/` | `festival_id`, `user_id`, `email`, `phone`, `types_of_payment_id`, `ticket_type_id`, `ticket[]`, `priceDto`, `status`, `promo_code`, `id`, `inviteLink`, `friendly_id` |
| **GuestsDto** | `Order/OrderTicket/Dto/OrderTicket/` | `value`, `email`, `number`, `id`, `festivalId` |
| **PriceDto** | `Order/OrderTicket/Dto/OrderTicket/` | `priceItem`, `count`, `discount`, `totalPrice`, `price` |
| **CommentDto** | `Order/OrderTicket/Dto/` | `user_id`, `order_tickets_id`, `comment` |

### Ticket Module

| DTO | Файл | Поля |
|-----|------|------|
| **TicketDto** | `Ticket/CreateTickets/Dto/` | `order_ticket_id`, `name`, `festival_id`, `id`, `kilter`, `email`, `number` |
| **TicketResponse** | `Ticket/CreateTickets/Responses/` | Ответ для API |

### PromoCode Module

| DTO | Файл | Поля |
|-----|------|------|
| **PromoCodeDto** | `PromoCode/Response/` | `id`, `name`, `discount`, `isPercent`, `limit`, `ticket_type_id`, `festival_id` |
| **LimitPromoCodeDto** | `PromoCode/Dto/` | `count`, `limit` |
| **ExternalPromoCodeDto** | `PromoCode/Response/` | `promocode` |

### User Module

| DTO | Файл | Поля |
|-----|------|------|
| **AccountDto** | `User/Account/Dto/` | `id`, `email`, `phone`, `city`, `name`, `is_admin`, `role` |
| **UserInfoDto** | `User/Account/Dto/` | `id`, `email`, `city`, `role`, `phone`, `name` |

### Questionnaire Module

| DTO | Файл | Поля |
|-----|------|------|
| **QuestionnaireTicketDto** | `Questionnaire/Dto/` | `id`, `email` (nullable), `phone` (nullable), `telegram` (nullable), `vk` (nullable), `agy` (nullable, ?string), `status`, `userId`, `orderId`, `ticketId`, `questionnaireTypeId` (UUID), `extraData` (JSON — динамические поля из `questionnaire_type.questions`), `link` |

### Festival Module

| DTO | Файл | Поля |
|-----|------|------|
| **FestivalDto** | `Festival/DTO/` | Данные фестиваля |
| **PriceDto** | `Festival/DTO/` | `uuid`, `price`, `beforeDate` |
| **TicketTypeDto** | `Festival/Response/` | `id`, `name`, `price`, `groupLimit`, `festivalList[]`, `priceList[]`, `isLiveTicket`, `isParking` |
| **TypesOfPaymentDto** | `Festival/Response/` | `id`, `name`, `card`, `is_billing` |

### History Module

| DTO | Файл | Поля |
|-----|------|------|
| **SaveHistoryDto** | `History/Dto/` | `aggregateId` (string), `event` (HistoryEventInterface), `actorId` (?string), `actorType` (string) |
| **DomainHistoryDto** | `History/Dto/` | `aggregateId` (string), `aggregateType` (string), `eventName` (string), `payload` (array), `actorId` (?string), `actorType` (string), `occurredAt` (Carbon) |

**ActorType** (`History/Domain/ActorType.php`) — типы инициаторов событий в `domain_history.actor_type`:
- `user` — действие выполнил пользователь (актер берётся из `Auth::id()`)
- `system` — системное действие (фоновые задачи, события)
- `artisan` — действие из artisan-команды (CLI)
- `auto_payment` — авто-одобрение заказа на `POST /api/v1/order/create` по валидному заголовку `AutoPayment` (`actorId` пишется `null`)
- `qr` — действия по заказам, пришедшим от витрины qr.spaceofjoy.ru (S2S-канал, не человек; `actorId` пишется `null`)

**Новые `aggregate_type` / `event_name` в `domain_history`:**
- `aggregate_type = 'email'` (модуль EmailDelivery) — таймлайн письма: `email_queued` / `email_sending` / `email_sent` / `email_failed` / `email_opened`. `actor_type`: события писем от qr → `qr`, системные → `system`, повтор из админки → `user`.
- `aggregate_type = 'baza_delivery'` (AF-4, модуль BazaDelivery) — таймлайн доставки билета в Baza, **история КАЖДОЙ попытки**: `baza_queued` / `baza_sending` / `baza_delivered` / `baza_failed`. `actor_type`: доставки от qr → `qr`, системные → `system`, повтор из админки → `user`.
- Новые `event_name` для `aggregate_type = 'qr_order'` (шаги пайплайна выдачи, `'step_' . $step->name()`, payload `{status: ok|fail, error?}`): `step_create_tickets`, `step_send_order_email`, `step_push_to_baza`, `step_send_telegram`, `step_create_live_tickets`, `step_link_live`, `step_send_list_email`, `step_send_live_email`.
- `aggregate_type = 'festival'` (AF-7, модуль `Festival`) — журнал каталога фестивалей: `festival_created` (payload `{name, year, active}`), `festival_edited` (payload `{changed: [...]}` — изменившиеся поля), `festival_deleted`. `actor_type = user` (`actor_id = Auth::id()`). Отдаётся `GET /api/v1/festival/getHistory/{id}` (admin).
- `aggregate_type = 'questionnaire'` (модуль `Questionnaire`) — факт одобрения анкеты: `questionnaire_approved` (payload без ПДн — `{order_id?, ticket_id?, questionnaire_type_id?}`, через `QuestionnaireApprovedEvent`). Пишется при `toApprove()` с `actor_type = user`, `actor_id = Auth::id()` администратора, выполнившего одобрение.

### QrOrder Module

| DTO | Файл | Поля |
|-----|------|------|
| **QrOrderDto** | `QrOrder/Dto/` | `id` (Uuid, == id заказа qr/org), `email`, `status`, `festivalId` (?Uuid), `typeOrder` (?string: regular/friendly/list/live), `city`, `phone`, `totalPrice` (int, рубли), `payload` (array — весь контракт qr), `issuedAt`, `externalOrderNo` (?string), `paymentMethod` (?string), `promoCode` (?string), `paidAt` (?Carbon). Фабрики: `fromState($row)` (из строки БД), `fromQrContract($json)` (из контракта витрины — проецирует `external_order_no`, `payment.method`, `payment.promo_codes[0]`, `order_data.paid_at`; весь JSON в `payload`) |
| **QrOrderItemForListResponse** | `QrOrder/Responses/` | Облегчённая проекция для списка админки (snake_case, **без `payload`**): `id`, `email`, `status`, `festival_id`, `type_order`, `city`, `phone`, `total_price`, `external_order_no`, `payment_method`, `promo_code`, `issued_at`, `paid_at`, `created_at` |
| **QrOrderGetListResponse** | `QrOrder/Responses/` | `collection` (Collection<QrOrderItemForListResponse>) + `totalCount` (int) — страница + total для пагинации |

### EmailDelivery Module

| DTO | Файл | Поля |
|-----|------|------|
| **EmailMessageDto** | `EmailDelivery/Dto/` | `id` (Uuid), `event` (string, EmailEvent), `recipient`, `subject` (?), `template_slug` (?), `status` (string, EmailStatus), `attempts` (int), `error` (?), `source` (qr_pipeline/qr_intake/org_event), `aggregate_type` (?), `aggregate_id` (?), `festival_id` (?), `tracking_token`, `provider_message_id` (?), `meta` (array), `sent_at` (?Carbon), `opened_at` (?Carbon), `created_at`/`updated_at`. **Не несёт сериализованный Mailable** (он в отдельной колонке, тяжёлый) → DTO безопасен для admin-API. Фабрики: `queued($id, $event, EmailContext, $slug, $token)`, `fromState($row)` |
| **EmailMessageItemForListResponse** | `EmailDelivery/Responses/` | Облегчённая проекция для списка «Доставка писем» (snake_case, **без `meta`/`mailable`**, но `error` включён — сразу видно «где застряло»): `id`, `event`, `recipient`, `subject`, `status`, `attempts`, `error`, `source`, `festival_id`, `aggregate_type`, `aggregate_id`, `sent_at`, `opened_at`, `created_at` |
| **EmailMessageGetListResponse** | `EmailDelivery/Responses/` | `collection` (Collection<EmailMessageItemForListResponse>) + `totalCount` (int) — страница + total для пагинации |

**Eloquent-модель:** `App\Models\EmailDelivery\EmailMessageModel` (таблица `email_messages`, миграция `2026_06_17_130000_create_email_messages_table`). Касты: `attempts` → integer, `meta` → array, `sent_at`/`opened_at` → datetime. Поля: `event`, `recipient`, `subject`, `template_slug`, `status`, `attempts`, `error`, `source`, `aggregate_type`, `aggregate_id`, `festival_id`, `tracking_token` (UNIQUE), `provider_message_id`, `meta` (json), `mailable` (longText, base64-serialize), `sent_at`, `opened_at`, timestamps. Индексы: `event`, `recipient`, `status`, `source`, `tracking_token` UNIQUE, `(status, created_at)`, `(aggregate_type, aggregate_id)`, `(festival_id, status)`.

---

## Domain Events

Все события — queued jobs (`ShouldQueue`), обрабатываются через Laravel Bus chain.

### Order уведомления

| Event | Данные | Что делает |
|-------|--------|-----------|
| **ProcessUserNotificationNewOrderTicket** | `email`, `kilter`, `ticketTypeId`, `festival` | Email "заказ создан" |
| **ProcessUserNotificationOrderPaid** | `email`, `tickets[]`, `ticketTypeId`, `comment?`, `promocode?` | Email с PDF билетами |
| **ProcessUserNotificationOrderPaidFriendly** | `email`, `tickets[]`, `ticketTypeId`, `comment?`, `promocode?` | Email Friendly (без ссылки `/myOrders`) |
| **ProcessUserNotificationOrderPaidLiveTicket** | `email`, `ticketTypeId`, `typeOrPaymentId`, `kilter` | Email для live |
| **ProcessUserNotificationOrderCancel** | `email`, `ticketTypeId` | Email об отмене |
| **ProcessUserNotificationOrderDifficultiesArose** | `orderId`, `email`, `comment`, `ticketTypeId` | Email о трудностях |
| **ProcessUserNotificationOrderLiveTicketIssued** | `email`, `ticketTypeId` | Email о выдаче live |
| **ProcessUserNotificationListApproved** | `email`, `tickets[]`, `festivalId`, `?locationId` | Email при одобрении заказа-списка → `OrderListApproved` (использует `Location.email_template`) |
| **ProcessUserNotificationListCancel** | `email`, `festivalId` | Email об отмене заказа-списка → `OrderListCancel` |
| **ProcessUserNotificationListDifficultiesArose** | `email`, `comment`, `festivalId` | Email о трудностях по списку → `OrderListDifficultiesArose` |

### Ticket операции

| Event | Данные | Что делает |
|-------|--------|-----------|
| **ProcessCreateTicket** | `orderId`, `quests[]` | Создаёт билеты через `TicketApplication::createList()` |
| **ProcessCancelTicket** | `orderId` | Отменяет билеты через `TicketApplication::cancelTicket()` |
| **ProcessCancelLiveTicket** | `orderId`, `guest[]` | Отменяет + пушит live с номерами |
| **ProcessPushLiveTicket** | `liveNumber`, `ticketId?` | Присваивает номер живому билету |
| **ProcessCreatingQRCode** | `TicketResponse` | Генерирует PDF + QR |

### User операции

| Event | Данные | Что делает |
|-------|--------|-----------|
| **ProcessAccountNotification** | `email`, `password` | Email с данными нового аккаунта |
| **ProcessPasswordResets** | `User` | Генерирует токен, отправляет ссылку сброса |

### Questionnaire операции

| Event | Данные | Что делает |
|-------|--------|-----------|
| **ProcessGuestNotificationQuestionnaire** | `email`, `orderId`, `ticketId` | Ссылка на анкету (если нет существующей + тип активен) |
| **ProcessInviteLinkQuestionnaire** | `email` | Email со ссылкой-приглашением |
| **ProcessTelegramSend** | `username` | POST на Telegram-бот |
| **ProcessTelegramByQuestionnaireSend** | `email` | Ищет анкету по email → Telegram |
| **ProcessReplayNotificationQuestionnaire** | `email`, `id` | Повторная отправка ссылки |

### Публикация событий

```php
// Асинхронно (по умолчанию)
Bus::chain($aggregateRoot->pullDomainEvents())->dispatch();

// Синхронно
Bus::chain($list)->onConnection('sync')->dispatch();

// С задержкой
Bus::chain($list)->delay(now()->addMinutes($delay))->dispatch();
```

---

## Repository

### OrderTicketRepositoryInterface

| Метод | Описание |
|-------|----------|
| `create(OrderTicketDto): bool` | Создание заказа |
| `getUserList(Uuid): OrderTicketItemForListResponse[]` | Заказы пользователя |
| `findOrder(Uuid): ?OrderTicketDto` | Поиск по ID |
| `getItem(Uuid): ?OrderTicketItemResponse` | Детали заказа |
| `getList(Filters): OrderTicketItemForListResponse[]` | Админ-список с фильтрами (`WHERE friendly_id IS NULL AND curator_id IS NULL`) |
| `getFriendlyList(Filters): OrderTicketItemForFriendlyListResponse[]` | Friendly-список (`WHERE friendly_id IS NOT NULL AND curator_id IS NULL`) |
| `getListsList(Filters): OrderTicketItemForListsResponse[]` | Заказы-списки (`WHERE curator_id IS NOT NULL`) — для admin/manager |
| `getCuratorList(Filters): OrderTicketItemForListsResponse[]` | Заказы-списки куратора (фильтр `curator_id` идёт через `Filters`) |
| `changeStatus(Uuid, Status, array): bool` | Смена статуса |
| `updateGuests(Uuid, array): bool` | Обновить список гостей |
| `changePrice(Uuid, float): bool` | Изменить цену (admin) |

### TicketsRepositoryInterface

| Метод | Описание |
|-------|----------|
| `createTickets(TicketDto): bool` | Создание билета |
| `deleteTicketsByOrderId(Uuid): bool` | Удаление по заказу |
| `getListIdByOrderId(Uuid, bool): Uuid[]` | IDs билетов |
| `getTicket(Uuid, bool): TicketResponse` | Получить билет |
| `setInBaza(TicketResponse): bool` | Синхронизация с БД Baza |
| `setInBazaLive(int, ?Uuid): bool` | Привязка live-номера |
| `checkLiveNumber(int): bool` | Проверка уникальности номера |

### PromoCodeInterface

| Метод | Описание |
|-------|----------|
| `find(string, Uuid, Uuid): ?PromoCodeDto` | Поиск с проверкой лимита |
| `getList(): PromoCodeDto[]` | Все промокоды |
| `getItem(Uuid): ?PromoCodeDto` | Один промокод |
| `createOrUpdate(PromoCodeDto): bool` | Создать/обновить |

### UserRepositoriesInterface

| Метод | Описание |
|-------|----------|
| `create(AccountDto, string): bool` | Создание аккаунта |
| `findAccountByEmail(string): ?UserInfoDto` | Поиск по email |
| `findAccountById(Uuid): ?UserInfoDto` | Поиск по ID |
| `getList(AccountGetListFilter, Order): UserInfoDto[]` | Список с фильтрами |
| `edit(Uuid, UserInfoDto): bool` | Редактирование |
| `chanceRole(Uuid, string): bool` | Смена роли |

### QuestionnaireRepositoryInterface

**Изменения:** Поля анкеты перенесены в JSON-колонку `data`. Стандартные поля (`agy`, `phone`, `telegram`, `vk`, `email`) стали nullable. Добавлена связь с типом анкеты через `questionnaireTypeId`.

| Метод | Описание |
|-------|----------|
| `create(QuestionnaireTicketDto): bool` | Создание анкеты (данные в `data` JSON) |
| `getList(Filters): Collection` | Список с фильтрами (по `data->$.fieldName`) |
| `existByEmail(string): bool` | Проверка email (nullable поле) |
| `findByEmail(?string): ?QuestionnaireTicketDto` | Поиск по email (nullable) |
| `get(int): QuestionnaireTicketDto` | По ID (с парсингом `data` в `extraData`) |
| `findByOrderIdAndTicketId(Uuid, Uuid): ?QuestionnaireTicketDto` | Поиск по заказу и билету |
| `findByEmailAndQuestionnaireType(string, Uuid): ?QuestionnaireTicketDto` | Поиск по email + типу анкеты |
| `cacheStatus(int, string): bool` | Обновить статус |

### TicketTypeRepositoryInterface

| Метод | Описание |
|-------|----------|
| `getList(TicketTypeGetListFilter, Order): Collection` | Список типов билетов с фильтрами |
| `getItem(Uuid): TicketTypeDto` | По ID |
| `editItem(Uuid, TicketTypeDto): bool` | Редактировать |
| `create(TicketTypeDto): bool` | Создать |
| `remove(Uuid): bool` | Удалить |

### LocationRepositoryInterface

**Путь:** `Backend/src/Location/Repositories/LocationRepositoryInterface.php`
**Реализация:** `InMemoryMySqlLocationRepository`

| Метод | Описание |
|-------|----------|
| `getList(Filters, Order): Collection` | Список локаций с фильтрами |
| `getItem(Uuid): LocationDto` | По ID |
| `create(LocationDto): bool` | Создать |
| `editItem(Uuid, LocationDto): bool` | Редактировать |
| `remove(Uuid): bool` | Удалить |

### TicketTypePriceRepositoryInterface

**Путь:** `Backend/src/TicketTypePrice/Repositories/TicketTypePriceRepositoryInterface.php`
**Реализация:** `InMemoryMySqlTicketTypePriceRepository` (таблица `ticket_type_price`, soft delete)

| Метод | Описание |
|-------|----------|
| `getList(Filters, Order): Collection` | Список волн (фильтр `ticket_type_id`) |
| `getItem(Uuid): TicketTypePriceDto` | Одна волна |
| `create(TicketTypePriceDto): bool` | Создать |
| `editItem(Uuid, TicketTypePriceDto): bool` | Редактировать |
| `remove(Uuid): bool` | Soft delete (помечает запись как удалённую) |

**DTO `TicketTypePriceDto`:** `id` (Uuid), `ticket_type_id` (Uuid), `price` (float), `before_date` (Carbon).

**Связь с TicketType:** `Tickets\TicketType\Repository\InMemoryTicketTypeRepository::buildBuilder()` берёт текущую цену билета подзапросом к `ticket_type_price` (`before_date >= CURDATE()`, ORDER BY `before_date` ASC LIMIT 1) — поведение не изменено. Новый CRUD управляет содержимым этой таблицы через API.

### HistoryRepositoryInterface

**Путь:** `Backend/src/History/Repositories/HistoryRepositoryInterface.php`
**Реализация:** `InMemoryMySqlHistoryRepository` (таблица `domain_history`)

| Метод | Описание |
|-------|----------|
| `save(SaveHistoryDto): void` | Сохранить событие истории |
| `getByAggregateId(string): DomainHistoryDto[]` | Получить всю историю агрегата по ID |

### QrOrderRepositoryInterface

**Путь:** `Backend/src/QrOrder/Repositories/QrOrderRepositoryInterface.php`
**Реализация:** `InMemoryMySqlQrOrderRepository` (таблица `qr_orders`)

Приём заказов от витрины qr (S2S) + чтение для админки org. Модуль без AggregateRoot — пассивная сущность (как `Location`). Чтение списка идёт через QueryBus (`QrOrderGetListQuery` + `QrOrderGetListQueryHandler`, паттерн `Location`), запись — тонким слоем `QrOrderApplication`.

| Метод | Описание |
|-------|----------|
| `create(QrOrderDto): bool` | Принять заказ (идемпотентно по `id`) |
| `getList(Filters, Order, int page, int perPage): Collection` | Страница списка для админки (проекции `QrOrderItemForListResponse`, без payload) |
| `countList(Filters): int` | Общее число заказов под фильтрами (для `totalNumber`) |
| `existsById(Uuid): bool` | Заказ уже принят (`id` == id заказа qr/org → идемпотентность) |
| `findById(Uuid): ?QrOrderDto` | Полный заказ по ID (с payload) |
| `changeStatus(Uuid, string): bool` | Сменить статус (API №2) |
| `markIssued(Uuid, Carbon): bool` | Отметить выданным (защита от повторной выдачи) |
| `clearIssued(Uuid): bool` | Снять отметку выдачи (при сбое — выдать повторно) |

**Whitelist фильтров `getList`** (в `QrOrderGetListQueryHandler`): `email` (LIKE), `city` (LIKE), `status` (EQUAL), `festival_id` (EQUAL), `type_order` (EQUAL). Сортировка через `Order` (`Order::none()` на кривом `orderBy`). Пагинация `forPage(page, perPage)`, total — `count()` под теми же фильтрами.

### TemplateRepositoryInterface (AF-3)

**Путь:** `Backend/src/Template/Repositories/TemplateRepositoryInterface.php`
**Реализация:** `InMemoryMySqlTemplateRepository` (таблицы `templates` + `template_versions`)

Шаблоны писем/PDF: резолв для рендера (с fallback на blade) + admin-CRUD/версии. Модуль без AggregateRoot (как `Location`/`QrOrder`). БД только здесь.

| Метод | Описание |
|-------|----------|
| `findActive(slug, kind): ?TemplateDto` | Активный шаблон для рендера. `null` → нет/неактивна → fallback на blade. Точка резолва для PDF и писем |
| `findBySlugKind(slug, kind): ?TemplateDto` | Шаблон по `(slug, kind)` независимо от `active` — для идемпотентного импорта blade |
| `getList(Filters, Order): Collection` | Список для админки (фильтры + сортировка) |
| `getItem(Uuid): TemplateDto` | По ID (с `body`/`draft_body`) |
| `create(TemplateDto): bool` | Создать |
| `editItem(Uuid, TemplateDto): bool` | Редактировать |
| `activate(Uuid, bool): bool` | Включить/выключить (деактивация = откат на blade) |
| `saveDraft(Uuid, draftBody): bool` | Сохранить черновик (`body` не затрагивается) |
| `publish(Uuid, body, authorId, comment): bool` | Опубликовать `body` + снапшот в `template_versions` (транзакционно) |
| `getVersions(Uuid): Collection` | Версии (новые сверху) |
| `rollback(templateId, versionId, authorId): bool` | Откат `body` к версии (+ новый снапшот). `DomainException` если версии нет |

### EmailMessageRepositoryInterface (Ф2)

**Путь:** `Backend/src/EmailDelivery/Repositories/EmailMessageRepositoryInterface.php`
**Реализация:** `InMemoryMySqlEmailMessageRepository` (таблица `email_messages`)

Трекинг отправки писем: текущий статус письма здесь, таймлайн событий — в `domain_history` (`aggregate_type='email'`). Модуль без AggregateRoot (как `Location`/`QrOrder`/`Template`). БД только здесь. Чтение списка — через QueryBus (`EmailMessageGetListQuery` + `EmailMessageGetListQueryHandler`, паттерн `Location`).

| Метод | Описание |
|-------|----------|
| `create(EmailMessageDto, ?mailableBlob): bool` | Создать запись письма (`queued`) + сохранить `base64(serialize(Mailable))` для (повторной) отправки |
| `findById(Uuid): ?EmailMessageDto` | Письмо по ID |
| `findByToken(string): ?EmailMessageDto` | Письмо по токену пикселя прочтения (Ф3) |
| `getMailableBlob(Uuid): ?string` | `base64(serialize(Mailable))` для отправки/повтора |
| `markSending(Uuid): bool` | `status → sending`, `attempts++` |
| `markSent(Uuid, ?providerMessageId): bool` | `status → sent`, `sent_at = now` |
| `markFailed(Uuid, error): bool` | `status → failed`, `error = причина` |
| `requeue(Uuid): bool` | `status → queued` (повтор из админки) |
| `markOpened(Uuid): bool` | `status → opened`, `opened_at = now` (идемпотентно, только из `sent`/`delivered`, Ф3) |
| `getList(Filters, Order, int page, int perPage): Collection` | Страница списка для админки (проекции `EmailMessageItemForListResponse`, без `meta`/`mailable`) |
| `countList(Filters): int` | Общее число писем под фильтрами (для `totalNumber`) |
| `getByAggregate(aggregateType, Uuid): Collection` | Письма агрегата (для экрана qr — «весь путь» заказа) |
| `existsByExternalId(string): bool` | Идемпотентность S2S-приёма (Ф4): письмо с таким `external_id` уже принято |

**Whitelist фильтров `getList`** (в `EmailMessageGetListQueryHandler`): `recipient` (LIKE), `status` (EQUAL), `event` (EQUAL), `source` (EQUAL), `festival_id` (EQUAL), `aggregate_id` (EQUAL). Сортировка через `Order` (`Order::none()` на кривом `orderBy`). Пагинация `forPage(page, perPage)` (`page` ≥ 1, `perPage` 1..100, иначе 20).

### BazaDeliveryRepositoryInterface (AF-4)

**Путь:** `Backend/src/BazaDelivery/Repositories/BazaDeliveryRepositoryInterface.php`
**Реализация:** `InMemoryMySqlBazaDeliveryRepository` (таблица `baza_deliveries`)

Трекинг доставки билетов в Baza: текущий статус доставки здесь, таймлайн всех попыток — в `domain_history` (`aggregate_type='baza_delivery'`). Модуль без AggregateRoot (как `EmailDelivery`). БД только здесь. Чтение списка — через QueryBus (`BazaDeliveryGetListQuery` + `Handler`, паттерн `Location`).

| Метод | Описание |
|-------|----------|
| `create(BazaDeliveryDto): bool` | Создать запись доставки (`queued`). Одна строка на `(ticket_id, target)` — UNIQUE |
| `findById(Uuid): ?BazaDeliveryDto` | Доставка по ID |
| `findByTicketTarget(Uuid, target): ?BazaDeliveryDto` | Текущая доставка по `(билет, цель)` — для идемпотентного диспатча |
| `markSending(Uuid): bool` | `status → sending`, `attempts++` |
| `markDelivered(Uuid): bool` | `status → delivered`, `delivered_at = now` |
| `markFailed(Uuid, error): bool` | `status → failed`, `error = причина` |
| `requeue(Uuid): bool` | `status → queued` (авто-ретрай / повтор из админки) |
| `getList(Filters, Order, int page, int perPage): Collection` | Страница списка (проекции `BazaDeliveryItemForListResponse`) |
| `countList(Filters): int` | Общее число доставок под фильтрами (для `totalNumber`) |
| `getByOrderId(Uuid): Collection` | Доставки билетов заказа (для «весь путь» qr) |
| `countStuck(?Uuid festivalId): int` | Число застрявших (`failed`) — для дашборда |
| `statusCounts(?Uuid festivalId): array` | Счётчики по статусам (+ `stuck` = failed) — для дашборда |

**Whitelist фильтров `getList`** (в `BazaDeliveryGetListQueryHandler`): `status` (EQUAL), `target` (EQUAL), `ticket_id` (EQUAL), `order_id` (EQUAL), `festival_id` (EQUAL), `source` (EQUAL), `name` (LIKE), `email` (LIKE). Сортировка через `Order`. Пагинация `forPage(page, perPage)`.

**Таблица `baza_deliveries`** (миграция `2026_06_19_120000`): `id` (uuid PK), `ticket_id` (uuid; для auto — id строки авто), `order_id` (nullable), `target` (el_tickets/spisok_tickets/live_tickets/auto), `status`, `attempts` (tinyint), `error`, `name`/`email` (ПДн), `number` (номер живого билета), `festival_id`, `source` (qr_pipeline/org_event), `subject_blob` (longText — base64(serialize(TicketResponse)) для el/spisok, миграция `2026_06_19_130000`), `delivered_at`, timestamps. UNIQUE `(ticket_id, target)`; индексы `(status, created_at)`, `(festival_id, status)`, `order_id`/`target`/`status`/`source`. **Eloquent-модель** `App\Models\BazaDelivery\BazaDeliveryModel` (касты `attempts`/`number` → int, `delivered_at` → datetime).

**DTO:** `BazaDeliveryDto` (фабрики `queued(id, ticketId, target, BazaDeliveryContext)`, `fromState($row)`). **Responses:** `BazaDeliveryItemForListResponse` (snake_case для списка, с `error`) + `BazaDeliveryGetListResponse` (collection + totalCount).

---

## Схема связей агрегатов

```
 Festival (read-only)
     |
     | festival_id
     v
 Account ←── user_id ── OrderTicket ←── friendly_id ── Account (pusher)
     |                    |       ↖── curator_id ────── Account (curator)
     |                    |       ↖── location_id ───── Location (для списков)
     |                    | order_ticket_id
     |                    v
     |               Ticket
     |                    |
     |                    | orderId + ticketId
     |                    v
     └──────────── Questionnaire

 PromoCode ── ticket_type_id ── OrderTicket
     |
     | order_tickets_id
     v
 ExternalPromoCode

 OrderTicket ──── aggregate_id ──── DomainHistory (таблица domain_history)
 Ticket      ────────────────────── DomainHistory
 (любой агрегат с trait HasHistory записывает события в общую таблицу истории)
```

---

## Criteria и Filters

Система построения типобезопасных запросов:

```php
// Создание фильтра
$filter = new Filter(
    new FilterField('email'),
    FilterOperator::CONTAINS(),
    new FilterValue('@example.com')
);
$filters = new Filters([$filter]);

// Применение в репозитории
$criteria = new Criteria($filters, Order::createDesc('created_at'), 0, 50);
$this->filterBuilder->build($this->model, $criteria->filters);
```

**Операторы:** `=`, `!=`, `>`, `<`, `LIKE`, `CONTAINS`, `NOT_CONTAINS`
**OrderType:** `asc`, `desc`, `none`

---

## Изменения в схеме БД (ветка questionnaire_multi)

### Таблица `questionnaire`

**Удалённые колонки** (перенесены в `data` JSON):
`agy`, `howManyTimes`, `questionForSysto`, `is_have_in_club`, `creationOfSisto`, `activeOfEvent`, `whereSysto`, `musicStyles`, `name`, `vk`

**Новая структура:**
```sql
id INT PRIMARY KEY,
email VARCHAR(255) NULL,
phone VARCHAR(50) NULL,
telegram VARCHAR(100) NULL,
vk VARCHAR(255) NULL,
status VARCHAR(50),
questionnaire_type_id INT,
data JSON,  -- динамические поля из questionnaire_type.questions
user_id VARCHAR(36),
order_id VARCHAR(36),
ticket_id VARCHAR(36),
link VARCHAR(255) NULL,
created_at TIMESTAMP,
updated_at TIMESTAMP
```

### FilterBuilder

**Изменение:** проверка значений изменена с `!== null` на `!empty()` для корректной обработки `0` и пустых строк.

```php
// Было
if ($filter->value()->value() !== null) { ... }

// Стало
if (!empty($filter->value()->value())) { ... }
```

### Исправление вывода полей из JSON `data` (2026-04-11)

**Проблема:** В админке анкет все поля отображались как "—" (прочерк), хотя данные присутствовали в JSON-колонке `data`.

**Найденные проблемы:**

1. **`phone` колонка имела NOT NULL ограничение** — при вставке записей где данные только в JSON `data`, Laravel пытался вставить NULL в корневую колонку `phone` что вызывало ошибку.

2. **`InMemoryMySqlQuestionnaireRepository::getList()` использовал `each()` вместо `map()`** — это означало что модели НЕ конвертировались в DTO, и контроллер возвращал сырые данные модели (корневые колонки) вместо данных из JSON `data`.

**Исправления:**

1. Миграция `2026_04_11_140005_fix_questionnaire_phone_nullable.php` — сделала `phone` NULLable.

2. Изменён `each()` на `map()` в `InMemoryMySqlQuestionnaireRepository::getList()`:
```php
// Было
->each(fn (QuestionnaireModel $model) => QuestionnaireTicketDto::fromState($model->toArray()));

// Стало
->map(fn (QuestionnaireModel $model) => QuestionnaireTicketDto::fromState($model->toArray()));
```

**Тесты:**
- Unit: `tests/Unit/Questionnaire/Dto/QuestionnaireTicketDtoTest.php` (4 теста)
- Feature: `tests/Feature/Questionnaire/QuestionnaireDataFieldsApiTest.php` (2 теста)
- Сидер: `QuestionnaireTestDataSeeder` — создаёт 3 тестовые анкеты с данными только в JSON `data`
