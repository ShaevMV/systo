#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  setup-rabbitmq-topology.sh — топология интеграции qr→org в RabbitMQ (фаза 2)
#
#  ЗАЧЕМ. Заводит на брокере отдельный контур приёма заказов от внешней
#  qr.spaceofjoy.ru, согласно контракту .claude/specs/qr-integration/RABBITMQ_PUBLISH.md
#  (+ asyncapi.yaml). Создаёт:
#    • vhost  qr-integration                 — изоляция от внутренних очередей (vhost systo)
#    • user   qr_ingest                      — ТОЛЬКО publish в x.qr.inbound (внешняя qr)
#    • user   qr_consumer                    — ТОЛЬКО read q.qr.* (внутренний консьюмер org)
#    • exchange x.qr.inbound (topic)         — единственная точка входа от qr
#    • queues q.qr.order / q.qr.email (quorum, durable; DLX-политика → x.qr.dlx,
#             at-least-once + overflow reject-publish — без молчаливой потери)
#    • DLQ   q.qr.dlq (терминальная; retry/backoff — фаза 4 вместе с консьюмером org)
#    • bindings по routing keys qr.order.create / qr.order.status / qr.email.send
#
#  СВОЙСТВА. Идемпотентный (повторный запуск безопасен). АДДИТИВНЫЙ — НЕ трогает
#  существующий vhost systo / пользователя systo / UI. БЕЗ рестарта брокера
#  (применяется к живому брокеру через rabbitmqctl + import_definitions).
#  Секрет qr_ingest НЕ в git — берётся/генерируется в .env (RABBITMQ_QR_INGEST_PASS).
#
#  ЗАПУСК (на сервере staging, из корня репозитория):
#     bash infra/staging/setup-rabbitmq-topology.sh
#  Переопределяемое: RABBITMQ_CONTAINER (по умолч. rabbitmq-staging), ENV_FILE.
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

CONTAINER="${RABBITMQ_CONTAINER:-rabbitmq-staging}"
ENV_FILE="${ENV_FILE:-${REPO_ROOT}/.env.staging}"
DEFS_FILE="${REPO_ROOT}/Docker/rabbitmq/qr-integration-definitions.json"
VHOST="qr-integration"
QR_USER="qr_ingest"
INBOUND_EXCHANGE="x.qr.inbound"

# rabbitmqctl внутри контейнера (cookie-auth, без логина/пароля).
rctl() { docker exec "${CONTAINER}" rabbitmqctl "$@"; }

echo "▶ Топология RabbitMQ для интеграции qr→org (контейнер: ${CONTAINER}, vhost: ${VHOST})"

# ── 0. Предусловия ───────────────────────────────────────────────────────────
if ! docker inspect -f '{{.State.Running}}' "${CONTAINER}" >/dev/null 2>&1; then
  echo "❌ Контейнер ${CONTAINER} не запущен. Подними брокер и повтори."
  exit 1
fi
if [[ ! -f "${DEFS_FILE}" ]]; then
  echo "❌ Нет файла определений: ${DEFS_FILE}"
  exit 1
fi
# Валидность JSON определений (если есть jq) — чтобы не импортировать битое.
if command -v jq >/dev/null 2>&1; then
  jq empty "${DEFS_FILE}" || { echo "❌ ${DEFS_FILE} не валидный JSON."; exit 1; }
fi

# ── 1. Пароль qr_ingest: взять из .env или сгенерировать (секрет НЕ в git) ────
QR_PASS=""
if [[ -f "${ENV_FILE}" ]] && grep -q '^RABBITMQ_QR_INGEST_PASS=' "${ENV_FILE}"; then
  QR_PASS="$(grep '^RABBITMQ_QR_INGEST_PASS=' "${ENV_FILE}" | head -1 | cut -d= -f2-)"
fi
if [[ -z "${QR_PASS}" ]]; then
  QR_PASS="$(openssl rand -hex 16)"
  if [[ -f "${ENV_FILE}" ]]; then
    printf '\nRABBITMQ_QR_INGEST_PASS=%s\n' "${QR_PASS}" >> "${ENV_FILE}"
    echo "  Сгенерирован RABBITMQ_QR_INGEST_PASS и дописан в ${ENV_FILE}"
  else
    echo "⚠ ${ENV_FILE} не найден — пароль qr_ingest сгенерирован, но НЕ сохранён."
    echo "  Сохрани вручную: RABBITMQ_QR_INGEST_PASS=${QR_PASS}"
  fi
else
  echo "  Использую существующий RABBITMQ_QR_INGEST_PASS из ${ENV_FILE}"
fi

# ── 2. vhost qr-integration (идемпотентно) ───────────────────────────────────
if rctl -q list_vhosts name 2>/dev/null | grep -qx "${VHOST}"; then
  echo "✓ vhost ${VHOST} уже есть"
else
  rctl add_vhost "${VHOST}"
  echo "  vhost ${VHOST} создан"
fi

# ── 3. Пользователь qr_ingest + publish-only права (идемпотентно) ────────────
if rctl -q list_users 2>/dev/null | awk '{print $1}' | grep -qx "${QR_USER}"; then
  rctl change_password "${QR_USER}" "${QR_PASS}" >/dev/null
  echo "✓ user ${QR_USER} уже есть — пароль синхронизирован с .env"
else
  rctl add_user "${QR_USER}" "${QR_PASS}" >/dev/null
  echo "  user ${QR_USER} создан"
fi
# Тегов администратора НЕ даём (обычный пользователь без management-доступа).
rctl set_user_tags "${QR_USER}" >/dev/null 2>&1 || true
# Права ТОЛЬКО на publish в x.qr.inbound: configure=^$, write=^x\.qr\.inbound$, read=^$
rctl set_permissions -p "${VHOST}" "${QR_USER}" '^$' "^${INBOUND_EXCHANGE//./\\.}$" '^$'
echo "  права ${QR_USER} на ${VHOST}: configure=∅  write=${INBOUND_EXCHANGE}  read=∅ (publish-only)"

# ── 3b. Пользователь qr_consumer + read-only права на q.qr.* (идемпотентно) ───
# Консьюмер org ЗАБИРАЕТ сообщения из очередей; qr_ingest этого НЕ может (publish-only).
# Это внутренний пользователь (org→брокер по docker-сети 5672), отдельный от внешнего qr_ingest.
QR_CONSUMER_USER="qr_consumer"
QR_CONSUMER_PASS=""
if [[ -f "${ENV_FILE}" ]] && grep -q '^RABBITMQ_QR_CONSUMER_PASS=' "${ENV_FILE}"; then
  QR_CONSUMER_PASS="$(grep '^RABBITMQ_QR_CONSUMER_PASS=' "${ENV_FILE}" | head -1 | cut -d= -f2-)"
fi
if [[ -z "${QR_CONSUMER_PASS}" ]]; then
  QR_CONSUMER_PASS="$(openssl rand -hex 16)"
  if [[ -f "${ENV_FILE}" ]]; then
    printf '\nRABBITMQ_QR_CONSUMER_PASS=%s\n' "${QR_CONSUMER_PASS}" >> "${ENV_FILE}"
    echo "  Сгенерирован RABBITMQ_QR_CONSUMER_PASS и дописан в ${ENV_FILE}"
  else
    echo "⚠ ${ENV_FILE} не найден — пароль qr_consumer сгенерирован, но НЕ сохранён."
    echo "  Сохрани вручную: RABBITMQ_QR_CONSUMER_PASS=${QR_CONSUMER_PASS}"
  fi
else
  echo "  Использую существующий RABBITMQ_QR_CONSUMER_PASS из ${ENV_FILE}"
fi
if rctl -q list_users 2>/dev/null | awk '{print $1}' | grep -qx "${QR_CONSUMER_USER}"; then
  rctl change_password "${QR_CONSUMER_USER}" "${QR_CONSUMER_PASS}" >/dev/null
  echo "✓ user ${QR_CONSUMER_USER} уже есть — пароль синхронизирован с .env"
else
  rctl add_user "${QR_CONSUMER_USER}" "${QR_CONSUMER_PASS}" >/dev/null
  echo "  user ${QR_CONSUMER_USER} создан"
fi
rctl set_user_tags "${QR_CONSUMER_USER}" >/dev/null 2>&1 || true
# Права ТОЛЬКО на чтение очередей q.qr.*: configure=^$, write=^$, read=^q\.qr\.
rctl set_permissions -p "${VHOST}" "${QR_CONSUMER_USER}" '^$' '^$' '^q\.qr\.'
echo "  права ${QR_CONSUMER_USER} на ${VHOST}: configure=∅  write=∅  read=q.qr.* (consume-only)"

# ── 4. Импорт топологии (exchanges/queues/bindings/policy) — идемпотентно ─────
TMP_IN_CONTAINER="/tmp/qr-integration-definitions.json"
docker cp "${DEFS_FILE}" "${CONTAINER}:${TMP_IN_CONTAINER}"
rctl import_definitions "${TMP_IN_CONTAINER}"
docker exec "${CONTAINER}" rm -f "${TMP_IN_CONTAINER}" 2>/dev/null || true
echo "  топология импортирована (exchanges/queues/bindings/policy)"

# ── 5. Проверка ──────────────────────────────────────────────────────────────
echo
echo "── Итог (${VHOST}) ──"
echo "vhosts:";       rctl -q list_vhosts name 2>/dev/null | sed 's/^/  /'
echo "permissions (все юзеры vhost):"; rctl -q list_permissions -p "${VHOST}" 2>/dev/null | sed 's/^/  /'
echo "exchanges:";    rctl -q list_exchanges -p "${VHOST}" name type 2>/dev/null | grep -E 'x\.qr|^name' | sed 's/^/  /'
echo "queues:";       rctl -q list_queues -p "${VHOST}" name type durable 2>/dev/null | sed 's/^/  /'
echo
echo "✓ Готово. Брокер принимает от qr через vhost '${VHOST}', exchange '${INBOUND_EXCHANGE}'."
echo "  Дальше: TLS/mTLS-листенер для внешней qr (фаза 3) + консьюмер в org (фаза 4)."
