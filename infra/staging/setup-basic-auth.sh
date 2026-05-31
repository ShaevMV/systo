#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  setup-basic-auth.sh
#  Создаёт пользователя в /etc/nginx/auth/staging-tools.htpasswd
#  для защиты phpMyAdmin и Mailpit.
#
#  Запускать на сервере как root:
#      bash setup-basic-auth.sh [USERNAME]
#
#  Если USERNAME не передан — спросит интерактивно.
#  Пароль читается из stdin (без эха).
#
#  Идемпотентен — можно перезапускать для смены пароля или добавления юзера.
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

HTPASSWD_DIR="/etc/nginx/auth"
HTPASSWD_FILE="$HTPASSWD_DIR/staging-tools.htpasswd"

log()  { echo -e "\033[1;34m[auth]\033[0m $*"; }
err()  { echo -e "\033[1;31m[err]\033[0m  $*" >&2; exit 1; }

# ─── Sanity ───────────────────────────────────────────────────────────────────
[[ "$EUID" -eq 0 ]] || err "Запускать от root"

# Установить apache2-utils (даёт htpasswd) если ещё нет
if ! command -v htpasswd >/dev/null; then
    log "Устанавливаю apache2-utils (для команды htpasswd)…"
    apt-get update -qq
    apt-get install -y -qq apache2-utils
fi

# ─── Запросить логин/пароль ───────────────────────────────────────────────────
USERNAME="${1:-}"
if [[ -z "$USERNAME" ]]; then
    read -rp "Логин для basic auth: " USERNAME
fi
[[ -n "$USERNAME" ]] || err "Логин не может быть пустым"
[[ "$USERNAME" =~ ^[a-zA-Z0-9_-]+$ ]] || err "Логин может содержать только a-z, A-Z, 0-9, _, -"

# ─── Создать или обновить ─────────────────────────────────────────────────────
install -d -m 755 "$HTPASSWD_DIR"

if [[ -f "$HTPASSWD_FILE" ]] && grep -q "^${USERNAME}:" "$HTPASSWD_FILE"; then
    log "Пользователь '$USERNAME' уже есть — обновляю пароль"
else
    log "Добавляю нового пользователя '$USERNAME'"
fi

# -B = bcrypt (сильнее crypt по умолчанию), -c = создать файл если нет
if [[ -f "$HTPASSWD_FILE" ]] && [[ -s "$HTPASSWD_FILE" ]]; then
    htpasswd -B "$HTPASSWD_FILE" "$USERNAME"
else
    htpasswd -B -c "$HTPASSWD_FILE" "$USERNAME"
fi

chown root:www-data "$HTPASSWD_FILE"
chmod 640 "$HTPASSWD_FILE"

log "✓ htpasswd обновлён: $HTPASSWD_FILE"

# Reload nginx чтобы новый htpasswd подхватился (обычно не нужен, но на всякий)
if systemctl is-active --quiet nginx; then
    systemctl reload nginx
    log "✓ nginx reloaded"
fi

echo ""
log "Готово."
echo ""
echo "  Файл:           $HTPASSWD_FILE"
echo "  Пользователей:  $(wc -l < "$HTPASSWD_FILE")"
echo ""
echo "  Тест:"
echo "    curl -u $USERNAME http://pma.staging.spaceofjoy.ru/"
echo "    curl -u $USERNAME http://mail.staging.spaceofjoy.ru/"
