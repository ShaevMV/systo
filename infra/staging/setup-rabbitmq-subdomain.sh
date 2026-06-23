#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  setup-rabbitmq-subdomain.sh
#  ТОЧЕЧНО добавляет субдомен rabbitmq.staging.spaceofjoy.ru → RabbitMQ Management UI.
#
#  ЗАЧЕМ ОТДЕЛЬНЫЙ СКРИПТ. setup-host-nginx.sh перезаписывает конфиги ВСЕХ доменов
#  и СОТРЁТ 443-блоки, которые certbot дописал in-place → на ЖИВОМ сервере его
#  повторно гонять НЕЛЬЗЯ. Этот скрипт добавляет ТОЛЬКО rabbitmq-домен, не трогая
#  остальные пять — безопасен на работающем staging.
#
#  ЧТО ДЕЛАЕТ (идемпотентно):
#    1. Копирует server-блок rabbitmq → /etc/nginx/sites-available + symlink в enabled
#    2. nginx -t → reload (только если конфиг валиден)
#    3. certbot --nginx -d rabbitmq.staging.spaceofjoy.ru (если сертификата ещё нет)
#    4. Проверка: https://rabbitmq.staging.spaceofjoy.ru/rabbitmq/ → 401 (UI жив за auth)
#
#  ПРЕДУСЛОВИЯ:
#    • DNS A-запись rabbitmq.staging.spaceofjoy.ru → IP сервера (77.222.32.244) РАСПРОСТРАНЕНА
#    • basic-auth уже настроен (setup-basic-auth.sh — общий htpasswd staging-tools)
#    • контейнер rabbitmq-staging слушает UI на 127.0.0.1:8085 (docker-compose.staging.yml)
#
#  ЗАПУСК (на сервере, root):
#      bash infra/staging/setup-rabbitmq-subdomain.sh <admin-email>
#  email нужен только при ПЕРВОМ выпуске сертификата (дальше certbot.timer продлевает).
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOMAIN="rabbitmq.staging.spaceofjoy.ru"
SRC="${SCRIPT_DIR}/nginx/sites-available/${DOMAIN}.conf"
DST="/etc/nginx/sites-available/${DOMAIN}.conf"
LINK="/etc/nginx/sites-enabled/${DOMAIN}.conf"
HTPASSWD="/etc/nginx/auth/staging-tools.htpasswd"
ADMIN_EMAIL="${1:-}"

log()  { echo -e "\033[1;34m[rmq-domain]\033[0m $*"; }
warn() { echo -e "\033[1;33m[warn]\033[0m $*" >&2; }
err()  { echo -e "\033[1;31m[err]\033[0m  $*" >&2; exit 1; }

[[ "${EUID}" -eq 0 ]] || err "Запускать от root."
command -v nginx >/dev/null || err "nginx не установлен."
[[ -f "${SRC}" ]] || err "Нет конфига: ${SRC} (скопируй infra/staging целиком на сервер)."

# ── DNS должен указывать на этот сервер (иначе certbot не выпустит) ───────────
SERVER_IP="$(curl -s -4 https://api.ipify.org 2>/dev/null || hostname -I | awk '{print $1}')"
RESOLVED="$(dig +short "${DOMAIN}" @8.8.8.8 2>/dev/null | tail -1)"
log "сервер IP: ${SERVER_IP} | ${DOMAIN} → ${RESOLVED:-(не резолвится)}"
if [[ "${RESOLVED}" != "${SERVER_IP}" ]]; then
  err "DNS ещё не указывает на этот сервер. Создай A-запись ${DOMAIN} → ${SERVER_IP} и подожди 5–10 мин."
fi

# ── basic-auth настроен? (пустой htpasswd блокирует всех) ────────────────────
if [[ ! -s "${HTPASSWD}" ]]; then
  warn "htpasswd ${HTPASSWD} пуст/нет — UI будет недоступен, пока не выполнишь setup-basic-auth.sh."
fi

# ── 1. Конфиг + symlink (идемпотентно) ───────────────────────────────────────
install -m 644 "${SRC}" "${DST}"
ln -sfn "${DST}" "${LINK}"
log "конфиг установлен: ${DST} (+ symlink в sites-enabled)"

mkdir -p /var/www/certbot/.well-known/acme-challenge
chown -R www-data:www-data /var/www/certbot 2>/dev/null || true

# ── 2. Проверка и reload nginx ───────────────────────────────────────────────
if ! nginx -t 2>&1; then
  err "nginx -t упал — проверь ${DST}. Изменения не применены (reload не делался)."
fi
systemctl reload nginx
log "nginx перезагружен"

# ── 3. SSL через certbot (точечно по одному домену, идемпотентно) ────────────
if ! command -v certbot >/dev/null; then
  warn "certbot не установлен. Поставь: apt-get install -y certbot python3-certbot-nginx — и перезапусти."
elif certbot certificates 2>/dev/null | grep -qE "(^|[ ])${DOMAIN}([ ]|\$)"; then
  log "сертификат для ${DOMAIN} уже есть — пропускаю (certbot.timer продлит)"
else
  [[ -n "${ADMIN_EMAIL}" ]] || err "Сертификата ещё нет — передай email: bash $0 you@example.com"
  [[ "${ADMIN_EMAIL}" =~ ^[^@]+@[^@]+\.[^@]+$ ]] || err "Не похоже на email: ${ADMIN_EMAIL}"
  log "выпускаю сертификат Let's Encrypt для ${DOMAIN}…"
  certbot --nginx --non-interactive --agree-tos --email "${ADMIN_EMAIL}" --redirect --no-eff-email -d "${DOMAIN}"
fi

# ── 4. Проверка ──────────────────────────────────────────────────────────────
echo
log "проверка https://${DOMAIN}/rabbitmq/ (ожидаем 401 — UI жив за basic-auth):"
CODE="$(curl -s -o /dev/null -m 15 -w '%{http_code}' "https://${DOMAIN}/rabbitmq/" 2>/dev/null || echo '000')"
echo "  HTTP ${CODE}"
case "${CODE}" in
  401) log "✓ Готово: ${DOMAIN} отдаёт UI за basic-auth (введи логин staging-tools, затем логин RabbitMQ)." ;;
  200) log "✓ Готово: ${DOMAIN} отвечает (basic-auth, похоже, пуст — проверь setup-basic-auth.sh)." ;;
  000) warn "Не достучался по HTTPS — возможно, сертификат ещё выпускается или DNS/файрвол. Проверь вручную." ;;
  *)   warn "Неожиданный код ${CODE} — проверь nginx error_log и certbot." ;;
esac
