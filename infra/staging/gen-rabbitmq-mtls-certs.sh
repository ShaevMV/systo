#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  gen-rabbitmq-mtls-certs.sh — mTLS-сертификаты для AMQPS-канала qr→брокер (Ф3)
#
#  МОДЕЛЬ (staging): серверный серт — переиспользуем Let's Encrypt, выпущенный для
#  rabbitmq.staging.spaceofjoy.ru (UI-субдомен) → qr верифицирует его публично, наш
#  CA ставить НЕ нужно. Клиентский серт qr — выпускает наш мини self-signed CA
#  (ca-client), брокер проверяет им клиента (verify_peer + fail_if_no_peer_cert).
#
#  ЧТО ДЕЛАЕТ (идемпотентно):
#    1. Копирует LE fullchain/privkey → server.pem/server.key (обновляется всегда).
#    2. Создаёт client CA (ca-client.pem/key), если ещё нет.
#    3. Выпускает клиентский серт qr (qr-client.pem/key), если ещё нет.
#    4. Ставит права (контейнер rabbitmq читает серты).
#
#  ВЫХОД (каталог НЕ в git — приватные ключи):
#    для БРОКЕРА:  server.pem, server.key, ca-client.pem  (монтируются в контейнер)
#    для QR:       qr-client.pem, qr-client.key           (передать безопасно)
#
#  ЗАПУСК (root — нужен доступ к приватному ключу Let's Encrypt):
#    sudo bash infra/staging/gen-rabbitmq-mtls-certs.sh
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

DIR="${CERT_DIR:-/var/www/systo/infra/staging/rabbitmq-certs}"
LE_DIR="${LE_DIR:-/etc/letsencrypt/live/rabbitmq.staging.spaceofjoy.ru}"
CLIENT_DAYS="${CLIENT_DAYS:-825}"

[[ "${EUID}" -eq 0 ]] || { echo "❌ Запускать от root (нужен приватный ключ Let's Encrypt)."; exit 1; }
if [[ ! -f "${LE_DIR}/fullchain.pem" || ! -f "${LE_DIR}/privkey.pem" ]]; then
  echo "❌ Нет LE-сертов в ${LE_DIR}. Сначала setup-rabbitmq-subdomain.sh (выпуск SSL для субдомена)."
  exit 1
fi
command -v openssl >/dev/null || { echo "❌ openssl не установлен."; exit 1; }

mkdir -p "${DIR}"
cd "${DIR}"

echo "▶ Серверный серт ← Let's Encrypt (${LE_DIR})"
cp -L "${LE_DIR}/fullchain.pem" server.pem
cp -L "${LE_DIR}/privkey.pem"   server.key
echo "  server.pem / server.key обновлены из LE"

echo "▶ Client CA (self-signed — проверяет клиентский серт qr)"
if [[ ! -f ca-client.pem ]]; then
  openssl genrsa -out ca-client.key 4096
  openssl req -x509 -new -nodes -key ca-client.key -sha256 -days 3650 \
    -subj "/CN=Systo qr-integration client CA (staging)" -out ca-client.pem
  echo "  client CA создан"
else
  echo "  client CA уже есть — пропускаю (не перевыпускаю, иначе клиентские серты станут невалидны)"
fi

echo "▶ Клиентский серт для qr (подписан client CA)"
if [[ ! -f qr-client.pem ]]; then
  openssl genrsa -out qr-client.key 4096
  openssl req -new -key qr-client.key -subj "/CN=qr-ingest-client" -out qr-client.csr
  printf 'extendedKeyUsage = clientAuth\n' > client-ext.cnf
  openssl x509 -req -in qr-client.csr -CA ca-client.pem -CAkey ca-client.key -CAcreateserial \
    -days "${CLIENT_DAYS}" -sha256 -extfile client-ext.cnf -out qr-client.pem
  rm -f qr-client.csr client-ext.cnf
  echo "  клиентский серт qr создан (CN=qr-ingest-client, ${CLIENT_DAYS} дней)"
else
  echo "  клиентский серт qr уже есть — пропускаю"
fi

# Контейнер rabbitmq (UID rabbitmq) должен читать серты. На staging — read-all (прагматично,
# сервер под доступом-контролем). Ключ client-CA наружу не нужен — закрываем.
chmod 644 server.pem server.key ca-client.pem qr-client.pem qr-client.key 2>/dev/null || true
chmod 600 ca-client.key 2>/dev/null || true

echo
echo "✓ Готово. ${DIR}:"
ls -1 "${DIR}"
echo
echo "ДЛЯ БРОКЕРА (монтируются в /etc/rabbitmq/certs/): server.pem, server.key, ca-client.pem"
echo "ДЛЯ QR (передать безопасно — НЕ по почте в открытом виде): qr-client.pem, qr-client.key"
echo "  (серверный CA для qr НЕ нужен — серт публичный Let's Encrypt)"
echo
echo "Дальше: подключить TLS-конфиг + порт 5671 + рестарт брокера + firewall (см. RABBITMQ_CONNECT_STAGING.md)."
echo "ПРИ ПРОДЛЕНИИ LE (раз в ~60 дней): перезапусти этот скрипт (обновит server.*) + рестарт брокера."
