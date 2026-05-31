#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  setup-ssl.sh
#  Устанавливает Let's Encrypt SSL для всех 5 поддоменов через certbot.
#
#  Использует http-challenge (без DNS) — нужно чтобы:
#    - DNS уже резолвился (5 A-записей → 77.222.32.244)
#    - host nginx уже слушал 80 порт с server_name на наши поддомены
#    - /var/www/certbot/.well-known/acme-challenge/ был доступен
#
#  Если что-то из этого не готово — certbot вернёт fail и ничего не сломает
#  (nginx-конфиги останутся как есть).
#
#  Запускать на сервере как root:
#      bash setup-ssl.sh <admin-email>
#
#  Где admin-email — куда Let's Encrypt будет слать уведомления об истечении.
#  Сертификаты автоматически продлеваются раз в 60 дней через systemd-таймер
#  certbot.timer (включён по умолчанию при установке).
#
#  Идемпотентен:
#    - Если сертификат уже есть — не запрашивает заново
#    - Если истекает скоро — продлевает
#    - Если конфиги уже изменены certbot'ом — не дублирует HTTPS-блоки
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

ADMIN_EMAIL="${1:-}"
DOMAINS=(
    "staging.spaceofjoy.ru"
    "api.staging.spaceofjoy.ru"
    "vhod.staging.spaceofjoy.ru"
    "pma.staging.spaceofjoy.ru"
    "mail.staging.spaceofjoy.ru"
)

log()  { echo -e "\033[1;34m[ssl]\033[0m $*"; }
warn() { echo -e "\033[1;33m[warn]\033[0m $*" >&2; }
err()  { echo -e "\033[1;31m[err]\033[0m  $*" >&2; exit 1; }

# ─── Sanity ───────────────────────────────────────────────────────────────────
[[ "$EUID" -eq 0 ]] || err "Запускать от root"
[[ -n "$ADMIN_EMAIL" ]] || err "Передай email для Let's Encrypt: bash $0 you@example.com"
[[ "$ADMIN_EMAIL" =~ ^[^@]+@[^@]+\.[^@]+$ ]] || err "Не похоже на email: $ADMIN_EMAIL"

# ─── 1. Установка certbot + python3-certbot-nginx ─────────────────────────────
if ! command -v certbot >/dev/null; then
    log "Устанавливаю certbot…"
    apt-get update -qq
    apt-get install -y -qq certbot python3-certbot-nginx
fi

# ─── 2. Проверка DNS ──────────────────────────────────────────────────────────
log "Проверяю распространение DNS…"
SERVER_IP="$(curl -s -4 https://api.ipify.org || hostname -I | awk '{print $1}')"
log "  IP этого сервера:  $SERVER_IP"
DNS_OK=true
for d in "${DOMAINS[@]}"; do
    RESOLVED="$(dig +short "$d" @8.8.8.8 | head -1)"
    if [[ "$RESOLVED" == "$SERVER_IP" ]]; then
        log "  ✓ $d → $RESOLVED"
    else
        warn "  ✗ $d → '$RESOLVED' (ожидалось $SERVER_IP)"
        DNS_OK=false
    fi
done
if [[ "$DNS_OK" == false ]]; then
    err "DNS не распространился для всех поддоменов. Жди 5-10 минут после создания A-записей."
fi

# ─── 3. Проверка что nginx слушает 80 и отдаёт ACME-challenge ─────────────────
log "Проверяю что /.well-known доступен через HTTP…"
mkdir -p /var/www/certbot/.well-known/acme-challenge
TEST_FILE="test-$(date +%s)"
echo "ok" > "/var/www/certbot/.well-known/acme-challenge/${TEST_FILE}"

for d in "${DOMAINS[@]}"; do
    if curl -fsSL "http://${d}/.well-known/acme-challenge/${TEST_FILE}" | grep -q "^ok$"; then
        log "  ✓ $d → ACME challenge доступен"
    else
        rm -f "/var/www/certbot/.well-known/acme-challenge/${TEST_FILE}"
        err "$d не отдаёт /.well-known/acme-challenge/. Проверь setup-host-nginx.sh."
    fi
done
rm -f "/var/www/certbot/.well-known/acme-challenge/${TEST_FILE}"

# ─── 4. Запрос сертификатов ───────────────────────────────────────────────────
# Каждому домену — отдельный сертификат (проще управление).
# Можно одной командой выпустить SAN, но при добавлении/удалении домена
# нужно перевыпускать целиком — менее гибко.
log "Запрашиваю сертификаты Let's Encrypt…"
CERTBOT_ARGS=(
    --nginx
    --non-interactive
    --agree-tos
    --email "$ADMIN_EMAIL"
    --redirect          # HTTP → HTTPS автоматически
    --no-eff-email
)

for d in "${DOMAINS[@]}"; do
    log "  Запрашиваю $d…"
    if certbot certificates 2>/dev/null | grep -A1 "Domains:" | grep -qE "(^|[ ])${d}([ ]|$)"; then
        log "    ✓ Уже выпущен — пропускаю (certbot.timer обновит автоматически)"
    else
        certbot "${CERTBOT_ARGS[@]}" -d "$d"
    fi
done

# ─── 5. Auto-renew systemd-таймер ─────────────────────────────────────────────
if systemctl is-enabled --quiet certbot.timer 2>/dev/null; then
    log "✓ certbot.timer активен — auto-renew работает"
else
    log "Включаю certbot.timer"
    systemctl enable --now certbot.timer
fi

# ─── 6. Итог ──────────────────────────────────────────────────────────────────
echo ""
log "════════════════════════════════════════════════════════"
log "  SSL установлен и настроен."
log "════════════════════════════════════════════════════════"
echo ""
echo "  Сертификаты:"
certbot certificates 2>/dev/null | grep -E "(Certificate Name|Domains|Expiry)" | sed 's/^/    /'
echo ""
echo "  HTTPS URL'ы:"
for d in "${DOMAINS[@]}"; do
    echo "    https://$d/"
done
echo ""
echo "  Auto-renew:  systemctl list-timers certbot.timer"
echo "  Тест renew:  certbot renew --dry-run"
