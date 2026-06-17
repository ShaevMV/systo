# Спека: Система отправки писем по шаблонам — привязка по событиям + контроль пути доставки

**Статус:** черновик на согласование (2026-06-17). Источник: запрос владельца.
**Связано:** `.claude/specs/template-system.md` (AF-3), `.claude/specs/template-aggregate-and-bindings.md` (привязки, Часть B), `.claude/specs/admin-frontend-vite-sakai.md` (AdminFront), `DOMAIN.md`, `API.md`, `BUSINESS_RULES.md`.
**Техдолг:** AF-6 (подтверждение доставки письма) — частично закрывается; провайдерская доставка (вебхуки delivered/bounced) остаётся в v2.8.0.

---

## 0. Решения владельца (зафиксированы 2026-06-17)

| # | Вопрос | Решение |
|---|--------|---------|
| 1 | Измерение «событие» в привязках | **Ось `event` + все письма.** В `template_bindings` добавляем `event`; охватываем заказные/листовые И регистрационные/парольные/анкетные письма. |
| 2 | Как qr запускает письма | **Пайплайн заказа + отдельный S2S-API уведомлений.** Письма заказа идут из qr-пайплайна выдачи; для регистрации/пароля и прочих не-заказных писем, инициированных на qr — отдельный S2S-эндпоинт. |
| 3 | Глубина контроля пути | **Статусы отправки + ретрай + свой пиксель прочтения.** Без транзакционного провайдера (вебхуки delivered/bounced — позже, AF-6/v2.8.0). |
| 4 | Порядок | **Спека сначала** (этот документ), затем реализация по фазам. Коммиты — только после одобрения. |

---

## 1. Что есть сегодня (опора, не переписываем)

| Подсистема | Где | Состояние |
|-----------|-----|-----------|
| Движок шаблонов | `Backend/src/Template/` | Mustache + сущность `Template` + версии/откат + CRUD/превью. ✅ AF-3 |
| Рендер письма из БД | `App\Mail\Concerns\RendersDbTemplate::renderDbOrView($slug,$vars)` | Активный DB-шаблон → `$this->html(render)`, иначе blade-fallback `email.{slug}`. ✅ |
| Привязки | `Backend/src/TemplateBinding/` + `TemplateBindingResolver` | Резолв по `(festival, order_type, ticket_type)` + `is_default`. **Без `event`.** |
| Mailable (15) | `Backend/app/Mail/*` | Каждый зовёт `renderDbOrView('<фикс-slug>', $vars)`. |
| Отправка | `ProcessUserNotification*` (queued `DomainEvent`) → `Mail::to($email)->send(new X(...))` | Асинхронно через `Bus::chain` + queue `database`. **Трекинга нет** (только `LogSentMessage` в монолог). |
| qr-пайплайн | `Backend/src/QrOrder/Application/Issuance/IssueOrderJob` + `Step/*` | Стратегии regular/friendly/list/live → шаги (билеты → письмо → Baza → Telegram). Лог шагов в канал `qr_pipeline` (**файл, не БД**). |
| История qr | `domain_history`, `aggregate_type='qr_order'` | 3 события: `created` / `status_changed` / `issued`. |
| Экран QR-заказов | `FrontEnd|AdminFront/src/views/admin/QrOrderListView.vue` + Vuex `appQrOrder` | Таблица + диалог + Timeline. **Нет:** скачивания PDF, данных по письмам, вида пайплайна. |
| Скачивание PDF (образец) | обычные заказы: `order/getTicketPdf` → `listUrl[]`; фронт `getUrlForPdf` | Переиспользуем паттерн для qr. PDF лежат в `storage/app/public/tickets/{ticketId}.pdf`. |

**Чего не хватает:** оси `event` в привязках; персистентного статуса письма (queued→sent→opened) + ретрая; пикселя прочтения; S2S-канала писем от qr; персиста пайплайна qr в БД; скачивания PDF и ссылки на письма в экране qr.

---

## 2. Целевая архитектура (поток письма)

```
ТРИГГЕР письма (3 источника):
  (A) qr-пайплайн выдачи  — SendOrderEmailStep / SendListEmailStep / SendLiveEmailStep
  (B) S2S qr-уведомления  — POST /api/v1/emailNotification/send  (регистрация/пароль/прочее от qr)
  (C) legacy org-события  — ProcessUserNotification* (старый order_tickets-канал, deprecated)
        │
        ▼
  EmailEvent + EmailContext{ to, festival_id?, order_type?, ticket_type_id?, vars, attachments, aggregate }
        │
        ▼
  TemplateBindingResolver.resolve(kind=email, event, festival, order_type, ticket_type)
        │   нет привязки → EmailEvent->defaultSlug()  (= текущий фикс-slug, полная обратная совместимость)
        ▼
  MailDispatcher.send(...)
        ├─ repo.create(EmailMessage status=queued, event, slug, recipient, aggregate, tracking_token)
        ├─ history 'email_queued' (aggregate_type='email')
        └─ SendEmailJob::dispatch(emailMessageId)        ← async, Laravel job (queue database)
                 │
                 ▼  SendEmailJob::handle()
                 ├─ status → sending  (+ history)
                 ├─ build Mailable(slug, vars, attachments) + инъекция <img> пикселя + header X-Systo-Email-Id
                 ├─ Mail::to(recipient)->send(mailable)
                 ├─ успех → status sent (+sent_at, smtp message-id, +history)
                 └─ ошибка → status failed (+error, +history); ретрай (tries=N) / ручной resend из админки
                                                          │
        пользователь открыл письмо ──► GET /api/v1/mail/open/{token}.gif (public)
                 └─ status → opened (+opened_at, idempotent) (+history 'email_opened')
```

**Что хранит состояние:**
- `email_messages` — **текущий статус** письма (для списка/фильтра/ретрая). *(как `templates`)*
- `domain_history` (`aggregate_type='email'`) — **таймлайн событий** письма (queued/sending/sent/failed/opened). *(как аудит шаблонов)*

**Новый модуль** `Backend/src/EmailDelivery/` (пассивная сущность, паттерн `Location`/`QrOrder`: БД только в репозитории, чтение списка через QueryBus). **Расширяем** `TemplateBinding` (ось `event`).

---

## 3. Часть 1 — Привязки шаблонов по событиям

### 3.1. `EmailEvent` — канонический справочник событий
`Backend/src/EmailDelivery/Domain/EmailEvent.php` (Enum по образцу `Status`/`TypeOrder`). Единственный источник: код события → дефолтный slug → Mailable. Все 15 текущих писем:

| `event` | default slug | Mailable | Источник |
|---------|-------------|----------|----------|
| `order_created` | `orderToCreate` | `OrderToCreate` | org/qr |
| `order_paid` | `orderToPaid` | `OrderToPaid` | qr-пайплайн |
| `order_paid_friendly` | `TypeTicketMailOrderToPaidFriendly1` | `OrderToPaidFriendly` | qr-пайплайн |
| `order_paid_live` | `orderToPaidLiveTicket` | `OrderToPaidLiveTicket` | qr-пайплайн (live) |
| `order_cancel` | `orderToCancel` | `OrderToCancel` | org |
| `order_changed` | `orderToChangeTicket` | `OrderToChangeTicket` | org |
| `order_difficulties` | `orderToDifficultiesArose` | `OrderToDifficultiesArose` | org |
| `order_live_issued` | `orderToLiveTicketIssued` | `OrderToLiveTicketIssued` | org |
| `list_approved` | `orderListApproved` | `OrderListApproved` | qr-пайплайн (list) |
| `list_cancel` | `orderListCancel` | `OrderListCancel` | org |
| `list_difficulties` | `orderListDifficultiesArose` | `OrderListDifficultiesArose` | org |
| `user_registered` | `newUser` | `CreateUser` | org/qr (S2S) |
| `password_reset` | `passwordResets` | `UserPasswordResets` | org/qr (S2S) |
| `invite` | `invate` | `InviteLink` | org |
| `questionnaire` | `questionnaire` | `TicketQuestionnaire` | org/qr |

> Slug `orderToPaid`/`...Friendly` сейчас ещё переключается динамически по типу билета (`emailView`). После ввода `event`-привязок это поведение **поглощается** резолвером (привязка `(event=order_paid, ticket_type_id=X) → шаблон`). Динамический `emailView` остаётся fallback'ом (шаг 4 резолва).

### 3.2. Изменение схемы `template_bindings`
Миграция `…_add_event_to_template_bindings.php`:
```php
$table->string('event', 40)->nullable()->index()->after('order_type'); // null = любое событие (wildcard)
```
`NULL` = «любое событие» → **существующие привязки продолжают работать как раньше** (для PDF/paid-резолва в `getTicket()`).

### 3.3. Изменение резолвера
`TemplateBindingResolver::resolve()` + `TemplateBindingDto` получают параметр/поле `event`:
- `matches()`: добавить `($this->event === null || $this->event === $event)`.
- `specificity()`: `event` — **сильнейший** дискриминатор. Вес: `event(8) + ticket_type(4) + order_type(2) + festival(1)`.
- Сигнатура: `resolve(array $bindings, string $kind, ?string $event, ?string $festivalId, ?string $orderType, ?string $ticketTypeId)`.
- Все текущие вызовы (`InMemoryMySqlTicketsRepository::getTicket()`) обновляются: для PDF/выдачи передаём событие issuance (`order_paid`/`list_approved`/`order_paid_live`); старые привязки `event=null` всё равно совпадают.

### 3.4. Точка интеграции slug-by-event
Резолв slug по событию выполняет **вызывающая сторона письма** (она знает event + festival + order_type + ticket_type), затем передаёт результат в `MailDispatcher` (см. §4). Mailable получает итоговый slug через лёгкий сеттер (`forceSlug(?string)`), а `renderDbOrView()` использует его вместо фикс-slug; `null` → текущий дефолт. **Mailable'ы не переписываем** — добавляем опциональную подмену slug.

### 3.5. Админка
Экран «Привязки шаблонов» (`appTemplateBinding`, уже есть по AF-3 Часть B) расширяем селектом **Событие** (из `EmailEvent`, «любое» = пусто). Колонка `event` в таблице привязок.

### 3.6. Обратная совместимость
Пустая ось `event` (NULL) = поведение до фичи. Нет привязки под событие → `EmailEvent->defaultSlug()` = текущий фикс-slug → текущий рендер (Mustache/blade). **Регресс рендера не меняется.**

### 3.7. Тесты — Часть 1 (unit резолвера, без БД)
- Привязка `event=order_cancel` (остальное wildcard) выбирается для письма отмены и **игнорируется** для `order_paid`.
- При двух подходящих (одна с `event`, другая с `ticket_type` но `event=null`) — выигрывает с `event` (вес 8 > 4).
- `event=null` совпадает с любым событием (обратная совместимость для PDF/paid).
- Нет привязки под событие → `null` (вызывающий уходит на `defaultSlug`).

---

## 4. Часть 2 — Трекинг отправки (email_messages + статусы + ретрай)

### 4.1. Таблица `email_messages`
Миграция `…_create_email_messages_table.php`:

| Поле | Тип | Смысл |
|------|-----|-------|
| `id` | uuid PK | |
| `event` | string(40) index | `EmailEvent` |
| `recipient` | string index | email получателя (ПДн — admin-only чтение) |
| `subject` | string | тема (для списка) |
| `template_slug` | string | какой slug фактически отрендерён |
| `status` | string(20) index | `EmailStatus`: `queued`/`sending`/`sent`/`failed`/`opened` |
| `attempts` | unsignedTinyInteger | число попыток |
| `error` | text NULL | последняя ошибка |
| `source` | string(20) index | `qr_pipeline`/`qr_intake`/`org_event` |
| `aggregate_type` | string(30) NULL index | `qr_order`/`order_ticket`/`user` |
| `aggregate_id` | uuid NULL index | id заказа/пользователя (для связи с экраном qr) |
| `festival_id` | uuid NULL index | для фильтра |
| `tracking_token` | string(64) UNIQUE | токен пикселя прочтения (≠ id, не светим PK) |
| `provider_message_id` | string NULL | message-id от SMTP (на будущее, AF-6) |
| `meta` | json NULL | доп. контекст (cast `array`) |
| `sent_at` | timestamp NULL | момент отправки (ставит код — это не creation-метка) |
| `opened_at` | timestamp NULL | момент первого открытия |
| `created_at`/`updated_at` | timestamp | **DB DEFAULT** (не задаём в PHP) |

Индексы: `(status, created_at)`, `(aggregate_type, aggregate_id)`, `(festival_id, status)`.

> Соблюдаем «единый формат данных»: `meta` — cast `array` (массив в `create`, без `json_encode`), `created_at/updated_at` — DB DEFAULT. `sent_at`/`opened_at` — событийные метки, их ставит код в момент события (не дубль DB DEFAULT).

### 4.2. `EmailStatus` — VO + машина состояний (provider-ready)
`Backend/src/EmailDelivery/Domain/ValueObject/EmailStatus.php` (паттерн `StatusForBillingValueObject`):
```
queued ──► sending ──► sent ──► delivered ──► opened
   │           │          │          │
   └──► failed ◄┘         └─► bounced ◄┘     (failed/bounced → queued при ретрае)
```
- `queued` — принято нашей системой, ждёт воркера.
- `sending` — воркер взял в работу.
- `sent` — **передано на SMTP-сервер** (`mail.solarsysto.ru` принял). Это НЕ «доставлено в ящик».
- `delivered` / `bounced` — **подтверждение доставки/отскок от почтового сервера получателя**. С нашим SMTP **не наблюдаемо** — заполняется только при подключении транзакционного провайдера с вебхуками (AF-6/v2.8.0). До этого статусы заведены, но не используются.
- `opened` — пиксель сработал: письмо **точно дошло и открыто** (сильнейший доступный сейчас сигнал доставки). Достижим из `sent`/`delivered`. Идемпотентен.
- `failed` — сбой ДО/ВО время передачи на SMTP (очередь/SMTP отверг). В `error` — текст ошибки = **где застряло**.

> **«Доставлено до пользователя» и «где застряло» (требование владельца):** до провайдера достоверно знаем путь до `sent` и точку залипания до неё (`queued`/`failed` + текст ошибки), плюс `opened` (точное «дошло+прочитано»). Полное `delivered`/`bounced` (доставка в ящик / отскок после нашей передачи) даёт только транзакционный провайдер — статусы и таймлайн уже спроектированы под него, подключение в AF-6 = только обработчик вебхука, без переделки модели.

### 4.3. Репозиторий
`EmailMessageRepositoryInterface` → `InMemoryMySqlEmailMessageRepository` (БД только здесь):

| Метод | Назначение |
|-------|-----------|
| `create(EmailMessageDto): bool` | новое письмо (status=queued) |
| `findById(Uuid): ?EmailMessageDto` | для job/детали |
| `findByToken(string): ?EmailMessageDto` | для пикселя |
| `changeStatus(Uuid, EmailStatus, ?array $patch): bool` | смена статуса (+ error/sent_at/opened_at/attempts) |
| `getList(Filters, Order, page, perPage): Collection` | список для админки (проекция, без `meta`) |
| `countList(Filters): int` | total |
| `getByAggregate(string $type, Uuid $id): Collection` | письма заказа (для экрана qr — «линк на данные по почтам») |

Чтение списка — через QueryBus (`EmailMessageGetListQuery(Handler)`, whitelist фильтров: `status`/`event`/`recipient` LIKE/`festival_id`/`source`/`aggregate_id`), пагинация — паттерн `QrOrderGetListQueryHandler`.

### 4.4. `MailDispatcher` (Application) — единая точка отправки
`Backend/src/EmailDelivery/Application/MailDispatcher.php`:
```php
public function send(EmailEvent $event, EmailContext $ctx): Uuid
{
    $slug = $this->bindingResolver->resolve(
        $this->activeBindings(), 'email',
        $event->value(), $ctx->festivalId, $ctx->orderType, $ctx->ticketTypeId,
    ) ?? $event->defaultSlug();

    $id = Uuid::random();
    $this->repo->create(EmailMessageDto::queued($id, $event, $ctx, $slug, $token = $this->token()));
    $this->history->save(new SaveHistoryDto($id->value(), new EmailQueuedEvent($event->value()), null, $ctx->actorType));
    SendEmailJob::dispatch($id);                 // async
    return $id;
}
```
`EmailContext` (DTO) — `to`, `festivalId?`, `orderType?`, `ticketTypeId?`, `vars[]`, `attachments[]` (пути PDF), `aggregateType?`, `aggregateId?`, `source`, `actorType`, плюс **готовый Mailable** (см. §3.4: вызывающий строит свой Mailable, dispatcher подменяет ему slug и трекинг).

### 4.5. `SendEmailJob` (Application/Job, `ShouldQueue`)
- `tries = config('mail_delivery.tries', 3)`, `backoff` экспоненциальный.
- `handle()`: load → `sending` → `forceSlug` + инъекция пикселя + header → `Mail::to()->send()` → `sent` (+`sent_at`, message-id) + history. Ошибка → throw (ретрай очереди).
- `failed()`: `failed` + `error` + history + лог в канал (по образцу `IssueOrderJob::failed`).
- Логирование — отдельный канал `mail_delivery` (JSON, masked email; по образцу `PipelineLog`).

### 4.6. Интеграция трёх источников
- **(A) qr-пайплайн:** `SendOrderEmailStep`/`SendListEmailStep`/`SendLiveEmailStep` вместо прямого `Mail::send()` зовут `MailDispatcher::send(EmailEvent::ORDER_PAID/..., $ctx)`. `aggregate_type='qr_order'`, `aggregate_id`=id qr-заказа, `source='qr_pipeline'`, `actorType=QR`. Это даёт «линк на письма» прямо из экрана qr.
- **(C) legacy org-события:** не переписываем все 15 событий сразу. Подключаем **passive-listener** на Laravel-события `MessageSending`/`MessageSent` (расширяем существующий `LogSentMessage`) — создаёт/обновляет `email_messages` для писем, ушедших мимо dispatcher (status `sent`, `source='org_event'`, без события-привязки). Так legacy-канал виден в админке без риска. Полная миграция org-событий на dispatcher — отдельной фазой/позже (org-публичный flow deprecated по pivot).
- **(B) S2S qr-уведомления:** см. §6.

### 4.7. Админ-API (`auth:api` + `admin`)
Префикс `/api/v1/emailDelivery`:
| Метод | Маршрут | Описание |
|-------|---------|----------|
| POST | `/getList` | список писем (фильтр+пагинация+total) |
| GET | `/getItem/{id}` | письмо + таймлайн (`domain_history` `aggregate_type=email`) |
| POST | `/resend/{id}` | повторная отправка (новый `SendEmailJob`, +attempts, +history) |

### 4.8. Админ-экран (AdminFront)
«Доставка писем»: `DataTable` (статус-Tag, событие, получатель, фестиваль, дата, кол-во попыток) + фильтры + диалог детали (поля + Timeline + кнопка «Отправить повторно»). Vuex `appEmailDelivery` + `useCrud`. Пункт меню в `AppMenu.vue`.

### 4.9. Тесты — Часть 2
- Unit `EmailStatus`: легальные/нелегальные переходы (`opened` только из `sent`; `failed→queued` при resend).
- Feature: `MailDispatcher::send` создаёт `email_messages(queued)` + history `email_queued` + ставит `SendEmailJob` (`Queue::fake`).
- Feature: `SendEmailJob` (`Mail::fake`) → `sent` + `sent_at` + history; при исключении Mailer → `failed` + error.
- Feature: `resend` failed-письма → новый job, `attempts++`.
- Feature: `getList` фильтры/пагинация (admin); non-admin → 403.
- Регресс: legacy `Mail::fake` письмо без dispatcher всё равно появляется в `email_messages` через listener.

---

## 5. Часть 3 — Пиксель прочтения (opened)

- При рендере HTML `SendEmailJob` дописывает `<img src="{APP_URL}/api/v1/mail/open/{tracking_token}.gif" width="1" height="1" alt="">` в конец тела (только `kind=email`, только если `config('mail_delivery.open_tracking')=true`).
- Публичный эндпоинт `GET /api/v1/mail/open/{token}.gif` (без auth, `throttle`): `findByToken` → если `status=sent` → `opened` + `opened_at` + history `email_opened` (идемпотентно). Возвращает 1×1 GIF (`Content-Type: image/gif`, `no-store`).
- **152-ФЗ:** трекинг открытия — обработка ПДн (факт прочтения). Флаг включения в `.env` (`MAIL_OPEN_TRACKING=false` по умолчанию), ревью **security-engineer** перед включением на проде. Токен — случайный (≠ PK), чтобы не перебирали заказы.
- Ограничение (честно фиксируем): пиксель не ловит клиентов с отключёнными картинками; «не открыто» ≠ «не доставлено». Точную доставку даст только провайдер (AF-6).

### Тесты — Часть 3
- Пиксель помечает `sent`→`opened` один раз; повторный заход не меняет `opened_at` и не плодит history.
- Письмо не в статусе `sent` (queued/failed) → пиксель не меняет статус.
- Флаг выключен → пиксель не инъектируется.

---

## 6. Часть 4 — S2S-канал писем от qr (не-заказные письма)

Для писем, инициированных на витрине qr и не связанных с выдачей билета (регистрация, сброс пароля и т.п.).

- **Эндпоинт:** `POST /api/v1/emailNotification/send` — middleware `auth:sanctum` + `abilities:qr:ingest` (тот же S2S-канал, что `qrOrder/*`).
- **Контракт:** `{ event, email, vars{}, festival_id?, order_type?, ticket_type_id?, aggregate_id?, external_id? }`. `event` валидируется против `EmailEvent`.
- **Идемпотентность:** опциональный `external_id` от qr (UNIQUE-проверка `meta->external_id`) — ретрай qr не плодит дублей писем.
- Обработка: контроллер → `MailDispatcher::send(EmailEvent::from($event), EmailContext(... source='qr_intake', actorType=QR))` → `{ success, email_id }`.
- **Безопасность:** `event` — только whitelist; vars санируются (Mustache и так экранирует); no-RCE by design.

### Тесты — Часть 4
- Валидный контракт → `email_messages(queued)` + `SendEmailJob`.
- Повтор с тем же `external_id` → второго письма нет (идемпотентно).
- Невалидный `event` / без scope `qr:ingest` → 422/403.

---

## 7. Часть 5 — QR-заказы: «видеть весь путь»

Цель: в экране QR-заказов — скачать билет (PDF), ссылка/блок данных по письмам, и **весь пайплайн** (приём → создание билетов → PDF/QR → письмо → Baza/Telegram).

### 7.1. Скачивание PDF билета
- **Эндпоинт** `GET /api/v1/qrOrder/getTicketPdf/{id}` (`auth:api`+`admin`): по id qr-заказа → id билетов (таблица `tickets` по `order_ticket_id`=id qr-заказа) → `listUrl[]` к `storage/app/public/tickets/{ticketId}.pdf` (переиспользуем `TicketApplication::getPdfList`/паттерн `order/getTicketPdf`). Если PDF ещё не сгенерирован (`ProcessCreatingQRCode` в очереди) — помечаем «готовится».
- **Фронт:** кнопка «Скачать билет» в диалоге детали (паттерн `appOrder/getUrlForPdf` → `window.open`).

### 7.2. Персист пайплайна в БД (чтобы показать путь)
Сейчас шаги только в `qr_pipeline.log`. Добавляем персист **в `domain_history`** (`aggregate_type='qr_order'`, новые `event_name`): `step_create_tickets`, `step_send_email`, `step_push_baza`, `step_send_telegram`, `step_link_live` со `status` (ok/fail) и кратким payload. `IssueOrderJob` после каждого шага пишет history-событие (рядом с текущим логом). Это переиспользует Timeline-механику, уже работающую в UI.
> Альтернатива — отдельная таблица `qr_order_pipeline_steps` (start/finish/error per step). См. открытый вопрос §11.3. Рекомендация: начать с `domain_history` (нулевая миграция, уже рисуется Timeline), отдельную таблицу — если понадобится тонкая метрика времени шага.

### 7.3. Сборка «всего пути»
**Эндпоинт** `GET /api/v1/qrOrder/getPipeline/{id}` (`auth:api`+`admin`) — собирает в один ответ:
```json
{
  "order":   { ...QrOrderItem... , "issued_at": "..." },
  "history": [ created, status_changed, step_*, issued ],   // domain_history aggregate_type=qr_order
  "tickets": [ { ticket_id, kilter, guest, pdf_url|null, pdf_ready } ],
  "emails":  [ { id, event, status, sent_at, opened_at } ]   // email_messages where aggregate_id=order.id
}
```
БД-доступ — только в репозиториях (`QrOrderRepository`, `HistoryRepository`, `TicketsRepository`, `EmailMessageRepository`); сборку оркеструет QueryHandler. Никаких запросов из контроллера.

### 7.4. Фронт — экран пути
В `QrOrderListView.vue` (оба фронта, приоритет — `AdminFront`): диалог детали → вкладки/секции:
1. **Заказ** (как сейчас) + кнопка «Скачать билет».
2. **Пайплайн** — вертикальный Timeline (PrimeVue `Timeline`) этапов: приём → билеты → PDF/QR → письмо → Baza → (live: связка) с иконками ok/fail/в работе.
3. **Письма** — мини-таблица писем заказа (статус/событие/открыто) + ссылка «Открыть в Доставке писем» (фильтр `aggregate_id`).

### 7.5. Тесты — Часть 5
- Feature: `getTicketPdf/{id}` (admin) → `listUrl` для выданного заказа; нет билетов → понятный ответ; non-admin → 403.
- Feature: `IssueOrderJob` пишет `step_*` события в `domain_history` (ok и fail-ветки).
- Feature: `getPipeline/{id}` собирает order+history+tickets+emails; письма коррелируют по `aggregate_id`.

---

## 8. Миграции и обратная совместимость

1. `add_event_to_template_bindings` — `event` nullable (wildcard) → старые привязки/рендер не меняются.
2. `create_email_messages` — новая таблица, ни на что не влияет.
3. `domain_history` — новые `aggregate_type='email'` и `event_name='step_*'` без изменения схемы (таблица общая).
4. **Поведение писем** при пустых привязках и до включения трекинга = текущее (dispatcher даёт тот же slug; пиксель за флагом; listener только пишет статус). Откат фичи безопасен.

---

## 9. Сводный тест-план
- **Unit:** `TemplateBindingResolver` (ось event), `EmailStatus` (машина), `EmailEvent` (slug-маппинг).
- **Feature:** dispatcher/job/resend, пиксель, S2S-уведомление (идемпотентность), qr `getTicketPdf`/`getPipeline`, listener legacy.
- **Регресс:** `TemplateConversionRenderTest` + рендер писем/PDF — зелёные (резолвер только выбирает slug). Полный PHPUnit Backend без падений (как сейчас 318+).
- Прогон — **только через Docker** (`docker exec -it php-solarSysto ./vendor/bin/phpunit`).

## 10. Фазы реализации (отдельными ветками/PR)
| Фаза | Ветка | Содержимое | Зависит |
|------|-------|-----------|---------|
| **Ф1** | `feat/email-event-bindings` | `EmailEvent` + миграция `event` + резолвер + slug-override в Mailable + селект в экране привязок. Unit-тесты. | — |
| **Ф2** | `feat/email-delivery-tracking` | `email_messages` + `EmailStatus` + repo + `MailDispatcher` + `SendEmailJob` + интеграция qr-пайплайна + legacy-listener + админ-API + экран «Доставка писем». | Ф1 |
| **Ф3** | `feat/email-open-pixel` | пиксель + endpoint + `opened` + флаг + ревью 152-ФЗ. | Ф2 |
| **Ф4** | `feat/qr-email-intake` | S2S `emailNotification/send` + идемпотентность. | Ф2 |
| **Ф5** | `feat/qr-order-pipeline-view` | `getTicketPdf` + персист шагов + `getPipeline` + UI пути в экране qr. | Ф2 |
| Доки | — | `API.md`, `DOMAIN.md`, `BUSINESS_RULES.md`, `template-system.md`, `BOARD.md`/`TECH_DEBT.md` (AF-6). | по мере |

> Видимый результат на qr-заказах (запрос владельца) даёт **Ф5**, но он зависит от Ф2 (письма) — поэтому Ф1→Ф2→Ф5 минимальный путь до «весь путь + скачать билет», Ф3/Ф4 параллельно после Ф2.

## 11. Открытые вопросы (нужно решение перед стартом Ф1)
1. **Дефолт-привязка по событию.** Делать ли `is_default` отдельно на каждый `event` или хватит одной дефолт-строки на kind (как сейчас)? *(Рекомендую: дефолт остаётся на kind; event добавляет специфичность поверх — проще.)*
2. **Хранить ли `recipient`/тему открыто в `email_messages`.** Это ПДн (admin-only чтение, как `qr_orders.email`). Альтернатива — хранить только `aggregate_id` и тянуть email из заказа. *(Рекомендую: хранить — нужно для ретрая писем без заказа, напр. password_reset; admin-only.)*
3. **Персист пайплайна qr:** `domain_history` (рекомендую) vs отдельная таблица `qr_order_pipeline_steps` (тонкие метрики времени). Решить в Ф5.
4. **Ретрай-политика `SendEmailJob`:** число попыток и backoff (предлагаю tries=3, backoff 30s/2m/10m) — подтвердить.
5. **Где живёт код «весь путь».** Расширяем `QrOrder`-модуль (новые Query) — да? *(Рекомендую: да, `getPipeline` в `QrOrder/Application`.)*

## 12. Влияние на документацию (по DoD)
- `API.md` — новые эндпоинты: `emailDelivery/*`, `emailNotification/send`, `mail/open/{token}`, `qrOrder/getTicketPdf`, `qrOrder/getPipeline`; `templateBinding` + поле `event`.
- `DOMAIN.md` — модуль `EmailDelivery` (EmailMessage, EmailEvent, EmailStatus, repo), `ActorType` (письма от qr — `qr`), расширение `TemplateBinding`.
- `BUSINESS_RULES.md` — раздел «События писем и привязки», правила трекинга/152-ФЗ.
- `TECH_DEBT.md`/`BOARD.md` — AF-6: частично закрыт (внутренние статусы + пиксель), провайдерская доставка остаётся.
