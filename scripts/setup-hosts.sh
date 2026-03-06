#!/bin/bash

# Скрипт для добавления локальных доменов в /etc/hosts
# Использование: ./scripts/setup-hosts.sh

HOSTS_FILE="/etc/hosts"
DOMAINS=(
    "127.0.0.1  api.tickets.loc"
    "127.0.0.1  org.tickets.loc"
    "127.0.0.1  drug.tickets.loc"
    "127.0.0.1  baza.tickets.loc"
    "127.0.0.1  friendly.tickets.loc"
)

echo "=== Настройка локальных доменов для systo ==="
echo ""

# Проверка прав root
if [ "$EUID" -ne 0 ]; then 
    echo "Ошибка: скрипт требует прав root"
    echo "Запустите: sudo ./scripts/setup-hosts.sh"
    exit 1
fi

echo "Добавление доменов в $HOSTS_FILE..."
echo ""

for domain in "${DOMAINS[@]}"; do
    if ! grep -qF "$domain" "$HOSTS_FILE"; then
        echo "$domain" >> "$HOSTS_FILE"
        echo "✓ Добавлено: $domain"
    else
        echo "✓ Уже существует: $domain"
    fi
done

echo ""
echo "=== Готово! ==="
echo ""
echo "Доступные домены:"
echo "  - http://api.tickets.loc"
echo "  - http://org.tickets.loc"
echo "  - http://drug.tickets.loc"
echo "  - http://baza.tickets.loc"
echo "  - http://friendly.tickets.loc"
echo ""
