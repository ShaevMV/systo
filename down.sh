#!/usr/bin/env bash
#
# Остановить локальное dev-окружение Systo.
# Аргументы пробрасываются в docker-compose down — например:
#   ./down.sh                       # просто остановить
#   ./down.sh --remove-orphans      # + удалить orphan-контейнеры
#   ./down.sh -v                    # + удалить volumes (ОСТОРОЖНО — снесёт БД!)

set -euo pipefail

cd "$(dirname "$(realpath "$0")")"

docker-compose down "$@"
