# Система шаблонов (AF-3) — единый редактор писем и PDF-билетов

> Статус: **спека дизайна** (2026-06-15). Источник: дизайн-воркфлоу `template-system-design` (8 агентов) + решения владельца.
> Контекст: org → admin-only система (создание билетов + контроль доставки). Рендер писем и PDF **остаётся на org**. Цель: admin меняет оформление писем и PDF-билетов **без правки кода и деплоя**.

## 1. Принятые решения (владелец, 2026-06-15)

| Вопрос | Решение |
|--------|---------|
| Подход | **A + C гибрид**: UX код-редактора с живым предпросмотром (A) на безопасном движке Mustache + MJML (C). Визуальный блочный конструктор (B) — **отвергнут** (не выразит кастомную PDF-вёрстку под DomPDF). |
| Движок | **Mustache.php** (`mustache/mustache`) для писем И PDF + **MJML** (опционально) для адаптивных писем. **Одобрено.** |
| Версии/откат | **В MVP** (снапшот при публикации + откат — страховка перед фестивалём). |
| Миграция ~36 blade | **По одному, с fallback на blade** (нулевой риск перед продажами; blade — safety net ≥1 фестиваль). |
| Кто редактирует | **Нетехнический админ** → инвестируем в «леса» редактора: подсветка кода (CodeMirror), **палитра готовых блоков-сниппетов** (не только переменных), инлайн-валидация, стартовые шаблоны. |

## 2. Движок и безопасность (критично — без RCE)

**Mustache — logic-less.** В теле шаблона доступны ТОЛЬКО:
- подстановка `{{ var }}` (auto-escape HTML),
- секции/циклы `{{#guests}}…{{/guests}}`,
- инверсия `{{^promocode}}…{{/promocode}}`,
- raw `{{{ qr_url }}}` — **whitelisted** только для data-URI QR.

Нет произвольного PHP, нет `@php`, нет blade-директив, нет вызова функций из шаблона. Даже `<?php system('rm -rf') ?>` выведется как экранированный **текст**. Исполнение кода админом на сервере исключено **архитектурно** — в отличие от `Blade::render($userInput)` (компилирует строку в PHP → прямой RCE).

Контраст с Twig-sandbox (подход A): тоже безопасен, но через настраиваемую `SecurityPolicy` (whitelist тегов/фильтров/методов) — ошибка конфига = дыра. У Mustache **нечего конфигурировать**.

Дополнительно:
- **XSS:** `{{ x }}` экранирует HTML; `{{{ raw }}}` запрещён линтером кроме whitelist (`qr_url`).
- **MJML** компилируется на **сохранении** (не на запросе рендера) через существующий Node-контейнер (`node-admin`), результат кэшируется в `compiled_html`. В рантайме воркера Node не нужен. Node не видит данных заказа — компилирует только разметку. Нет Node → fallback `engine=html`.
- **DomPDF:** `enable_php=false` в `config/dompdf.php` — PHP не исполняется и на уровне PDF.
- **Доступ:** все роуты `auth:api + admin`.
- **Preview:** рендерит ТОЛЬКО фикстуры (`PlaceholderCatalog::sample()`), без ПДн реальных заказов.

**Зависимости:** обязательная `mustache/mustache` (composer, ~0 транзитивных). MJML — через Node-контейнер (не PHP-пакет). CodeMirror на фронте — Фаза 4.

## 3. Модель БД

```sql
CREATE TABLE templates (
  id            CHAR(36) PRIMARY KEY,
  slug          VARCHAR(120) NOT NULL,   -- = текущему имени blade ('pdf','orderToPaid','TypeTicketPdf1',...)
  kind          ENUM('email','pdf') NOT NULL,
  engine        ENUM('html','mjml') NOT NULL DEFAULT 'html',  -- mjml только для email
  title         VARCHAR(255) NOT NULL,
  body          MEDIUMTEXT NOT NULL,     -- опубликованный исходник
  draft_body    MEDIUMTEXT NULL,         -- черновик (не в проде)
  compiled_html MEDIUMTEXT NULL,         -- кэш скомпилированного MJML (для html = body)
  active        TINYINT(1) NOT NULL DEFAULT 1,
  is_system     TINYINT(1) NOT NULL DEFAULT 0,
  created_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_slug_kind (slug, kind)
);
CREATE TABLE template_versions (
  id CHAR(36) PRIMARY KEY, template_id CHAR(36), body MEDIUMTEXT,
  comment VARCHAR(255) NULL, author_id CHAR(36) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);
```

**`slug` = имени blade → нулевая миграция привязки.** Существующие колонки-селекторы (`ticket_type_festival.pdf`/`.email`, `locations.email_template`/`pdf_template`, `types_of_payment.email`) уже указывают на нужную запись — их НЕ трогаем. `body`/`compiled_html` — обычные строковые колонки (не JSON, без кастов, правило единого формата). Timestamps — DB DEFAULT, не задавать в PHP.

## 4. Точки интеграции (2 точки, fallback на blade)

**PDF — `CreatingQrCodeService::createPdf()`** (`Backend/src/Ticket/CreateTickets/Services/`):
```php
$slug = $dataInfoForPdf->getFestivalView() ?? 'pdf';
$tpl = $this->templateRepo->findActive($slug, TemplateKind::PDF);
if ($tpl !== null) {
    $html = $this->renderer->render($tpl->getBody(), $this->varsAssembler->forPdf($dataInfoForPdf));
    return Pdf::loadHTML($html);
}
return Pdf::loadView($slug, [...]); // FALLBACK на blade-файл
```

**Email — `Mailable::build()`** (трейт `RendersDbTemplate` на `OrderToPaid`/`OrderToPaidFriendly`/`OrderListApproved` + хардкод-письма):
```php
$tpl = app(TemplateRepositoryInterface::class)->findActive($emailView ?: 'orderToPaid', TemplateKind::EMAIL);
return $tpl !== null
    ? $this->html($renderer->render($tpl->getBody(), $vars))   // из БД
    : $this->view('email.'.$emailView, $vars);                  // FALLBACK на blade
```

Оба канала (**legacy `order_tickets`** + **qr-flow** `QrOrder/Application/Step/Send*EmailStep`) используют ОДНИ Mailable/CreatingQrCodeService → переключение в этих 2 местах покрывает оба автоматически.

**Бонус:** хрупкий хардкод `$emailView === 'TypeTicketMailOrderToPaidChild'` в `OrderToPaid::build()` удаляется → запись templates + секция `{{#questionnaireLinks}}`.

## 5. Каталог переменных (`PlaceholderCatalog`)

Единственный источник истины (per `kind`+`slug`), данные из `TicketResponse` + `QrOrderDto.payload`. Группы:
- `order.*` — email, phone, city, total_price, comment, status…
- `guest.*` — **ЦИКЛ** `{{#guests}}`; внутри `guest.name`, `guest.email`, `guest.number`, `guest.options[]` (**вложенный цикл**)
- `ticket.*` — `ticket.number` (kilter→`E-{kilter}`), raw `{{{ ticket.qr_url }}}` (data-URI)
- `festival.*` — title, date, place…
- `qr.*` — admin-only поля qr-заказа
- `location.*` / `curator.*` — **только для списков** (list)

`TemplateVarsAssembler` расширяет нынешние 4 PDF-переменные (url/name/email/kilter) до полного набора. Неизвестный плейсхолдер → пустая строка (Mustache не падает), preview подсветит «нет в контексте».

## 6. Live-preview — серверный эндпоинт (CQRS)

`POST /api/v1/template/preview` (admin) → `PreviewTemplateQueryHandler`: подставляет фикстуру `PlaceholderCatalog::sample(kind, slug)` (демо-гость, фиктивный QR data-URI), рендерит через `TemplateRenderer`.
- `kind=email` → `{ html }` → фронт в `<iframe sandbox srcdoc>`.
- `kind=pdf` → прогон через **ТОТ ЖЕ DomPDF** (`Pdf::loadHTML(...)->output()`) → `application/pdf` → фронт в `<iframe>`/blob-URL. (Клиентский preview PDF невозможен — DomPDF рендерит CSS 2.1 иначе, чем браузер.)
- Ошибка рендера/синтаксиса → **422** с понятным сообщением (не 500).

## 7. Эндпоинты (все `auth:api + admin`, БД только в репозитории)

`POST /template/getList`, `GET /template/getItem/{id}`, `POST /template/save/{id?}` (черновик/публикация + снапшот в versions), `POST /template/activate/{id}`, `POST /template/preview`, `GET /template/versions/{id}`, `POST /template/rollback/{id}/{versionId}`, `GET /template/variables/{slug}` (палитра).

## 8. UX (AdminFront, Vite + PrimeVue Sakai)

**Список `/admin/templates`** — DataTable (server-side, Vuex `appTemplate`, по образцу QrOrderModule): `title`, `kind` (тег email/pdf), `engine`, `slug`/привязка, `active`, `updated_at`. Фильтр по kind. Кнопка «Создать».

**Редактор `/admin/templates/:id`** — PrimeVue `Splitter`, 2 панели:
- **Слева (исходник):** `title`, `kind` (disabled при edit), `engine` (html/mjml), привязка. Поле кода. **Под нетехнического админа (решение владельца):** CodeMirror 6 (подсветка HTML/Mustache, парность скобок) + инлайн-валидация (баланс секций, только whitelisted плейсхолдеры, предупреждение на `{{{ raw }}}`) + стартовые шаблоны при создании.
- **Справа (палитра + превью):**
  - **Палитра переменных** — показывает только валидные для slug; клик вставляет `{{ order.email }}` в курсор (важно при дислексии — не печатать). Циклы вставляются парой.
  - **Палитра готовых блоков-сниппетов** (для нетехнического) — «шапка с лого», «блок билета + QR», «таблица гостей», «футер» вставляются как готовые куски кода.
  - «Превью» + выбор тестового набора (regular / friendly / детский / список). Письмо → iframe; PDF → реальный DomPDF.

**Тулбар:** Сохранить черновик / Опубликовать (mjml → компиляция, статус) / Превью / Активировать. «Сохранить» неактивна при пустом body/невалидном engine.

**Флоу:** открыть → править (вставка из палитр) → выбрать тестовый набор → Превью (на демо-данных, отражает несохранённый body) → «Сохранить черновик» (прод не затронут) → «Опубликовать» (`body ← draft_body` + снапшот в `template_versions`). Следующее реальное письмо/билет рендерится новым телом **без деплоя**.

**Версии/откат (MVP):** вкладка «История» — список версий (дата/автор/комментарий), превью любой версии, «Откатить» = новая версия из снапшота (без потери истории).

**Привязка:** прозрачна — шаблон `orderToPaid` применяется ко всем оплатам; переопределение под тип билета/фестиваль — через slug в `ticket_type_festival.pdf/.email` (дропдаун из getList); для списков — slug в `locations.*_template`.

## 9. Структура модуля `Backend/src/Template/`

Пассивная сущность (образец `Location`/`QrOrder`), без AggregateRoot/Domain Events. Расширяем существующий модуль (там сейчас `TemplateService`).

```
src/Template/
├── Application/
│   ├── TemplateApplication.php          # тонкий фасад (как LocationApplication)
│   ├── Create/ Edit/ Delete/ Activate/  # Command + Handler (CommandBus)
│   ├── GetList/ GetItem/                # Query + Handler (QueryBus)
│   └── Preview/                         # PreviewTemplateQuery + Handler
├── Domain/
│   ├── TemplateKind.php                 # Enum email|pdf
│   ├── TemplateEngine.php               # Enum html|mjml
│   └── PlaceholderCatalog.php           # контракт плейсхолдеров per (kind, slug) + sample()
├── Dto/ TemplateDto.php, TemplateVersionDto.php
├── Repositories/
│   ├── TemplateRepositoryInterface.php
│   └── InMemoryMySqlTemplateRepository.php   # БД ТОЛЬКО здесь
├── Responses/ TemplateItemForListResponse.php  # без body (body только в getItem)
└── Service/
    ├── TemplateService.php (existing — fallback/импорт)
    ├── TemplateRenderer.php             # Mustache::render(body, context)
    ├── TemplateVarsAssembler.php        # TicketResponse/QrOrderDto → переменные
    └── MjmlCompiler.php                 # MJML→HTML через Node на сохранении
```
Bind в `TicketsProvider`: `TemplateRepositoryInterface → InMemoryMySqlTemplateRepository`.

## 10. Поэтапный план

| Фаза | Содержимое | Оценка |
|------|-----------|--------|
| **1. Сущность Template + PDF из БД с fallback** (MVP) | Миграции `templates`+`template_versions`. Модуль Template (DTO, Repo+Interface, Application CRUD, контроллер, роуты admin). `mustache/mustache`. `TemplateRenderer`. Переключение `CreatingQrCodeService::createPdf` на `loadHTML` с fallback на `loadView`. Artisan-импорт blade в templates (slug=имя файла, `updateOrCreate`). PHPUnit: рендер + безопасность (`<?php` не исполняется). **Результат:** PDF-slug `pdf` рендерится из БД, проверен на staging — admin меняет PDF-билет без деплоя. | M (~3-5д) |
| **2. Письма из БД + переменные** | Трейт `RendersDbTemplate`. `OrderToPaid`/`Friendly`/`OrderListApproved` → рендер из БД с fallback. Оба канала (legacy + qr Step). `TemplateVarsAssembler` + `PlaceholderCatalog` per-slug. Удаление хардкода детского билета. PHPUnit на контракт переменных. | M (~3-4д) |
| **3. Preview-эндпоинт + версии/откат** | `POST /template/preview` (email HTML + pdf через DomPDF) на фикстурах. `draft_body`/publish + снапшот в `template_versions`. `/versions` + `/rollback`. `/variables/{slug}`. Троттлинг preview. | M (~2-3д) |
| **4. AdminFront — экран редактора** | Vuex `appTemplate` (1:1 QrOrderModule). `TemplateListView.vue` (DataTable). `TemplateEditorView.vue` (Splitter + **CodeMirror** + палитра переменных/блоков + iframe-preview + тестовые наборы + валидация — под нетехнического админа). Роут role:['admin'] + меню. | M-L (~5-7д) |
| **5. Миграция шаблонов + MJML + регресс** | Конвертация ~36 blade в Mustache **по одному** (`{{$x}}`→`{{ x }}`, `@if`→`{{#}}`, `@foreach`→`{{#guests}}`), активация после сверки. `MjmlCompiler` через Node + кэш `compiled_html`. Регресс на staging: Mailpit (письма) + DomPDF-превью (PDF) по каждому. blade — safety net ≥1 фестиваль. | M-L (дробится) |

## 11. Связанное

- TECH_DEBT: AF-3 (генератор шаблонов), AF-6 (email-доставка — параллельно).
- Память: [[project_admin_ui_primevue]], [[project_qr_pivot_2026_06_13]].
- Не путать с **AF-6** (подтверждение доставки письма) — это про трекинг доставки, а не про оформление.
