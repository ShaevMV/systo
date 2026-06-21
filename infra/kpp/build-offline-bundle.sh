#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  Сборка офлайн-бандла узла КПП (Ф5, PR-9) — ОПЦИОНАЛЬНО (облако-мастер).
#
#  Готовит всё для поднятия аварийного локального узла БЕЗ интернета:
#   - dist BazaFront (собранный с --base=/baza/),
#   - код Baza (Laravel API-ядро),
#   - минимизированный дамп БД (билеты/смены, B5),
#   - docker-образы (php/mysql/nginx) через `docker save`.
#  Результат — каталог ./bundle + образы *.tar, переносится на ноут узла.
#
#  ⚠️ Скелет: пути/образы/дамп доводятся под реальное окружение (см. README.md).
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

HERE="$(cd "$(dirname "$0")" && pwd)"
ROOT="$(cd "$HERE/../.." && pwd)"
BUNDLE="$HERE/bundle"

echo "▸ Сборка офлайн-бандла КПП → $BUNDLE"
mkdir -p "$BUNDLE/baza-front" "$BUNDLE/baza"

# 1) BazaFront dist (--base=/baza/)
echo "▸ Сборка BazaFront (dist)…"
( cd "$ROOT/BazaFront" && npm ci --include=dev && npm run build -- --base=/baza/ )
cp -r "$ROOT/BazaFront/dist/." "$BUNDLE/baza-front/"

# 2) Код Baza (без vendor-разработки — production deps ставятся в образе)
echo "▸ Копирование кода Baza…"
rsync -a --delete \
  --exclude 'node_modules' --exclude 'tests' --exclude '.git' \
  "$ROOT/Baza/." "$BUNDLE/baza/"

# 3) Дамп БД (минимизированный, B5) — ЗАГЛУШКА: подставить реальный источник.
#    На боевом: mysqldump только нужных таблиц (el_tickets/spisok/live/auto/changes/
#    ticket_search/baza_blacklist) с маскировкой лишних ПДн. См. TODO в README.
if [ ! -f "$BUNDLE/baza-dump.sql" ]; then
  echo "-- TODO: подставить минимизированный дамп билетов/смен (B5)" > "$BUNDLE/baza-dump.sql"
  echo "⚠ baza-dump.sql — заглушка. Подставьте реальный минимизированный дамп."
fi

# 4) docker save образов (если собраны)
echo "▸ Экспорт docker-образов (если есть)…"
for img in kpp-baza-php:offline mysql:8.0 nginx:alpine; do
  if docker image inspect "$img" >/dev/null 2>&1; then
    fname="$BUNDLE/$(echo "$img" | tr '/:' '__').tar"
    docker save "$img" -o "$fname"
    echo "  saved $img → $fname"
  else
    echo "  ⚠ образ $img не найден — соберите/спуллите заранее"
  fi
done

echo ""
echo "✓ Бандл собран: $BUNDLE"
echo "  Перенесите каталог infra/kpp на ноут узла, затем docker load *.tar + up (см. README.md)."
