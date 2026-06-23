# Graylog Readiness — готовность к подключению Graylog (фундамент default-OFF)

> Статус: **черновик-план**. Graylog-сервер НЕ поднят. Этот документ описывает минимальный фундамент, который можно влить **сейчас** (всё default OFF, поведение без флагов не меняется), и шаги активации, когда сервер появится. Конкурирующий выбор (Loki+Grafana) — открытый вопрос для владельца, см. §8.

---

## 1. Контекст

- **org** становится admin-only; идёт интеграция **qr→org через RabbitMQ**; на staging RabbitMQ уже стоит.
- **Паттерн проекта:** новые каналы — **аддитивно, default OFF за env** (как ingest-API / вебхуки Baza). Поведение без флага не меняется.
- В планах мониторинга ранее зафиксирован **Loki+Promtail+Grafana на отдельном RU-сервере** (roadmap v2.9.0). Staging тяжёлый стек на год не тянет. **Graylog — конкурирующий выбор**, а не дополнение → требует явного go владельца/DevOps/tech-lead.
- **Baza offline-first** — логи КПП не должны теряться/блокировать при офлайне (`QUEUE_CONNECTION=sync`, своего воркера нет; топология «облако-мастер, узел опционален»).
- **152-ФЗ** — ПДн (телефон/email/telegram/ФИО/госномер/`card_number`/детские данные) в централизованный лог слать НЕЛЬЗЯ; `sql_bindings` выключить.
- Только что сделана **Фаза A** (фронт-тосты, «вердикт, а не диагноз»). **Фаза B** (бэкенд-гигиена) и **TD-10** (audit/воронка) логично «кормят» Graylog.

### Текущее состояние логирования (что уже есть)

| Компонент | Состояние |
|-----------|-----------|
| Backend `config/logging.php` | default `stack` → только `single` (LineFormatter, `laravel.log`, **не JSON**). 4 готовых JSON-канала с маскировкой: `qr_pipeline`, `qr_access`, `mail_delivery`, `baza_delivery` (все `ignore_exceptions=true`). |
| Backend Monolog | 3.10 (поддерживает `GelfHandler`/`GelfMessageFormatter`). `graylog2/gelf-php` **НЕ установлен**. |
| Backend Sentry | установлен, `send_default_pii=false`, но **`breadcrumbs.sql_bindings=true`** (утечка значений параметров SQL). Нет `before_send`/`dontReport` фильтров шума (TD-5). |
| Backend ошибки | необработанные исключения + ~11 прямых `Log::*` в `src/` идут в `laravel.log` (line-формат, без маскировки). `OrderTickets` отдаёт `file`/`line`/сырой `getMessage()` в ответ (TD-29). Нет единого `ErrorResponse`/каталога кодов. |
| Baza `config/logging.php` | стоковый, default `stack`→`single`. Кастомных каналов нет. Импортирован `SyslogUdpHandler` (шаблон UDP). `graylog2/gelf-php` нет. Sentry установлен, но **выключен** (нет DSN). `SentryEventBus` — мёртвый код. |
| Baza код | логирует через `Log::` с тегами событий (`baza.webhook.*`, `baza.outbox.*`, `baza.ingest.rejected`) + контекст-массив → хорошо мапится в structured fields. |
| Инфра | Graylog/Loki/OpenSearch **нигде нет** (только в roadmap). docker log-driver = `json-file` (ротация `setup-docker-log-rotation.sh`). RabbitMQ зажат: `vm_memory_high_watermark.absolute=256MiB`, `disk_free_limit.absolute=1GB`. |

---

## 2. Выбранный подход (транспорт)

**Структурный JSON-лог локально (Monolog) → лёгкий внешний шиппер (Vector / Fluent Bit / Filebeat) с дисковым буфером и retry → Graylog GELF input.**

Приложение **НЕ** делает сетевых вызовов в Graylog из обработчика запроса. Оно пишет структурированные JSON-логи в локальный файл; доставку берёт на себя отдельный процесс-шиппер.

### Почему не остальные варианты (скоринг)

| Вариант | Score | Почему отклонён |
|---------|:-----:|-----------------|
| **JSON-файл + внешний шиппер** | **6/10** | ✅ Выбран. Запись локальна (офлайн ок), доставка асинхронна с буфером/retry — не блокирует впуск; ноль PHP-зависимостей; не трогает RabbitMQ; совместим с Loki **и** Graylog. |
| Прямой GELF UDP/TCP из PHP (`GelfHandler`) | 4/10 | UDP молча теряет логи КПП при офлайне; TCP даёт таймаут прямо во впуске гостя; нет disk-буфера. Годен только для онлайн-Backend. |
| AMQP через staging-RabbitMQ | 3/10 | Смешивает объёмный лог-поток с **критичным приёмом qr-заказов** на одном зажатом брокере (watermark 256MiB / disk_free 1GB → блокировка публикаторов = встанут продажи). Синхронный AMQP в sync-Baza блокирует впуск. |
| docker `gelf` log-driver | 3/10 | Конфликтует с уже настроенной `json-file` ротацией (один глобальный драйвер); шлёт сырой stdout **без маскировки** (утечка ПДн); не default-OFF (правка daemon.json + restart). |

---

## 3. Схема потока логов

```
┌─────────────────────────┐         ┌──────────────────────────┐
│ Backend (org, online)   │         │ Baza (КПП, offline-first)│
│                         │         │  QUEUE=sync, нет воркера  │
│ Monolog → JSON-каналы:  │         │ Monolog → JSON-каналы:    │
│  • structured.log       │         │  • structured.log         │
│  • mail_delivery.log    │         │   (события baza.*)        │
│  • baza_delivery.log    │         │                          │
│  • qr_pipeline.log      │         │  [MaskPiiProcessor ПДн]   │
│  • qr_access.log        │         │                          │
│  [MaskPiiProcessor ПДн] │         │ запись ВСЕГДА локальна    │
└───────────┬─────────────┘         └────────────┬─────────────┘
            │ файлы на диске                      │ файлы на диске
            ▼                                     ▼
   ┌─────────────────┐                  ┌─────────────────────┐
   │ Шиппер (Vector/ │                  │ Шиппер на узле КПП   │
   │ Fluent Bit)     │                  │ disk-буфер + retry   │
   │ disk-буфер+retry│                  │ досыл при появлении  │
   └────────┬────────┘                  │ сети (сотовая)       │
            │ GELF HTTP/TCP             └──────────┬──────────┘
            │                                      │ GELF HTTP/TCP
            ▼                                      ▼
        ┌──────────────────────────────────────────────┐
        │ Graylog (отдельный RU-VPS, НЕ staging)        │
        │  Graylog + MongoDB + OpenSearch (JVM ~4GB+)   │
        │  GELF input :12201 → streams / extractors     │
        └──────────────────────────────────────────────┘

  Sentry (облако) — ПАРАЛЛЕЛЬНО, для алертинга по исключениям. Не дублировать.
```

**Ключевой принцип офлайн-Baza:** приложение пишет только в локальный файл (работает всегда), сеть в Graylog — забота шиппера. Никакого синхронного GELF из PHP в Baza.

---

## 4. Фундамент default-OFF (вливаем СЕЙЧАС, без Graylog-сервера)

Всё ниже — аддитивно, поведение без флагов не меняется.

### 4.1 Backend `config/logging.php`

Починить рассинхрон `stack`↔`LOG_STACK` (сейчас `stack` захардкожен на `['single']`, а `.env.example` задаёт `LOG_STACK=single,sentry_logs`):

```php
'stack' => [
    'driver' => 'stack',
    'channels' => explode(',', env('LOG_STACK', 'single')),
    'ignore_exceptions' => false,
],
```

Добавить единый структурный сток (источник для шиппера) — по образцу существующих `mail_delivery`/`baza_delivery`:

```php
// Единый JSON-сток для ошибок и audit-событий (TD-10). Источник для шиппера → Graylog.
'structured' => [
    'driver'            => 'daily',
    'path'              => storage_path('logs/structured.log'),
    'level'             => env('LOG_LEVEL', 'info'),
    'days'              => 14,
    'formatter'         => \Monolog\Formatter\JsonFormatter::class,
    'processors'        => [\App\Logging\MaskPiiProcessor::class],
    'ignore_exceptions' => true,
],
```

Опциональный прямой GELF-канал (резерв ТОЛЬКО для онлайн-Backend; не подмешан в стек, активен лишь при `GRAYLOG_HOST`; требует `composer require graylog2/gelf-php` при активации):

```php
// default OFF: пока GRAYLOG_HOST пуст — канал не используется.
'gelf' => [
    'driver'      => 'monolog',
    'handler'     => \Monolog\Handler\GelfHandler::class,
    'formatter'   => \Monolog\Formatter\GelfMessageFormatter::class,
    'processors'  => [\App\Logging\MaskPiiProcessor::class],
    'handler_with' => [
        'publisher' => new \Gelf\Publisher(/* транспорт по env GRAYLOG_* */),
    ],
],
```

### 4.2 Backend — гигиена ПДн (Фаза B, предусловие)

- `config/sentry.php`: `breadcrumbs.sql_bindings => env('SENTRY_SQL_BINDINGS', false)` (было `true`).
- Новый `Backend/src/Shared/Application/Support/LogSanitizer.php` — `maskEmail` (вынести из `MailDeliveryLog`), `maskPhone`, `maskTelegram` + blocklist ключей (`card_number`, `password`, `child`, `search_blob`, …).
- Новый `Backend/app/Logging/MaskPiiProcessor.php` — Monolog-процессор, прогоняющий `message`/`context` через `LogSanitizer`. Подключается **только** к `structured`/`gelf`, не к `laravel.log`.
- `app/Exceptions/Handler.php` — `reportable`, пишущий в канал `structured` структурную запись `{error_code, exception_class, http_status, route, controller, trace_short}` **без** сырого `getMessage()`.
- `OrderTickets.php` (3 места) — убрать `file`/`line`/сырой `getMessage()` из JSON-ответов (TD-29), отдавать `{success:false, code, message}` из каталога кодов.

### 4.3 Baza `config/logging.php`

Аддитивно — канал `structured` (события `baza.*` сейчас идут в `single`/LineFormatter):

```php
'structured' => [
    'driver'            => 'daily',
    'path'              => storage_path('logs/structured.log'),
    'level'             => env('LOG_LEVEL', 'info'),
    'days'              => 14,
    'formatter'         => \Monolog\Formatter\JsonFormatter::class,
    'ignore_exceptions' => true,
],
```

**Прямой GELF-канал в Baza НЕ добавляем** (sync-режим → блокировка впуска). `LOG_LEVEL=info` на боевом КПП (не `debug` — раздувает офлайн-буфер). `daily` вместо `single` — app-level ротация против переполнения диска офлайн-узла.

### 4.4 env (примеры, всё закомментировано/выключено)

```dotenv
# --- Логирование (Backend) ---
LOG_CHANNEL=stack
LOG_STACK=single          # явный состав стека; добавить ',structured' / ',gelf' при активации
LOG_LEVEL=info
SENTRY_SQL_BINDINGS=false  # 152-ФЗ: не писать значения SQL-параметров

# --- Graylog (default OFF — заполнить при активации) ---
# GRAYLOG_HOST=
# GRAYLOG_PORT=12201
# GRAYLOG_PROTOCOL=udp     # для онлайн-Backend; Baza — только файл+шиппер
```

| Изменение | Файл | default OFF |
|-----------|------|:-----------:|
| `stack` читает `LOG_STACK` | `Backend/config/logging.php` | ✅ |
| Канал `structured` | `Backend/config/logging.php` | ✅ |
| Канал `gelf` (опц.) | `Backend/config/logging.php` | ✅ |
| `sql_bindings=false` | `Backend/config/sentry.php` | ✅ |
| `LogSanitizer` + `MaskPiiProcessor` | новые файлы | ✅ |
| `reportable` → structured | `Backend/app/Exceptions/Handler.php` | ✅ |
| убрать `file`/`line` из ответов | `OrderTickets.php` | ✅ |
| Канал `structured` | `Baza/config/logging.php` | ✅ |
| env-блок Graylog | `*/.env.example` | ✅ |

---

## 5. Правила 152-ФЗ (что НЕ слать / как маскировать)

### НЕЛЬЗЯ слать в централизованный лог

| Категория | Поля / источник | Действие |
|-----------|-----------------|----------|
| **SQL bindings** | `sentry.php breadcrumbs.sql_bindings` (email/телефон/ФИО/имена детей/госномера/`card_number`/хэши паролей) | `=false`. Не покрывается `send_default_pii=false` — отдельный флаг. |
| **PCI** | `payment.method_details.card_number` | **НИКОГДА**, blocklist по ключу. |
| **Тела запросов** | `order/create`, `qrOrder/create` (`guests[].child{}`, `buyer.fio`), `questionnaire/send` (phone/email/telegram/vk/agy/childName/childAge/allergy/parentInfo/trustedPhone/contact) | не логировать payload. |
| **rich-поиск** | `search_blob` / `ticket_search` (fio, phone, telegram, email, car_number, child_name, parent_phone, external_order_no) | не слать. |
| **Колонки-ПДн** | `baza_deliveries.name/email`, `email_messages.recipient` | только маскированно. |
| **Сырые исключения** | `getMessage()`/`getFile()`/`getLine()` | логировать `error_code` + класс + http_status + route, не сырой текст. |
| **Текст комментария заказа** | тред комментариев | образец `OrderCommentAddedEvent`: только `source/length/has_text`. |

### Маскировка (единый `LogSanitizer`, подключён к `structured`/`gelf`)

- email → `i***@mail.ru` (есть `maskEmail` в `MailDeliveryLog`, вынести в общий хелпер)
- phone → `+7***85` (добавить `maskPhone`)
- telegram → `@iv***` (добавить `maskTelegram`)

### МОЖНО слать (структурно, без ПДн)

- События `domain_history` (PII-минимизированы by design): `aggregate_type`, `event_name`, `actor_type` (user/system/artisan/auto_payment/qr/baza), `actor_id`, `occurred_at`, безопасный `payload`.
- `error_code` + класс исключения + http_status + route/controller + укороченный trace.
- Маскированные идентификаторы; воронка покупки по `order_id`/`qr_order_id` без ФИО/контактов.

### Резидентность

Graylog-сервер с ПДн-логами **обязан быть в РФ**. Сервер вне РФ — самостоятельный блокер (TD-7, регистрация оператора ПДн в РКН не закрыта).

---

## 6. Шаги активации (когда появится Graylog-сервер)

1. Согласовать **Graylog vs Loki+Grafana** (см. §8) — явный go.
2. Поднять стек **Graylog + MongoDB + OpenSearch** (JVM ~4GB+) на **отдельном RU-VPS** (~4vCPU/8GB/160GB). **НЕ на staging** (2vCPU/1.9GB/своп → OOM). Сервер в РФ.
3. Создать **GELF input** в Graylog: HTTP/TCP (рекомендуется) на :12201; UDP — только если допустима потеря при пиках.
4. Установить **шиппер** (Vector / Fluent Bit / Filebeat) на каждый хост-источник (Backend org + узлы Baza КПП): tail `structured.log` + `mail_delivery.log` + `baza_delivery.log` + `qr_pipeline.log` + `qr_access.log`; GELF/HTTP output; **persistent disk buffer + retry**.
5. Убедиться, что **Фаза B активна** на проде: `SENTRY_SQL_BINDINGS=false`, `MaskPiiProcessor` подключён, `file`/`line` убраны из ответов.
6. (Только при выборе прямого GELF для онлайн-Backend) `composer require graylog2/gelf-php`, заполнить `GRAYLOG_*`, добавить `gelf` в `LOG_STACK`. **Baza — только файл+шиппер.**
7. Завести в Graylog streams/extractors/alerts по `error_code`, `actor_type`, `event_name`. Алертинг по исключениям оставить в Sentry (не дублировать).
8. Проверить офлайн-сценарий Baza: отключить сеть на КПП → впуск пишется локально и НЕ тормозит; вернуть сеть → шиппер досылает накопленное без потери.

---

## 7. Связь с Фазой B / TD-10 / планом мониторинга

- **Фаза B (бэкенд-гигиена)** — предусловие безопасного Graylog: без `sql_bindings=false`, единого `ErrorResponse`/каталога кодов и маскировки `phone`/`telegram` в любой централизованный сток потечёт ПДн. Каталог кодов даёт стабильное поле `error_code` для стримов/алертов (лечит боль TD-5 «несгруппируемый шум»).
- **TD-10 (audit + воронка)** — это «контент» для Graylog. Реализуется поверх `domain_history` (структурный `actor_type`/`event_name`) + отдельный audit-канал (`structured`). Слать первыми именно audit-события (by-design без ПДн), а не дубль исключений.
- **План мониторинга (roadmap v2.9.0)** — фиксирует **Loki+Promtail+Grafana** на отдельном RU-VPS. Graylog — **прямая альтернатива** (другой стек хранения/UI), а не дополнение → переигрывание решения, нужен явный go. Фундамент §4 (JSON-файлы + маскировка + шиппер) совместим с обоими — выбор хранилища можно отложить.

---

## 8. Открытые вопросы для владельца

| # | Вопрос | Варианты | Предлагаемый дефолт |
|---|--------|----------|---------------------|
| 1 | Graylog или ранее запланированный Loki+Grafana? | Graylog (мощнее, тяжелее) / Loki+Grafana (в roadmap, легче) / отложить выбор хранилища | **Loki+Grafana** (уже выбран; Graylog — переигрывание). Фундамент совместим с обоими. |
| 2 | Транспорт логов? | JSON-файл+шиппер (disk-буфер) / прямой GELF UDP-TCP / AMQP через RabbitMQ | **JSON-файл+шиппер** (единственный безопасный для офлайн-Baza, не трогает брокер). |
| 3 | Где разместить тяжёлый стек? | Отдельный RU-VPS ~4vCPU/8GB / staging / облако | **Отдельный RU-VPS**. На staging нельзя (OOM). Облако вне РФ — блокер по 152-ФЗ. |
| 4 | Что слать первым? | Audit-события TD-10 (без ПДн) / ошибки (дубль Sentry) / инфра-логи | **Audit-события TD-10 + воронка** (бизнес-ценность, by-design без ПДн). |
| 5 | Sentry параллельно Graylog? | Оставить Sentry (исключения) + Graylog (аудит) / всё в Graylog / только Sentry | **Оставить оба** (не дублировать алертинг). `SentryEventBus` в Baza — мёртвый код, удалить. |

---

## 9. Реализовано (Фаза B, 2026-06-24) — фундамент default-OFF

Влито на ветке `feat/festival-service`. **Поведение по умолчанию не меняется**: новый канал не подмешан в дефолтный стек, пишется только явными вызовами `Log::channel('structured')`. Решение владельца: «писать в файл сейчас, подключение Grafana/Loki — потом».

| Что | Файл | Деталь |
|-----|------|--------|
| Канал `structured` (JSON, daily 14д, `ignore_exceptions`) | `Backend/config/logging.php`, `Baza/config/logging.php` | Единый сток для шиппера: `storage/logs/structured.log`. Backend — с tap-маскировкой; Baza — JSON-канал готов (наполнение `baza.*` событий — следующий шаг). |
| Маскировка ПДн | `Shared/Infrastructure/Logging/LogSanitizer.php` (новый) + `Backend/app/Logging/MaskPiiProcessor.php` + `MaskPiiTap.php` | `maskEmail/maskPhone/maskTelegram` + blocklist (`card_number`/`password`/`x-qr-token`/`sql_bindings`/`child`/`search_blob`…) + `maskText` свободного текста. Процессор навешан tap'ом на `structured`. |
| Sentry `sql_bindings` → OFF | `Backend/config/sentry.php` | `env('SENTRY_SQL_BINDINGS', false)` (было `true`) — значения SQL-параметров с ПДн больше не пишутся в breadcrumbs. |
| Структурный лог исключений | `Backend/app/Exceptions/Handler.php` | 2-й `reportable` → `structured`: `error_code`/класс/HTTP-статус/маршрут, БЕЗ сырого `getMessage()`. |
| Единый `ErrorResponse` + `ErrorCode` | `Backend/app/Http/Responses/` (новые) | `{success:false, code, message}` без `file`/`line`; полная диагностика — в `structured` (маскированно). |
| Чистка утечки TD-29 | `Backend/app/Http/Controllers/TicketsOrder/OrderTickets.php` | 3 catch-блока `file`/`link`/`getMessage` → `ErrorResponse::fromThrowable`. |
| `.env.example` | `Backend/.env.example`, `Baza/.env.example` | Блок Graylog (закомментирован), `SENTRY_SQL_BINDINGS=false`, убран мёртвый дубль `LOG_CHANNEL`/`LOG_STACK`. |

**Осознанное отклонение от §4 плана:** глобальный `stack` НЕ переведён на чтение `LOG_STACK` (в `.env.example` была мина `LOG_STACK=single,sentry_logs` — при включении чтения все логи внезапно пошли бы в Sentry Logs). Вместо этого важные события пишутся в `structured` **явно** → нулевой риск поведения. Рассинхрон `LOG_STACK` оставлен как задокументированная заметка.

**Проверка:** PHPUnit Backend 520/520 + Baza 212/212; новые тесты `LogSanitizerTest`/`ErrorResponseTest`/`SentrySqlBindingsTest` (10 тестов / 38 проверок); e2e-запись в `structured` подтвердила маскировку ПДн (email/телефон/telegram/`card_number`/вложенный `password`) в JSON.

**Остаётся до фактического подключения** (см. §6): Graylog/Loki на отдельном RU-сервере + шиппер; решить Graylog vs Loki+Grafana; завести `baza.*` события в `structured`.

---

## История изменений документа

| Дата | Изменение |
|------|-----------|
| 2026-06-24 | Создан документ. Сведены результаты аудита логирования (Backend/Baza/инфра/152-ФЗ) и скоринг 4 транспортов. Выбран JSON-файл+шиппер. Описан фундамент default-OFF, правила 152-ФЗ, шаги активации, открытые вопросы. |
| 2026-06-24 | Реализован фундамент default-OFF (Фаза B): канал `structured` + маскировка ПДн (`LogSanitizer`/`MaskPiiProcessor`) + `sql_bindings` off + `ErrorResponse`/`ErrorCode` + чистка TD-29 + Baza-канал. Тесты 520+212 зелёные. |
