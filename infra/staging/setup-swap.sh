#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  setup-swap.sh
#  Создаёт swap-файл для staging-сервера (опционально).
#
#  На staging с 1.9 Gi RAM при сборке npm build + одновременно запущенных
#  Docker-контейнерах легко получить OOM-kill. Swap 4 ГБ снимает риск ценой
#  диска (которого на /dev/vda1 24 GB свободно — хватит с большим запасом).
#
#  Идемпотентен — если /swapfile уже есть и подключён, ничего не делает.
#
#  Запускать на сервере как root:
#      bash setup-swap.sh [SIZE_GB]
#  По умолчанию 4 ГБ. Минимум 1, максимум 16 (sanity).
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

SIZE_GB="${1:-4}"
SWAPFILE="/swapfile"

log()  { echo -e "\033[1;34m[swap]\033[0m $*"; }
err()  { echo -e "\033[1;31m[err]\033[0m  $*" >&2; exit 1; }

[[ "$EUID" -eq 0 ]] || err "Запускать от root"

if ! [[ "$SIZE_GB" =~ ^[0-9]+$ ]] || (( SIZE_GB < 1 || SIZE_GB > 16 )); then
    err "Размер должен быть 1..16 GB (получено: '$SIZE_GB')"
fi

# Уже подключён?
if swapon --show=NAME --noheadings | grep -qx "$SWAPFILE"; then
    log "✓ $SWAPFILE уже активен — пропускаю"
    swapon --show
    exit 0
fi

# Проверка свободного места: нужно SIZE_GB + 1 GB запас
FREE_KB="$(df --output=avail / | tail -1)"
NEED_KB=$(( (SIZE_GB + 1) * 1024 * 1024 ))
if (( FREE_KB < NEED_KB )); then
    err "Недостаточно места: нужно ${SIZE_GB}GB+1GB, доступно $((FREE_KB / 1024 / 1024))GB"
fi

if [[ -f "$SWAPFILE" ]]; then
    log "Файл $SWAPFILE существует, но не активен. Удаляю старый."
    rm -f "$SWAPFILE"
fi

log "Создаю swap ${SIZE_GB}G в $SWAPFILE (fallocate)…"
fallocate -l "${SIZE_GB}G" "$SWAPFILE"
chmod 600 "$SWAPFILE"
mkswap "$SWAPFILE" >/dev/null
swapon "$SWAPFILE"
log "✓ Swap включён"

# Постоянное подключение через fstab
if ! grep -q "^$SWAPFILE " /etc/fstab; then
    echo "$SWAPFILE  none  swap  sw  0  0" >> /etc/fstab
    log "✓ Добавил запись в /etc/fstab"
fi

# Тюнинг — на сервере с малой RAM лучше swappiness=10 (использовать swap только при нужде)
if [[ -f /etc/sysctl.conf ]] && ! grep -q "^vm.swappiness" /etc/sysctl.conf; then
    echo "vm.swappiness=10" >> /etc/sysctl.conf
    sysctl vm.swappiness=10 >/dev/null
    log "✓ vm.swappiness=10 (минимизировать использование swap)"
fi

log "Итог:"
free -h | head -3
swapon --show
