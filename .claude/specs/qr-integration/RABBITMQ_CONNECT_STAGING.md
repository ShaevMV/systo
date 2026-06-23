# QR → ORG — подключение к брокеру RabbitMQ (staging, mTLS)

> **Что это:** практическая инструкция для команды/ИИ-агента **qr.spaceofjoy.ru** — как ПОДКЛЮЧИТЬСЯ к нашему брокеру на staging и публиковать заказы. Дополняет `RABBITMQ_PUBLISH.md` (что и каким сообщением слать) и `asyncapi.yaml` (схема). Здесь — endpoint, mTLS и реквизиты.
> **Дата:** 2026-06-23. **Среда:** staging. Прод-endpoint будет отдельно.
> **Статус канала:** активируется на нашей стороне (генерация сертов + TLS-листенер + firewall). Перед боем сверьтесь, что мы дали «зелёный».

---

## 1. Параметры подключения (staging)

| Параметр | Значение |
|---|---|
| Протокол | **AMQPS** (AMQP over TLS) — `amqps://`, НЕ `amqp://` |
| Хост | `rabbitmq.staging.spaceofjoy.ru` |
| Порт | **5671** |
| vhost | `qr-integration` |
| Пользователь | `qr_ingest` |
| Пароль | **передаётся отдельно** (защищённым каналом, не в этом файле) |
| Права пользователя | только **publish** в exchange `x.qr.inbound` (ничего читать/создавать нельзя) |

---

## 2. TLS / mTLS

Канал требует **взаимного TLS** — и сервер, и клиент предъявляют сертификаты.

**Серверный сертификат (наш):** публичный **Let's Encrypt** для `rabbitmq.staging.spaceofjoy.ru`. Вы верифицируете его **штатно** (системный trust store) — **наш CA ставить НЕ нужно**, никаких custom-CA для проверки сервера.

**Клиентский сертификат (ваш):** мы выпускаем его для вас и передаём отдельно:
- `qr-client.pem` — клиентский сертификат,
- `qr-client.key` — приватный ключ (**секрет**, храните безопасно).

Подключаясь, вы предъявляете этот клиентский серт. Без него брокер откажет (`fail_if_no_peer_cert`). Поверх mTLS — обычная авторизация пользователем `qr_ingest` (двойной барьер).

> ⚠️ Дополнительно порт `5671` на время теста открыт по firewall. Для боевого режима пришлите нам **исходящий IP вашего сервера** — сузим доступ до него (allowlist).

---

## 3. Что вам передаём отдельно (секреты, НЕ в git/почте открытым текстом)

1. пароль пользователя `qr_ingest`;
2. `qr-client.pem` (клиентский сертификат);
3. `qr-client.key` (приватный ключ клиента).

---

## 4. Что публиковать

Тела и routing-keys — без изменений, см. **`RABBITMQ_PUBLISH.md`**:
- exchange: **`x.qr.inbound`** (topic);
- routing keys: `qr.order.create`, `qr.order.status`, `qr.email.send`;
- свойства: `delivery_mode=2` (persistent), `message_id = order_id` (для писем — `external_id`), `content_type=application/json`;
- publisher confirms — включить.

Идемпотентность — по `order_id` (id qr == id org): повторная публикация безопасна.

---

## 5. Пример подключения (Python, pika)

```python
import ssl, pika

ctx = ssl.create_default_context()           # доверяем публичному LE-серту сервера
ctx.load_cert_chain("qr-client.pem", "qr-client.key")  # наш клиентский серт (mTLS)

params = pika.ConnectionParameters(
    host="rabbitmq.staging.spaceofjoy.ru",
    port=5671,
    virtual_host="qr-integration",
    credentials=pika.PlainCredentials("qr_ingest", "<пароль>"),
    ssl_options=pika.SSLOptions(ctx, server_hostname="rabbitmq.staging.spaceofjoy.ru"),
    heartbeat=30,
)

conn = pika.BlockingConnection(params)
ch = conn.channel()
ch.confirm_delivery()  # publisher confirms

import json, uuid
order_id = str(uuid.uuid4())
body = json.dumps({"order_id": order_id, "order_data": {"email": "buyer@example.com", "status": "оплачен"}})
ch.basic_publish(
    exchange="x.qr.inbound",
    routing_key="qr.order.create",
    body=body,
    properties=pika.BasicProperties(delivery_mode=2, content_type="application/json", message_id=order_id),
)
conn.close()
```

(`aio-pika` / любой AMQP 0-9-1 клиент с TLS — аналогично: TLS-контекст + клиентский серт + creds.)

---

## 6. Проверка после подключения

После публикации заказ должен:
- исчезнуть из очереди `q.qr.order` (мы его забрали консьюмером);
- появиться у нас как принятый заказ (создаётся `qr_order`, пишется история `created`);
- при `status = "оплачен"` — запуститься выдача билетов.

Если что-то не доходит — напишите, посмотрим очереди / DLQ (`q.qr.dlq`) и логи на нашей стороне.

---

## 7. Прод

Этот файл — про **staging**. Боевой endpoint, серты и реквизиты (а также сужение firewall до вашего IP) согласуем отдельно перед запуском продаж.
