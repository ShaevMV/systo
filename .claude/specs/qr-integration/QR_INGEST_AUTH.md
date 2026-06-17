# Аутентификация канала qr → org (приём заказов)

> **Статус:** актуально на 2026-06-17. Реализовано в ветке `feat/template-bindings` (middleware `QrIngestAuth`).
> **Связь:** `QR_CREATE_API.md` — контракт тела запроса. Этот документ — **про защиту канала** (кто имеет право слать заказ).

---

## 1. Модель защиты

Канал `POST /api/v1/qrOrder/create` хранит ПДн и выпускает билеты, поэтому закрыт **двумя независимыми барьерами** (defense in depth):

| Барьер | Уровень | Что проверяет | Обязателен |
|---|---|---|---|
| **TLS (HTTPS)** | транспорт | шифрует данные в пути | ✅ уже есть (`api.spaceofjoy.ru`) |
| **Сервисный ключ** `X-QR-Token` | приложение | «это точно витрина qr» | ✅ middleware `qr.ingest` |
| **Allowlist IP qr-сервера** | сеть (nginx) | запрос пришёл с адреса qr | ⬜ опционально (если у qr статичный IP) |

Принцип: даже если один барьер обойдён (утёк ключ / подменён IP), второй ещё держит.

---

## 2. Сервисный ключ — как устроен

- qr шлёт в каждом запросе заголовок **`X-QR-Token: <ключ>`**.
- org сверяет его со списком валидных ключей из `config('services.qr_ingest.tokens')` (источник — env `QR_INGEST_TOKENS`).
- Сравнение — `hash_equals` (constant-time, защита от timing-атаки).
- **Несколько ключей через запятую** → ротация без простоя (см. §5).
- **Безопасный дефолт:** пустой `QR_INGEST_TOKENS` = канал **закрыт** (любой запрос → 401). Открыть случайно нельзя.

Нет/неверный ключ → `401 { "success": false, "message": "Доступ запрещён: …" }`, заказ **не создаётся**.

---

## ЧАСТЬ A. Настройка на org (деплой на прод)

### A.1. Сгенерировать ключ

```bash
openssl rand -hex 32
# пример: 7f3c9a1e8b6d4f20a5c7e9b1d3f5a7c9e1b3d5f7a9c1e3b5d7f9a1c3e5b7d9f1
```

### A.2. Прописать в `.env` Backend на проде

```dotenv
# /var/www/.../Backend/.env
QR_INGEST_TOKENS=7f3c9a1e8b6d4f20a5c7e9b1d3f5a7c9e1b3d5f7a9c1e3b5d7f9a1c3e5b7d9f1
```

> Этот же ключ передать **владельцу qr-сервиса** по защищённому каналу (не в почте/чате открытым текстом). Он пропишет его у себя (см. Часть B).

### A.3. Сбросить кеш конфига (обязательно, если на проде `config:cache`)

```bash
docker exec php-solarSysto php artisan config:clear
# или, если используется закешированный конфиг:
docker exec php-solarSysto php artisan config:cache
```

> ⚠️ Laravel читает `QR_INGEST_TOKENS` через `config()`. При закешированном конфиге новое значение `.env` **не подхватится** без `config:cache`/`config:clear`.

### A.4. Проверить

```bash
# без ключа → 401
curl -s -o /dev/null -w "%{http_code}\n" -X POST https://api.spaceofjoy.ru/api/v1/qrOrder/create \
  -H "Content-Type: application/json" -d '{}'
# ожидаем: 401

# с ключом и пустым телом → 422 (ключ принят, упала валидация контракта — это норма)
curl -s -o /dev/null -w "%{http_code}\n" -X POST https://api.spaceofjoy.ru/api/v1/qrOrder/create \
  -H "Content-Type: application/json" -H "X-QR-Token: <ключ>" -d '{}'
# ожидаем: 422
```

### A.5. (Опционально) Allowlist IP qr на nginx

Если у qr-сервера статичный исходящий IP — добавить второй барьер в конфиг nginx прод-сервера:

```nginx
# в server-блоке api.spaceofjoy.ru
location = /api/v1/qrOrder/create {
    allow 203.0.113.10;   # IP qr-сервера (узнать у владельца qr)
    deny  all;
    try_files $uri $uri/ /index.php?$query_string;  # как в основном location
}
```

```bash
nginx -t && systemctl reload nginx
```

> Если IP у qr **плавает** — этот шаг пропустить, защиту держит ключ (Часть A.1–A.4).

---

## ЧАСТЬ B. Инструкция для системы qr.spaceofjoy.ru

> Передать команде/Claude репозитория qr.

### B.1. Что добавить в запрос

К каждому `POST` на `https://api.spaceofjoy.ru/api/v1/qrOrder/create` добавить заголовок:

```
X-QR-Token: <ключ, выданный org>
```

Остальной контракт тела — без изменений (см. `QR_CREATE_API.md`).

### B.2. Пример (Python, requests)

```python
import os, requests

ORG_API = "https://api.spaceofjoy.ru/api/v1/qrOrder/create"
QR_TOKEN = os.environ["ORG_QR_INGEST_TOKEN"]  # хранить в env/секретах, НЕ в коде

resp = requests.post(
    ORG_API,
    headers={
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-QR-Token": QR_TOKEN,
    },
    json=order_payload,   # тот же расширенный JSON-контракт
    timeout=15,
)
```

### B.3. Обработка ответов

| Код | Значение | Что делать на стороне qr |
|---|---|---|
| `200` | заказ принят (вкл. повторную отправку того же `order_id`) | успех |
| `401` | ключ неверный/отсутствует | **не ретраить** — проверить `X-QR-Token` (протух/опечатка) |
| `422` | ключ ок, но контракт невалиден (нет email/битый uuid) | исправить тело, не ретраить как есть |
| `500` | ошибка на стороне org | можно ретраить (приём идемпотентен по `order_id`) |

> Приём **идемпотентен** по `order_id` — сетевые сбои можно безопасно ретраить тем же телом.

### B.4. Хранение ключа

- Ключ — в переменных окружения / секрет-менеджере qr, **не в коде и не в git**.
- При компрометации — немедленно сообщить org (ключ сменят, см. §5).

---

## 5. Ротация ключа (при компрометации или плановая)

Список ключей через запятую позволяет менять ключ **без простоя**:

1. **org:** добавить новый ключ рядом со старым:
   ```dotenv
   QR_INGEST_TOKENS=<старый>,<новый>
   ```
   `config:cache` → теперь валидны **оба**.
2. **qr:** переключить `X-QR-Token` на `<новый>`, выкатить.
3. **org:** убедиться по логам, что трафик идёт с новым ключом, затем убрать старый:
   ```dotenv
   QR_INGEST_TOKENS=<новый>
   ```
   `config:cache`.

В любой момент перехода хотя бы один валидный ключ активен → заказы не теряются.

---

## 6. Провенанс (файлы кода)

| Слой | Файл |
|---|---|
| Middleware | `Backend/app/Http/Middleware/QrIngestAuth.php` |
| Alias `qr.ingest` | `Backend/app/Http/Kernel.php` |
| Конфиг ключей | `Backend/config/services.php` (`qr_ingest.tokens`) |
| Роут | `Backend/routes/qrOrder.php` (`/create` + `->middleware('qr.ingest')`) |
| env | `Backend/.env.example` (`QR_INGEST_TOKENS`) |
| Тесты | `Backend/tests/Feature/QrOrder/QrOrderAuthApiTest.php` (+ трейт `WithQrIngestToken`) |

> Чтение (`getList`/`getItem`/`getHistory`/`getStats`) — отдельный канал, закрыт `auth:api + admin` (JWT админа org), его этот документ не касается.
