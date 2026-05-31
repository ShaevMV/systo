#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  setup-runner.sh
#  Устанавливает self-hosted GitHub Actions runner на staging-сервере.
#
#  Runner работает от имени пользователя `deploy` (создан скриптом
#  setup-deploy-user.sh). Регистрируется как systemd service —
#  автозапуск после ребута.
#
#  Запускать на сервере КАК ROOT (нужно для установки systemd unit):
#      bash setup-runner.sh <REPO_URL> <REGISTRATION_TOKEN> [LABEL]
#
#  Где:
#    REPO_URL           — https://github.com/ShaevMV/systo
#    REGISTRATION_TOKEN — одноразовый токен, валиден ~1 час.
#                         Получить:
#                           - UI: Settings → Actions → Runners → New self-hosted runner
#                           - CLI: gh api -X POST repos/<owner>/<repo>/actions/runners/registration-token --jq .token
#    LABEL              — default "staging" (плюс автоматически self-hosted/linux/x64)
#
#  Пример запуска с локальной машины (одной командой):
#      TOKEN=$(gh api -X POST repos/ShaevMV/systo/actions/runners/registration-token --jq .token)
#      scp infra/staging/setup-runner.sh root@77.222.32.244:/tmp/
#      ssh root@77.222.32.244 "bash /tmp/setup-runner.sh https://github.com/ShaevMV/systo '$TOKEN'"
#
#  Идемпотентен:
#   - повторный запуск с новым токеном перерегистрирует runner с тем же именем
#   - systemd service обновляется без ошибок
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

REPO_URL="${1:-}"
REG_TOKEN="${2:-}"
LABEL="${3:-staging}"
RUNNER_VERSION="${RUNNER_VERSION:-2.334.0}"
RUNNER_NAME="${RUNNER_NAME:-staging-systo}"

DEPLOY_USER="deploy"
DEPLOY_HOME="/home/${DEPLOY_USER}"
RUNNER_DIR="${DEPLOY_HOME}/actions-runner"
ARCH="linux-x64"

log()  { echo -e "\033[1;34m[runner]\033[0m $*"; }
warn() { echo -e "\033[1;33m[warn]\033[0m   $*" >&2; }
err()  { echo -e "\033[1;31m[err]\033[0m    $*" >&2; exit 1; }

# ─── Sanity ───────────────────────────────────────────────────────────────────
[[ "$EUID" -eq 0 ]] || err "Запускать от root (нужно для systemd-сервиса)"
id "$DEPLOY_USER" >/dev/null 2>&1 || err "Пользователя '$DEPLOY_USER' нет — сначала запусти setup-deploy-user.sh"
[[ -n "$REPO_URL"  ]] || err "Не передан REPO_URL (1-й аргумент)"
[[ -n "$REG_TOKEN" ]] || err "Не передан REGISTRATION_TOKEN (2-й аргумент). См. README §Runner."
[[ "$REPO_URL" =~ ^https://github\.com/ ]] || err "REPO_URL должен начинаться с https://github.com/ (получено: $REPO_URL)"

# ─── 1. Зависимости ───────────────────────────────────────────────────────────
log "Проверяю зависимости…"
MISSING=()
for cmd in curl tar systemctl; do
    command -v "$cmd" >/dev/null 2>&1 || MISSING+=("$cmd")
done
if [[ ${#MISSING[@]} -gt 0 ]]; then
    log "Устанавливаю недостающее: ${MISSING[*]}"
    apt-get update -qq
    apt-get install -y -qq "${MISSING[@]}"
fi

# .NET / runtime зависимости для runner (актуально для Ubuntu 24.04)
# Список из официальной доки: https://github.com/actions/runner/blob/main/docs/start/envlinux.md
log "Устанавливаю runtime-зависимости runner (если не установлены)…"
apt-get install -y -qq \
    libicu74 \
    libssl3 \
    libstdc++6 \
    zlib1g \
    2>/dev/null || warn "Не все пакеты установились — на 24.04 может потребоваться libicu74"

# ─── 2. Скачать runner ────────────────────────────────────────────────────────
log "Готовлю директорию ${RUNNER_DIR}…"
install -d -o "$DEPLOY_USER" -g "$DEPLOY_USER" "$RUNNER_DIR"
cd "$RUNNER_DIR"

TARBALL="actions-runner-${ARCH}-${RUNNER_VERSION}.tar.gz"
URL="https://github.com/actions/runner/releases/download/v${RUNNER_VERSION}/${TARBALL}"

if [[ -f "config.sh" ]]; then
    log "✓ Runner уже распакован — пропускаю скачивание"
else
    log "Скачиваю ${TARBALL}…"
    sudo -u "$DEPLOY_USER" curl -fsSL -o "$TARBALL" "$URL"
    log "Распаковываю…"
    sudo -u "$DEPLOY_USER" tar xzf "$TARBALL"
    sudo -u "$DEPLOY_USER" rm -f "$TARBALL"
    log "✓ Runner распакован"
fi

# ─── 3. Остановить старый сервис (если был) ───────────────────────────────────
if [[ -f /etc/systemd/system/actions.runner.*.service ]] 2>/dev/null \
   || systemctl list-units --all --type=service 2>/dev/null | grep -q "actions.runner"; then
    OLD_SVC="$(systemctl list-units --all --type=service --no-pager --no-legend 2>/dev/null | grep -oE 'actions\.runner\.[^ ]+\.service' | head -1 || true)"
    if [[ -n "$OLD_SVC" ]]; then
        log "Останавливаю старый сервис $OLD_SVC (для переконфигурации)…"
        systemctl stop "$OLD_SVC" 2>/dev/null || true
    fi
fi

# Сбросить старую регистрацию (если была)
if [[ -f "$RUNNER_DIR/.runner" ]]; then
    log "Удаляю старую регистрацию…"
    sudo -u "$DEPLOY_USER" "$RUNNER_DIR/config.sh" remove --token "$REG_TOKEN" 2>/dev/null \
        || warn "Не смог снять старую регистрацию через API (возможно токен другой). Удаляю файлы."
    rm -f "$RUNNER_DIR/.runner" "$RUNNER_DIR/.credentials" "$RUNNER_DIR/.credentials_rsaparams"
fi

# ─── 4. Зарегистрировать ──────────────────────────────────────────────────────
log "Регистрирую runner '${RUNNER_NAME}' с лейблами 'self-hosted,linux,x64,${LABEL}'…"
sudo -u "$DEPLOY_USER" "$RUNNER_DIR/config.sh" \
    --url "$REPO_URL" \
    --token "$REG_TOKEN" \
    --name "$RUNNER_NAME" \
    --labels "$LABEL" \
    --work "_work" \
    --unattended \
    --replace
log "✓ Runner зарегистрирован"

# ─── 5. Установить systemd-сервис ─────────────────────────────────────────────
log "Устанавливаю systemd-сервис (от имени root)…"
cd "$RUNNER_DIR"
./svc.sh install "$DEPLOY_USER"
./svc.sh start
sleep 2
./svc.sh status

# ─── 6. Итог ──────────────────────────────────────────────────────────────────
echo ""
log "════════════════════════════════════════════════════════"
log "  Runner установлен и запущен."
log "════════════════════════════════════════════════════════"
echo ""
echo "  Версия runner:    $RUNNER_VERSION"
echo "  Имя:              $RUNNER_NAME"
echo "  Лейблы:           self-hosted, linux, x64, $LABEL"
echo "  Директория:       $RUNNER_DIR"
echo "  Под пользователем:$DEPLOY_USER"
echo ""
echo "  Проверь в GitHub UI:"
echo "    Settings → Actions → Runners → должен появиться '$RUNNER_NAME' (Idle)"
echo ""
echo "  Управление сервисом:"
echo "    sudo systemctl status  actions.runner.*.service"
echo "    sudo systemctl restart actions.runner.*.service"
echo "    sudo journalctl -u     actions.runner.*.service -f"
