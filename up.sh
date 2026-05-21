#!/usr/bin/env bash
#
# Поднять локальное dev-окружение Systo.
# Все аргументы пробрасываются в docker-compose — например:
#   ./up.sh                       # просто поднять
#   ./up.sh --build               # с пересборкой образов
#   ./up.sh --build --remove-orphans
#
# После старта выводится статус контейнеров.

set -euo pipefail

cd "$(dirname "$(realpath "$0")")"

docker-compose up -d "$@"

echo
echo "=== Статус контейнеров ==="
docker-compose ps
