#!/usr/bin/env bash
# Запуск отчёта «сколько закупить» внутри контейнера php-fpm.
#
# Примеры:
#   ./replenish.sh 568922 40                 # товар 568922, срок поставки 40 дней
#   ./replenish.sh 568922 40 --period=90      # окно продаж 90 дней вместо 180
#   ./replenish.sh 568922 40 --json           # вывод в JSON
#
set -euo pipefail

CONTAINER=laradock-php-fpm-1

if [ "$#" -lt 2 ]; then
    echo "Использование: $0 <GOODSCODE> <срок_поставки_дней> [доп. флаги]"
    echo "Пример:        $0 568922 40"
    exit 1
fi

GOOD="$1"
LEAD="$2"
shift 2

docker exec "$CONTAINER" bash -lc \
    "cd /var/www && php artisan report:replenish $GOOD --lead=$LEAD $* 2>&1 | grep -v Xdebug"
