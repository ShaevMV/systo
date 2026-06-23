#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  setup-docker-log-rotation.sh — ротация логов Docker (фаза 1 мониторинг-плана)
#
#  ЗАЧЕМ. По умолчанию docker пишет логи контейнеров драйвером json-file БЕЗ
#  ограничения размера → файлы растут бесконтрольно и съедают диск. На staging
#  свободно ~9.5 GB, а у RabbitMQ disk_free_limit.absolute = 1GB: при падении
#  свободного места ниже порога брокер БЛОКИРУЕТ публикаторов → встанет приём
#  заказов от qr. Этот скрипт включает ротацию (max-size 10m, max-file 3) —
#  закрывает риск TD-8 в части, критичной для брокера.
#
#  СВОЙСТВА. Идемпотентный (повторный запуск безопасен; детект «уже настроено»
#  структурно через jq). Не теряет существующие ключи daemon.json (бэкап + jq-мерж).
#  Не затирает молча чужой log-driver (спрашивает подтверждение). Перезапуск
#  docker (нужен, чтобы настройка применилась) — ТОЛЬКО с подтверждения оператора
#  с терминала (/dev/tty); без TTY рестарт пропускается с инструкцией. Перезапуск
#  пересоздаёт контейнеры (поднимутся по restart-policy, короткий простой стенда).
#
#  ЗАПУСК (на сервере staging, штатно — НЕ через pipe):
#     sudo bash infra/staging/setup-docker-log-rotation.sh
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

DAEMON_JSON="/etc/docker/daemon.json"
MAX_SIZE="10m"
MAX_FILE="3"

have_jq() { command -v jq >/dev/null 2>&1; }

# Подтверждение строго с терминала (/dev/tty), чтобы не сломаться при запуске
# через pipe (stdin занят телом скрипта). Нет TTY → возвращаем «нет».
confirm() {
  if [[ -e /dev/tty ]]; then
    local a=""
    read -r -p "$1 [y/N] " a </dev/tty || a="n"
    [[ "${a}" =~ ^[yY]([eE][sS])?$ ]]
  else
    return 1
  fi
}

if [[ "${EUID}" -ne 0 ]]; then
  echo "❌ Запусти от root (sudo) — пишем в /etc/docker/daemon.json и рестартим docker."
  exit 1
fi

echo "▶ Настройка ротации docker-логов (json-file: max-size=${MAX_SIZE}, max-file=${MAX_FILE})"
mkdir -p /etc/docker

ALREADY_SET=0

# ── 1. Сформировать/обновить daemon.json, не теряя существующие ключи ─────────
if [[ -f "${DAEMON_JSON}" ]]; then
  # Структурный детект «ротация уже есть» (точнее, чем grep по строкам).
  if have_jq && jq -e '."log-opts"."max-size"' "${DAEMON_JSON}" >/dev/null 2>&1; then
    echo "✓ Ротация уже настроена в ${DAEMON_JSON} (log-opts.max-size задан) — пропускаю запись."
    ALREADY_SET=1
  elif ! have_jq && grep -q '"max-size"' "${DAEMON_JSON}"; then
    echo "✓ Похоже, ротация уже настроена (найден max-size; jq нет для точной проверки) — пропускаю."
    ALREADY_SET=1
  else
    # Нужно добавить ротацию в существующий файл.
    if ! have_jq; then
      echo "⚠ ${DAEMON_JSON} уже существует, а jq не установлен — не рискую перезаписать его вслепую."
      echo "  Установи jq (apt-get install -y jq) и перезапусти, ЛИБО добавь вручную в ${DAEMON_JSON}:"
      echo "    \"log-driver\": \"json-file\","
      echo "    \"log-opts\": { \"max-size\": \"${MAX_SIZE}\", \"max-file\": \"${MAX_FILE}\" }"
      exit 1
    fi

    # Гард: не затирать чужой драйвер логов молча.
    CUR_DRIVER="$(jq -r '."log-driver" // empty' "${DAEMON_JSON}")"
    if [[ -n "${CUR_DRIVER}" && "${CUR_DRIVER}" != "json-file" ]]; then
      echo "⚠ В ${DAEMON_JSON} уже задан log-driver = '${CUR_DRIVER}' (не json-file)."
      echo "  Скрипт настраивает ротацию для json-file — это перезапишет драйвер."
      if ! confirm "Всё равно переключить на json-file с ротацией?"; then
        echo "↷ Отменено — драйвер не тронут."
        exit 0
      fi
    fi

    BACKUP="${DAEMON_JSON}.bak.$(date +%Y%m%d%H%M%S)"
    cp "${DAEMON_JSON}" "${BACKUP}"
    echo "  Бэкап текущего конфига → ${BACKUP}"
    tmp="$(mktemp)"
    # Мерж: добавляем log-driver/log-opts, остальные ключи (registry-mirrors и пр.) сохраняем.
    jq --arg ms "${MAX_SIZE}" --arg mf "${MAX_FILE}" \
      '. + {"log-driver":"json-file","log-opts":{"max-size":$ms,"max-file":$mf}}' \
      "${DAEMON_JSON}" > "${tmp}"
    mv "${tmp}" "${DAEMON_JSON}"
    echo "  Ключи log-driver/log-opts добавлены через jq (остальные ключи сохранены)."
  fi
else
  cat > "${DAEMON_JSON}" <<EOF
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "${MAX_SIZE}",
    "max-file": "${MAX_FILE}"
  }
}
EOF
  echo "  Создан ${DAEMON_JSON} с ротацией логов."
fi

# Валидация итогового JSON, чтобы не уронить docker битым конфигом.
if have_jq; then
  jq empty "${DAEMON_JSON}" || { echo "❌ ${DAEMON_JSON} не валидный JSON — проверь вручную."; exit 1; }
else
  echo "ℹ jq не найден — пропускаю валидацию JSON (созданный heredoc статичен и валиден)."
fi

# ── 2. Подсказка по освобождению места (НЕ выполняем автоматически) ───────────
echo
echo "ℹ Полезно вручную освободить место (build cache ~1.9 GB на staging):"
echo "    docker system df            # посмотреть, что занято"
echo "    docker builder prune -f     # удалить кеш сборки (безопасно)"
echo "  Ротация PDF-билетов (storage/app/public/tickets) — отдельной задачей."
echo

# ── 3. Перезапуск docker — только с подтверждения с терминала ─────────────────
if [[ "${ALREADY_SET}" -eq 1 ]]; then
  echo "✓ Изменений нет — перезапуск не требуется."
  exit 0
fi

echo "⚠ Настройка применится только после ПЕРЕЗАПУСКА docker."
echo "  Это пересоздаст контейнеры (поднимутся по restart-policy, короткий простой стенда)."
if confirm "Перезапустить docker сейчас?"; then
  systemctl restart docker
  echo "✓ docker перезапущен. Ротация логов активна."
  echo "  Проверка: docker inspect -f '{{.HostConfig.LogConfig}}' rabbitmq-staging"
else
  echo "↷ Перезапуск пропущен (нет подтверждения/TTY)."
  echo "  Применить вручную: sudo systemctl restart docker"
fi
