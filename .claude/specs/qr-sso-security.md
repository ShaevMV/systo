---
title: SSO для qr.spaceofjoy.ru через Laravel Passport — Security Spec
status: draft
created: 2026-05-30
author: security-engineer
related:
  - .claude/meetings/2026-05-30/RESULTS.md  # источник скоупа v2.6.0
  - .claude/docs/BUSINESS_RULES.md  # §7 роли, §9 JWT, §11 лимиты
  - .claude/docs/API.md  # middleware, публичные/защищённые эндпоинты
  - .claude/docs/TECH_DEBT.md  # TD-7 152-ФЗ, TD-10 audit-логи
target_release: v2.6.0
deadline: 2026-06-12
size_estimate: L (5 sub-веток внутри feat/v2.6.0-fall-festival)
---

# Спецификация: SSO для qr.spaceofjoy.ru через Laravel Passport

> Документ — security-черновик для tech-lead. Не готов к разработке — требует решения по §11 (open questions) и §2 (сосуществование JWT/Passport).
> Все рекомендации основаны на OWASP ASVS 4.0.3, RFC 6749 (OAuth2), RFC 7636 (PKCE), RFC 8252 (OAuth2 for Native Apps).

---

## 1. Архитектура SSO

### 1.1. Grant type

**Решение: Authorization Code Grant + PKCE (RFC 7636).**

Обоснование:
- `qr.spaceofjoy.ru` — публичный SPA-клиент (Vue/JS), client_secret хранить негде → нужен PKCE
- `Implicit grant` — устарел (OAuth 2.1 deprecated), запрещён OWASP
- `Password grant` — запрещён (пользователь не должен отдавать пароль клиенту, даже доверенному)
- `Client credentials grant` — отдельно для service-to-service вызовов (см. §1.4)

### 1.2. Где живёт сессия

| Хранилище | Где | Что лежит | Срок |
|-----------|-----|-----------|------|
| **Backend session** (cookie) | `tickets.spaceofjoy.ru` | Laravel session ID (consent screen) | 2 часа |
| **Access token** | qr.spaceofjoy.ru, **httpOnly secure cookie** на домене `qr.spaceofjoy.ru` | OAuth2 access token | 60 мин |
| **Refresh token** | qr.spaceofjoy.ru, **httpOnly secure cookie**, отдельный path `/auth/refresh` | OAuth2 refresh token | 14 дней |

**Запрещено:** хранить access/refresh token в `localStorage` или `sessionStorage` — это даёт XSS-эксфильтрацию. См. OWASP Cheat Sheet «JWT for Java».

**Cookies настройки (для access/refresh):**
```
SameSite=Lax  (Strict ломает редирект-flow из tickets.spaceofjoy.ru)
Secure=true   (только HTTPS)
HttpOnly=true (недоступен из JS)
Domain=qr.spaceofjoy.ru (не общий .spaceofjoy.ru — изоляция от других субдоменов)
```

### 1.3. Как qr.spaceofjoy.ru проверяет валидность токена

**Решение: JWT-verify локально + introspection при подозрении.**

- Passport умеет выпускать access tokens **в JWT-формате** (`Passport::useTokenForBlade()` + `Passport::personalAccessTokensExpireIn()`). По умолчанию — opaque tokens (записи в `oauth_access_tokens`).
- **Опция А:** opaque tokens + introspection endpoint `POST /oauth/token/introspect` (RFC 7662) — каждый запрос с qr на Backend для проверки → 1 лишний HTTP-роundtrip → нагрузка.
- **Опция B (рекомендуется):** JWT access tokens, qr проверяет подпись локально через публичный ключ `oauth-public.key`. Запросы к Backend — только когда qr хочет вызвать наш API.

**Trade-off:** JWT нельзя сразу отозвать (живёт до `exp`). Митигация — короткий TTL (60 мин) + revoke списка `oauth_access_tokens.revoked = 1` для повышенных рисков (logout всех сессий, смена пароля). При следующем обращении к Backend API — проверка revoked-флага.

### 1.4. Service-to-service (Backend ↔ qr.spaceofjoy.ru)

Для серверных вызовов **с qr-сервера** (например, qr хочет создать заказ от имени системы):

- **Client Credentials Grant** — отдельный OAuth2 client с client_secret в `.env` qr-сервера
- Scope: `qr-service:*` (см. §5)
- Не имеет `user_id` — actor в audit-логе = `qr_service` (новый ActorType)

### 1.5. Single Sign-Out

**Сценарии:**
1. Пользователь нажал «Выйти» в `tickets.spaceofjoy.ru` (наш фронт) → должен разлогиниться и в qr
2. Пользователь нажал «Выйти» в qr.spaceofjoy.ru → должен ли разлогиниваться в `tickets.spaceofjoy.ru`?
3. Админ забанил аккаунт → во всех клиентах

**Решение:**
- `POST /oauth/logout` (наш эндпоинт, не из коробки Passport) — принимает access token, ищет все `oauth_access_tokens` пользователя, ставит `revoked = 1`, удаляет `oauth_refresh_tokens`.
- На qr.spaceofjoy.ru — clear cookies + редирект на `/oauth/logout?redirect=...`.
- **Front-channel logout (OIDC):** для будущего, если появятся другие клиенты. Сейчас не нужно.
- **Back-channel logout:** не реализуем (нужен webhook от Backend в qr — overkill для двух сервисов).

**Внимание:** JWT access token не может быть отозван «мгновенно». В худшем случае — пользователь работает до конца TTL (60 мин). Для критичных кейсов (бан пользователя) — нужен `revoked`-чек на каждый защищённый Backend-эндпоинт.

---

## 2. Сосуществование JWT и Passport

### Текущее состояние

- Backend использует `php-open-source-saver/jwt-auth` (HS256, TTL 60 мин, refresh 14 дней) — для всех своих эндпоинтов
- В `composer.json` уже есть `laravel/sanctum: ^3.0.1` — установлен, но **не используется**
- guard `api` = jwt

### Опции

| Опция | Описание | Pros | Cons |
|-------|----------|------|------|
| **A. Hybrid (JWT + Passport)** | JWT для текущих эндпоинтов и фронта `tickets.spaceofjoy.ru`. Passport только для OAuth2-сценариев qr.spaceofjoy.ru. Два guard'а: `api` (jwt) + `api-oauth` (passport) | Минимум изменений в существующем коде. Нет регресса на проде. Изоляция | Два механизма одновременно, два refresh-flow, две точки revoke. Сложность для junior-разработчика |
| **B. Full migration на Passport** | Полная замена jwt-auth на Passport. Все эндпоинты — через oauth_access_tokens | Единый механизм. Native OAuth2 везде. Из коробки revocation | Требует миграции всех существующих токенов (или принудительный re-login). Frontend (`tickets.spaceofjoy.ru`) переписать. Риск регресса на проде до фестиваля **за 2 недели до дедлайна** |
| **C. Passport + Personal Access Tokens** | Passport везде, для qr — Authorization Code, для `tickets.spaceofjoy.ru` — Password Grant или Personal Access Tokens | Единый стек | Password Grant запрещён OAuth 2.1. Personal Access Tokens — это не SSO, это API-токены |

### Рекомендация

**Опция A (Hybrid)** — для v2.6.0. Обоснование:
- Дедлайн 2026-06-12 — 13 дней. Полная миграция на Passport ломает текущий фронт и риск регресса слишком велик
- Hybrid даёт быстрый путь к SSO без переделки `tickets.spaceofjoy.ru`
- В v2.9.0 (Laravel 11 part 2) можно переоценить: возможно унифицировать на Passport, когда будет окно

**Конфигурация `config/auth.php`:**
```php
'guards' => [
    'api' => [
        'driver' => 'jwt',           // существующее, для tickets.spaceofjoy.ru
        'provider' => 'users',
    ],
    'api-oauth' => [
        'driver' => 'passport',      // новое, для qr.spaceofjoy.ru
        'provider' => 'users',
    ],
],
```

Эндпоинты, доступные через qr — отдельная группа в `routes/api.php` с `middleware('auth:api-oauth')`.

---

## 3. Конфигурация Passport

### 3.1. Установка

```bash
composer require laravel/passport:^11.0   # для Laravel 9
php artisan migrate                        # таблицы oauth_*
php artisan passport:keys --force          # генерация RSA-ключей
php artisan passport:client --client       # client credentials для qr-server
php artisan passport:client --public --redirect_uri=https://qr.spaceofjoy.ru/auth/callback  # public PKCE-client для qr-SPA
```

**Создаётся:**
- 5 таблиц: `oauth_auth_codes`, `oauth_access_tokens`, `oauth_refresh_tokens`, `oauth_clients`, `oauth_personal_access_clients`
- Файлы ключей: `storage/oauth-private.key`, `storage/oauth-public.key`

### 3.2. Какие grants включить

В `AuthServiceProvider::boot()`:

```php
Passport::routes();                        // /oauth/* endpoints

// Authorization Code with PKCE — для qr SPA (основной flow)
Passport::enableImplicitGrant();           // НЕ включать! deprecated

// Client Credentials — для qr-server → Backend (S2S)
// Включён по умолчанию

// Password Grant — НЕ включать (DEPRECATED OAuth 2.1)
// (закомментирован Passport::enablePasswordGrant())

// Personal Access Tokens — для отладочных токенов админа (опционально)
Passport::personalAccessTokensExpireIn(now()->addDays(30));

// Lifetime
Passport::tokensExpireIn(now()->addMinutes(60));       // как сейчас JWT
Passport::refreshTokensExpireIn(now()->addDays(14));   // как сейчас JWT
Passport::personalAccessTokensExpireIn(now()->addDays(30));

// Scopes
Passport::tokensCan([
    'tickets:read'     => 'Просмотр типов билетов и опций',
    'orders:write'     => 'Создание заказов от имени пользователя',
    'orders:read'      => 'Просмотр своих заказов',
    'promocodes:write' => 'Создание промокодов',
    'promocodes:read'  => 'Просмотр промокодов',
    'profile:read'     => 'Просмотр профиля пользователя',
]);

Passport::setDefaultScope(['profile:read']);
```

### 3.3. Ключи

- `storage/oauth-private.key` и `storage/oauth-public.key` — **категорически НЕ коммитить в git**
- В `.gitignore`: `storage/oauth-*.key`
- В деплой-скрипте (см. v2.7.0 CD): копировать с защищённого хранилища (Vault / шифрованный bundle / Yandex Lockbox)
- На локальной разработке — генерировать `passport:keys --force` после `git clone`
- В `.env.example`: указать что нужно сгенерировать ключи

**Альтернатива — хранение в .env:**

Можно через `PASSPORT_PRIVATE_KEY` (multiline в base64). Использовать `Passport::loadKeysFrom(storage_path())` или явное:
```php
Passport::loadKeysFrom(base_path('storage'));
// или
Passport::loadKeysFromEnv(); // подгружает из переменных окружения
```

**Рекомендация:** ключи **в файлах** (`storage/`), но **файлы исключены из git** + копируются deploy-скриптом из защищённого источника. Это упрощает rotation и не превращает `.env` в портянку.

---

## 4. Роль `qr_service`

### 4.1. Где добавляется

`Backend/src/User/Account/Helpers/AccountRoleHelper.php`:
```php
public const qr_service = 'qr_service'; // сервисная роль для qr.spaceofjoy.ru
```

И в `isValid()` — добавить в массив.

### 4.2. Назначение

**Решение: это не user-роль, а маркер service-account.**

- Создаётся **один технический User** с `email = qr-service@spaceofjoy.ru`, `role = qr_service`, без пароля (не может логиниться через `/api/login`)
- Этот user — `client_credentials` resource owner для qr-server S2S вызовов
- Обычные пользователи **никогда** не получают эту роль

### 4.3. Разрешения

| Может | Не может |
|-------|----------|
| Создавать заказы с auto-payment от имени any user (по email) | Менять профиль/пароль других пользователей |
| Читать типы билетов, опции, цены | Менять матрицу переходов статусов |
| Создавать/редактировать промокоды | Видеть полные истории всех заказов системы |
| Получать список заказов конкретного user-email | Назначать роли (`changeRole`) |
| Использовать introspect endpoint | Удалять что-либо |

### 4.4. Middleware `role:qr_service`

Использовать существующий `CheckRole` middleware (см. `Backend/app/Http/Middleware/CheckRole.php`) — он уже работает с `AccountRoleHelper`. **Не плодить новый middleware.**

Для **OAuth2-сценариев** (когда qr действует от имени user) — middleware **`scope:tickets:read`** (Passport ship'ит `CheckScopes`/`CheckForAnyScope`).

---

## 5. Защита эндпоинтов

### 5.1. Эндпоинты для qr.spaceofjoy.ru

| Эндпоинт | Метод | Scope | Кто реально работает |
|----------|-------|-------|----------------------|
| `/api/v1/festival/load` | GET | публичный (без изменений) | qr читает типы билетов |
| `/api/v1/festival/loadByTicketType/{id}` | GET | публичный | qr читает способы оплаты |
| `/api/v1/option/getList` (новый, v2.6.0) | POST | `tickets:read` | qr читает опции |
| `/api/v1/order/create` | POST | `orders:write` + AutoPayment header | qr создаёт заказ от user |
| `/api/v1/order/getUserList` | GET | `orders:read` (через auth-user) | пользователь смотрит свои заказы в qr UI |
| `/api/v1/promoCode/savePromoCode` | POST | `promocodes:write` | qr создаёт промокод |
| `/api/v1/promoCode/getListPromoCode` | GET | `promocodes:read` | qr читает свои промокоды |
| `/api/user` | GET | `profile:read` | qr показывает имя/email пользователя |

**Не дать qr:**
- `account/*` (управление пользователями)
- `questionnaire/approve` (одобрение анкет — только admin/manager)
- `order/getHistory` (полная история заказа — админ-данные)
- `order/toChangeStatus` (смена статуса заказа — это полномочия seller/admin)

### 5.2. Throttle / rate limit

Отдельный rate limiter для qr-эндпоинтов в `RouteServiceProvider`:

```php
RateLimiter::for('qr-api', function (Request $request) {
    return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('qr-oauth', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip());  // защита от brute /oauth/token
});
```

Применить:
- `Route::middleware(['auth:api-oauth', 'throttle:qr-api'])->group(...)`
- `/oauth/token` — `throttle:qr-oauth` (10/мин на IP)

---

## 6. Безопасность авторизации пользователя

### 6.1. Bypass / impersonation attacks

**Угроза:** злоумышленник перехватывает `code` из redirect URL и обменивает на токен.

**Митигация:**
- **PKCE обязателен** (`code_challenge_method=S256`). Без него — отказ от обмена `code` на token
- **State parameter** — обязателен. Backend проверяет совпадение state из authorize-запроса и token-запроса. Защита от CSRF
- **Authorization code lifetime** — 10 минут (default Passport). Снизить до 5
- **One-time use** — code инвалидируется после первого использования (Passport делает по умолчанию)

### 6.2. CSRF на authorization endpoint

`/oauth/authorize` — это GET-страница с consent. Passport использует Laravel CSRF middleware (`web` group). Должно быть включено.

**Проверить:** `app/Http/Kernel.php → $middlewareGroups['web']` содержит `VerifyCsrfToken` (и `/oauth/authorize` использует web group, а не api).

### 6.3. PKCE для public clients

В `oauth_clients` — для qr-SPA-клиента `personal_access_client = 0`, `password_client = 0`, `revoked = 0`. Через CLI:
```bash
php artisan passport:client --public --name="qr.spaceofjoy.ru SPA" --redirect_uri=https://qr.spaceofjoy.ru/auth/callback
```

Это создаёт client **без секрета** (public client). Passport автоматически требует PKCE для таких клиентов.

### 6.4. Validate redirect_uri

Passport проверяет `redirect_uri` ровно (string match). **Ни в коем случае не позволять wildcard** (`https://*.spaceofjoy.ru/callback` — атака на subdomain takeover).

Список разрешённых:
- `https://qr.spaceofjoy.ru/auth/callback` (prod)
- `http://localhost:5173/auth/callback` (dev — только в dev `.env`!)

### 6.5. Open Redirect

После `/oauth/logout?redirect=URL` — **whitelist** URL. Запрещено перенаправлять на произвольный URL.

```php
$allowedRedirects = [
    'https://qr.spaceofjoy.ru',
    'https://tickets.spaceofjoy.ru',
];
if (!in_array($redirect, $allowedRedirects, true)) {
    $redirect = config('app.url');
}
```

---

## 7. Защита персональных данных (152-ФЗ)

### 7.1. Что передаём в qr.spaceofjoy.ru

- `email`, `phone`, `name`, `city` (через `/api/user` с scope `profile:read`)
- Список заказов пользователя (если scope `orders:read`)
- Это **передача персданных третьему лицу** (даже если внутреннее — формально юридически)

### 7.2. Согласие пользователя

**Consent screen** — обязательный шаг OAuth2 flow:

При первом логине в qr.spaceofjoy.ru показать:
> «qr.spaceofjoy.ru запрашивает доступ к: вашему email, телефону, заказам. Согласны?»
> [Разрешить] [Отказать]

Согласие сохраняется в `oauth_clients.consent_granted_at` (расширить таблицу миграцией) + флаг в `users.qr_consent_at` (timestamp).

**Альтернатива (быстрее, но рискованнее):** если qr — наш внутренний сервис, можно использовать `Passport::ignoreClientConsent()` для конкретного client_id. **Не рекомендую** — РКН может счесть отсутствие явного согласия нарушением.

### 7.3. Логирование передачи персданных

В audit-log писать событие `oauth.consent.granted`:
```json
{
  "actor_type": "user",
  "actor_id": "uuid",
  "action": "oauth.consent.granted",
  "payload": {
    "client_id": "qr-spa",
    "scopes": ["profile:read", "orders:read"]
  }
}
```

### 7.4. Связь с TD-7 (152-ФЗ compliance)

Этот пункт **дополняет** TD-7. После v2.6.0 — обновить TECH_DEBT:
- Согласие на передачу персданных в qr.spaceofjoy.ru добавить в Политику конфиденциальности (work с tech writer)
- Уведомить товарища, который помогает с РКН, о новом канале передачи персданных

---

## 8. Audit-логи

### 8.1. Что логировать

| Событие | Уровень | actor_type | Что писать в payload |
|---------|---------|------------|----------------------|
| `oauth.token.issued` | info | user / qr_service | client_id, scopes, token_id (последние 8 chars), expires_at. **НИКОГДА не сам токен** |
| `oauth.token.refreshed` | info | user | client_id, old_token_id, new_token_id |
| `oauth.token.revoked` | info | user / admin | token_id, reason |
| `oauth.login.failed` | warn | null | client_id, reason, ip |
| `oauth.consent.granted` | info | user | client_id, scopes |
| `oauth.consent.denied` | info | user | client_id |
| `oauth.scope.violation` | warn | user / qr_service | client_id, requested_scope, granted_scope |
| `oauth.client.created` | info | admin | client_id, name |
| `oauth.client.secret_rotated` | warn | admin | client_id |

### 8.2. Канал

Связано с TD-10 (v2.7.0 — единый audit-канал). До v2.7.0:
- Отдельный файл-канал `passport.log` (config/logging.php) + ротация 14 дней
- После v2.7.0 — слить в Loki поток `audit`

### 8.3. Что НЕ логировать

- Сам access/refresh token (даже в маскированном виде — рискованно)
- Пароли (Passport их не видит — но на всякий случай)
- Полный PII payload в `oauth.token.issued` — только token_id и scopes

### 8.4. Алерты (Sentry / Grafana)

| Триггер | Действие |
|---------|----------|
| > 5 failed login за 1 мин с одного IP | Sentry alert + временный block IP |
| Refresh без последующего use токена | Подозрение на token theft — пометить как анормальный паттерн |
| Issue token с scope, которого нет у клиента | CRITICAL — скорее всего misconfig или scope confusion attack |
| Использование revoked-токена | Sentry alert + force-logout |

---

## 9. Хранение секретов

### 9.1. Что куда

| Секрет | Где живёт | Можно в git? |
|--------|-----------|--------------|
| `oauth-private.key` / `oauth-public.key` | `storage/` (filesystem) | **НЕТ** (добавить в `.gitignore`) |
| Client secrets для S2S | `.env` на qr-сервере (`QR_OAUTH_CLIENT_SECRET`) | **НЕТ** |
| `client_id` (S2S и SPA) | `.env` или config (не секрет, но в коде хардкодить плохо) | OK (config) |
| `AUTO_PAYMENT_TOKEN` | `.env` (уже есть) | **НЕТ** |
| `JWT_SECRET` | `.env` (уже есть) | **НЕТ** |

### 9.2. Файлы для добавления в `.gitignore`

```
storage/oauth-*.key
```

### 9.3. `.env.example` дополнить

```bash
# Laravel Passport
PASSPORT_PRIVATE_KEY_PATH=storage/oauth-private.key
PASSPORT_PUBLIC_KEY_PATH=storage/oauth-public.key

# Для qr.spaceofjoy.ru integration (S2S)
QR_OAUTH_CLIENT_ID=
QR_OAUTH_CLIENT_SECRET=
QR_ALLOWED_REDIRECT_URIS=https://qr.spaceofjoy.ru/auth/callback
```

### 9.4. Ротация ключей

**План:**
1. Сгенерировать новую пару `oauth-private.key.new` / `oauth-public.key.new`
2. Laravel Passport на v11 поддерживает только одну пару — нужен `passport:key-rotation` workaround:
   - Опубликовать новый публичный ключ на qr-сторону (qr поддерживает старый + новый ключи переходный период)
   - Заменить файлы на Backend
   - Restart php-fpm
   - Все ныне выпущенные токены становятся невалидными → пользователи должны re-login
3. **Ротация делается раз в год** или при подозрении компрометации
4. **Без downtime — невозможно** (без многоключевой реализации). Митигация — короткое окно (5-10 мин в ночное время)

---

## 10. Тестовая стратегия

### 10.1. Unit-тесты

- `AccountRoleHelperTest::testQrServiceIsValid()` — что роль зарегистрирована
- `CheckRoleMiddlewareTest::testAllowsQrServiceRole()` / `testRejectsGuestForQrEndpoint()`
- `OAuthScopeTest::testTicketsReadScopeIncludes(...)`

### 10.2. Integration-тесты OAuth2 flow

Passport ship'ит helpers:
```php
use Laravel\Passport\Passport;

public function testQrCanReadTickets(): void
{
    Passport::actingAs($qrServiceUser, ['tickets:read']);
    $response = $this->getJson('/api/v1/option/getList');
    $response->assertOk();
}

public function testQrCannotChangeStatus(): void
{
    Passport::actingAs($qrServiceUser, ['orders:write']);
    $response = $this->postJson('/api/v1/order/toChangeStatus/...');
    $response->assertForbidden();
}
```

### 10.3. Security-pentest checklist

| # | Атака | Тест |
|---|-------|------|
| 1 | **CSRF на /oauth/authorize** | Отправить POST без CSRF token → 419 |
| 2 | **Open redirect на /oauth/logout** | redirect=https://evil.com → редирект на app.url |
| 3 | **PKCE bypass** | Запросить code без code_challenge → отказ |
| 4 | **State replay** | Один state использовать дважды → второй раз отказ |
| 5 | **JWT confusion (alg=none)** | Если перейдём на JWT access tokens — токен с alg=none должен быть отвергнут |
| 6 | **Refresh token theft / reuse detection** | Использовать старый refresh после его обновления → revoke всей цепочки |
| 7 | **Scope escalation** | client запросил `tickets:read`, пытается вызвать endpoint с `orders:write` → 403 |
| 8 | **IDOR через /api/user** | qr-токен user A пытается получить данные user B → 403 |
| 9 | **SSRF на /oauth/authorize callback** | Проверить что redirect_uri не позволяет внутренние URL (file://, http://localhost) |
| 10 | **Brute force /oauth/token** | 50 запросов за 10 сек → throttle 429 |

---

## 11. Open questions / решения для tech-lead

| # | Вопрос | Варианты | Моя рекомендация |
|---|--------|----------|------------------|
| 1 | **Pure Passport или Socialite+Passport** | A) Passport только. B) Passport + Socialite (если в будущем будут Google/VK login) | **A** для v2.6.0. Socialite — отдельный backlog |
| 2 | **Consent screen — где** | A) Дефолтный Passport (`vendor:publish --tag=passport-views`). B) Кастомная страница на Backend (`/oauth/authorize` override). C) Редирект на отдельный фронт | **A** для v2.6.0 (быстро). B — в v2.7.0+ когда будет дизайн |
| 3 | **Multi-tenant** | Сейчас один qr-клиент. Расширяемо? | Сейчас не нужно. Архитектура Passport multi-client из коробки, расширяться есть куда |
| 4 | **Refresh token rotation** | A) Включить (новый refresh каждый refresh). B) Не включать (один refresh весь срок). | **A** — security best practice. Passport ship'ит `Passport::enableRefreshTokenRotation()` (или эквивалент). Проверить совместимость с qr-стороной |
| 5 | **Auto-revoke старых токенов при logout** | A) Только текущий токен. B) Все токены пользователя | **B** для безопасности (если user обнаружил, что аккаунт скомпрометирован) |
| 6 | **Что с уже выпущенными JWT при миграции** | Если когда-нибудь мигрируем на Passport: A) Принудительный re-login. B) Параллельное хождение до истечения JWT | **A** — проще. Объявить maintenance window 30 мин |
| 7 | **JWT vs Opaque access tokens в Passport** | A) JWT (быстро на qr-стороне). B) Opaque + introspection | **B** для v2.6.0 (проще, безопаснее — мгновенный revoke). A — оптимизация на потом |
| 8 | **Hybrid (JWT для tickets + Passport для qr) vs Full Passport** | См. §2 | **Hybrid** — рекомендация §2 |

---

## 12. План реализации (sub-веток)

Все ветки от **`feat/v2.6.0-fall-festival`** (интеграционная). Мерж — через PR.

| # | Ветка | Что делает | Длительность |
|---|-------|------------|--------------|
| 1 | `feat/v2.6.0-passport-install` | Установка `laravel/passport`. Миграции. Ключи (gitignore). `.env.example` дополнить. Hybrid конфиг `config/auth.php`. Smoke-test через `/oauth/token` | 1 день |
| 2 | `feat/v2.6.0-qr-service-role` | Роль `qr_service` в `AccountRoleHelper`. Технический User. Тесты middleware | 0.5 дня |
| 3 | `feat/v2.6.0-oauth-clients` | Создание public PKCE-client + S2S client через seeder/artisan. Документация `.env` для qr-стороны. Whitelist redirect_uri | 1 день |
| 4 | `feat/v2.6.0-sso-flow` | `/oauth/authorize` consent screen. `/oauth/logout` с revoke. Scopes (`tickets:read`, `orders:write`, etc). Middleware `scope:*` на эндпоинты для qr | 2-3 дня |
| 5 | `feat/v2.6.0-passport-tests` | Integration-тесты OAuth2 flow. Pentest-чеклист §10.3. Audit-логи Passport-событий | 1-2 дня |

**Итого:** ~6-8 рабочих дней (около половины спринта). Запас 2-3 дня на отладку и интеграцию с qr-стороной (нужна координация с разработчиком qr).

**Координация:** в начале sub-ветки #3 (oauth-clients) — синхрон с разработчиком qr.spaceofjoy.ru. Договориться о redirect_uri, scopes, формате error-response. **Без этой синхронизации — риск переделок.**

---

## 13. Чек-лист безопасности перед релизом v2.6.0

- [ ] Приватные ключи Passport НЕ в git (`git ls-files | grep oauth-`)
- [ ] `.env.example` обновлён (без реальных секретов, только placeholder)
- [ ] Все oauth endpoints за HTTPS на проде (проверить через `curl -I https://api.spaceofjoy.ru/oauth/token`)
- [ ] CSRF protection включён на `/oauth/authorize` (web middleware group)
- [ ] PKCE требуется для public clients (проверить — попытаться обменять code без `code_verifier` → отказ)
- [ ] Rate limiting на `/oauth/token` (10/мин по IP)
- [ ] Audit-логи всех OAuth-событий пишутся (см. §8.1)
- [ ] Refresh token rotation включён (см. §11 пункт 4)
- [ ] Pentest §10.3 пройден (минимум — пункты 1, 2, 3, 6, 7, 10)
- [ ] 152-ФЗ согласие на передачу персданных в qr.spaceofjoy.ru реализовано (consent screen + запись `qr_consent_at`)
- [ ] Документация обновлена: `BUSINESS_RULES.md` §7 (роль `qr_service`), `API.md` (OAuth endpoints), `DOMAIN.md` (ActorType `qr_service`)
- [ ] `composer audit` без критичных уязвимостей (после `laravel/passport` install)
- [ ] Sentry-алерты по §8.4 настроены
- [ ] Создан техдолг на ротацию ключей (раз в год) — в TECH_DEBT
- [ ] Tech-lead подписал решения по §11 open questions

---

## История изменений документа

| Дата | Изменение |
|------|-----------|
| 2026-05-30 | Создан security-engineer'ом по запросу. Покрывает все 13 разделов из ТЗ. Главные рекомендации: Hybrid JWT+Passport (§2 Опция A), Authorization Code+PKCE (§1.1), Opaque tokens + introspection (§11 пункт 7), refresh rotation on (§11 пункт 4), ключи в storage + gitignore (§9). Дедлайн v2.6.0 — 2026-06-12. |
