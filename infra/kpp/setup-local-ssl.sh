#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  Локальный SSL для офлайн-узла КПП (Ф5, PR-7).
#
#  Камера PWA (getUserMedia/BarcodeDetector) требует secure context (HTTPS). При
#  топологии «облако-мастер» телефоны обычно ходят к облаку по своей сотовой HTTPS —
#  тогда этот скрипт НЕ нужен. Он нужен ТОЛЬКО для аварийного локального узла КПП
#  (полный блэкаут сотовой): поднять локальный HTTPS на kpp.local / фикс-IP, чтобы
#  камера работала на телефонах, подключённых к локальному Wi-Fi узла.
#
#  Делает: ставит mkcert (если нет) → создаёт локальный CA → выпускает серт на
#  заданные hostname/IP → кладёт серт+ключ в ./certs → экспортирует корневой CA
#  для раскатки на телефоны (см. INSTALL_CA_RUNBOOK.md).
#
#  Использование:
#    ./setup-local-ssl.sh kpp.local 192.168.1.10
#    HOSTS="kpp.local 192.168.1.10 192.168.1.11" ./setup-local-ssl.sh
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

CERT_DIR="$(cd "$(dirname "$0")" && pwd)/certs"
HOSTS="${HOSTS:-${*:-kpp.local}}"

echo "▸ Локальный SSL для узла КПП. Хосты: $HOSTS"
mkdir -p "$CERT_DIR"

# 1) mkcert
if ! command -v mkcert >/dev/null 2>&1; then
    echo "▸ mkcert не найден. Установка…"
    if command -v apt-get >/dev/null 2>&1; then
        sudo apt-get update -y && sudo apt-get install -y libnss3-tools wget
        ARCH="$(dpkg --print-architecture)"
        wget -qO /tmp/mkcert "https://dl.filippo.io/mkcert/latest?for=linux/${ARCH}"
        sudo install -m 0755 /tmp/mkcert /usr/local/bin/mkcert
    elif command -v brew >/dev/null 2>&1; then
        brew install mkcert nss
    else
        echo "✗ Не удалось поставить mkcert автоматически. Поставьте вручную: https://github.com/FiloSottile/mkcert" >&2
        exit 1
    fi
fi

# 2) Локальный CA (идемпотентно)
echo "▸ Установка локального CA (mkcert -install)…"
mkcert -install

# 3) Серт на хосты
echo "▸ Выпуск сертификата для: $HOSTS"
# shellcheck disable=SC2086
mkcert -cert-file "$CERT_DIR/kpp.crt" -key-file "$CERT_DIR/kpp.key" $HOSTS

# 4) Экспорт корневого CA для раздачи на телефоны
CAROOT="$(mkcert -CAROOT)"
cp "$CAROOT/rootCA.pem" "$CERT_DIR/rootCA.pem"

echo ""
echo "✓ Готово."
echo "  Серт:   $CERT_DIR/kpp.crt"
echo "  Ключ:   $CERT_DIR/kpp.key"
echo "  CA:     $CERT_DIR/rootCA.pem  ← раскатать на телефоны (см. INSTALL_CA_RUNBOOK.md)"
echo ""
echo "  Подключите серт в nginx узла КПП (infra/kpp/docker-compose.kpp.yml, PR-9):"
echo "    ssl_certificate     /certs/kpp.crt;"
echo "    ssl_certificate_key /certs/kpp.key;"
