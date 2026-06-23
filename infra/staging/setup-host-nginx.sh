#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  setup-host-nginx.sh
#  Настраивает host-nginx на staging как reverse proxy для 5 поддоменов.
#
#  Что делает:
#    1. Бэкапит существующий default-конфиг
#    2. Копирует наши server-блоки из infra/staging/nginx/sites-available/
#    3. Создаёт symlinks в /etc/nginx/sites-enabled/
#    4. Удаляет default (если он мешает)
#    5. Готовит директорию /var/www/certbot для http-challenge SSL
#    6. nginx -t (проверка) → systemctl reload
#
#  Запускать на сервере как root (без аргументов):
#      bash setup-host-nginx.sh
#
#  Идемпотентен:
#    - Бэкап делается только если ещё не было
#    - Конфиги перезаписываются (можно править и перезапускать)
#    - Symlinks обновляются
#
#  ВАЖНО:
#    - DNS-записи должны уже распространиться (5 поддоменов → 77.222.32.244)
#    - Этот скрипт настраивает только HTTP (порт 80).
#      SSL ставится отдельным шагом — setup-ssl.sh.
#    - Контейнеры на 8080-8084 ещё не запущены → upstream вернёт 502,
#      это норма до следующего PR (docker-compose.staging.yml).
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
NGINX_SITES_AVAILABLE="/etc/nginx/sites-available"
NGINX_SITES_ENABLED="/etc/nginx/sites-enabled"
BACKUP_DIR="/etc/nginx/backups/$(date +%Y%m%d-%H%M%S)"
CERTBOT_WEBROOT="/var/www/certbot"

SITES=(
    "staging.spaceofjoy.ru"
    "api.staging.spaceofjoy.ru"
    "vhod.staging.spaceofjoy.ru"
    "pma.staging.spaceofjoy.ru"
    "mail.staging.spaceofjoy.ru"
    "rabbitmq.staging.spaceofjoy.ru"
)
# Примечание: на ЖИВОМ сервере этот скрипт повторно НЕ гонять — он перезапишет
# конфиги всех доменов и сотрёт 443-блоки certbot. Добавить ОДИН домен на живую —
# через setup-rabbitmq-subdomain.sh (точечно, не трогает остальные).

log()  { echo -e "\033[1;34m[nginx]\033[0m $*"; }
warn() { echo -e "\033[1;33m[warn]\033[0m  $*" >&2; }
err()  { echo -e "\033[1;31m[err]\033[0m   $*" >&2; exit 1; }

# ─── Sanity ───────────────────────────────────────────────────────────────────
[[ "$EUID" -eq 0 ]] || err "Запускать от root"
command -v nginx >/dev/null || err "nginx не установлен"

# Проверка что скрипт запускается из infra/staging/ (или содержит nginx/sites-available)
CONF_SRC_DIR="${SCRIPT_DIR}/nginx/sites-available"
if [[ ! -d "$CONF_SRC_DIR" ]]; then
    err "Не найдена директория с конфигами: $CONF_SRC_DIR. Скопируй infra/staging целиком на сервер."
fi

# ─── 1. Бэкап ─────────────────────────────────────────────────────────────────
log "Создаю бэкап текущей конфигурации в $BACKUP_DIR"
mkdir -p "$BACKUP_DIR"
cp -a "$NGINX_SITES_AVAILABLE" "$BACKUP_DIR/sites-available" 2>/dev/null || true
cp -a "$NGINX_SITES_ENABLED"   "$BACKUP_DIR/sites-enabled"   2>/dev/null || true

# ─── 2. Создать webroot для certbot ──────────────────────────────────────────
log "Создаю $CERTBOT_WEBROOT для будущего ACME-challenge"
mkdir -p "$CERTBOT_WEBROOT/.well-known/acme-challenge"
chown -R www-data:www-data "$CERTBOT_WEBROOT"

# ─── 3. Копируем конфиги ──────────────────────────────────────────────────────
log "Копирую server-блоки в $NGINX_SITES_AVAILABLE"
for site in "${SITES[@]}"; do
    src="$CONF_SRC_DIR/${site}.conf"
    dst="$NGINX_SITES_AVAILABLE/${site}.conf"
    if [[ ! -f "$src" ]]; then
        err "Нет конфига: $src"
    fi
    install -m 644 "$src" "$dst"
    log "  ✓ $dst"
done

# ─── 4. Symlinks в sites-enabled ──────────────────────────────────────────────
log "Активирую сайты (symlinks в $NGINX_SITES_ENABLED)"
for site in "${SITES[@]}"; do
    target="$NGINX_SITES_AVAILABLE/${site}.conf"
    link="$NGINX_SITES_ENABLED/${site}.conf"
    if [[ -L "$link" ]]; then
        rm -f "$link"
    fi
    ln -s "$target" "$link"
    log "  ✓ $link"
done

# ─── 5. Удалить default-конфиг (если есть) ────────────────────────────────────
DEFAULT_LINK="$NGINX_SITES_ENABLED/default"
if [[ -L "$DEFAULT_LINK" ]] || [[ -f "$DEFAULT_LINK" ]]; then
    log "Удаляю default-конфиг (заглушку 'Welcome to nginx!')"
    rm -f "$DEFAULT_LINK"
fi

# ─── 6. Создать пустой htpasswd-stub (чтобы nginx -t прошёл до setup-basic-auth) ─
HTPASSWD_DIR="/etc/nginx/auth"
HTPASSWD_FILE="$HTPASSWD_DIR/staging-tools.htpasswd"
if [[ ! -f "$HTPASSWD_FILE" ]]; then
    log "Создаю пустой htpasswd-stub (заполнится через setup-basic-auth.sh)"
    install -d -m 755 "$HTPASSWD_DIR"
    : > "$HTPASSWD_FILE"
    chown root:www-data "$HTPASSWD_FILE"
    chmod 640 "$HTPASSWD_FILE"
    warn "phpMyAdmin и Mailpit недоступны до запуска setup-basic-auth.sh (пустой пароль = всех блочит)"
fi

# ─── 7. Проверка nginx -t и reload ────────────────────────────────────────────
log "Проверка конфигурации nginx -t…"
if ! nginx -t 2>&1; then
    err "nginx -t упал. Откатывай из $BACKUP_DIR или правь конфиги."
fi

log "Reload nginx…"
systemctl reload nginx
log "✓ nginx перезагружен"

# ─── 8. Итог ──────────────────────────────────────────────────────────────────
echo ""
log "════════════════════════════════════════════════════════"
log "  Host nginx настроен как reverse proxy."
log "════════════════════════════════════════════════════════"
echo ""
echo "  Активные сайты:"
for site in "${SITES[@]}"; do
    echo "    http://$site/"
done
echo ""
echo "  Бэкап старой конфигурации:  $BACKUP_DIR"
echo ""
echo "  Следующие шаги:"
echo "    1. bash setup-basic-auth.sh             # пароль для pma и mail"
echo "    2. bash setup-ssl.sh                    # Let's Encrypt SSL"
echo "    3. Развернуть docker compose staging   # следующий PR — контейнеры на 8080-8084"
echo ""
echo "  Сейчас все 5 поддоменов возвращают 502 Bad Gateway —"
echo "  это нормально, контейнеры ещё не запущены."
