#!/usr/bin/env bash
#
# ВелоТОР — обёртка для планировщика Laravel под shared-хостинг (CloudLinux/cPanel).
#
# Зачем: у cron минимальный PATH, а хостинг может оборачивать команду в `nice`,
# поэтому жёстко прописанный в crontab путь к PHP часто «No such file or directory».
# Здесь мы один раз находим абсолютный путь к PHP CLI и запускаем `schedule:run`.
#
# Установка в cron (одна строка, КАЖДУЮ минуту):
#   * * * * * /bin/bash /home/socialny/velotor.com.ua/ride/schedule-cron.sh >> /home/socialny/velotor.com.ua/ride/storage/logs/schedule.log 2>&1
#
# Вызов через `/bin/bash <путь>` не требует бита +x на файле (важно, т.к. файл
# приезжает из git с Windows).

# Всегда работаем из корня проекта (там, где лежит этот скрипт и artisan).
cd "$(dirname "$0")" || exit 1

# 1) Если знаете точный путь — впишите его сюда, и поиск ниже пропустится.
#    Пример: PHP_BIN="/opt/alt/php83/usr/bin/php"
PHP_BIN="${PHP_BIN:-}"

# 2) Иначе — перебираем типовые расположения PHP на shared-хостингах.
if [ -z "$PHP_BIN" ]; then
    for candidate in \
        /opt/alt/php83/usr/bin/php \
        /opt/alt/php82/usr/bin/php \
        /opt/alt/php81/usr/bin/php \
        /opt/cpanel/ea-php83/root/usr/bin/php \
        /opt/cpanel/ea-php82/root/usr/bin/php \
        /opt/cpanel/ea-php81/root/usr/bin/php \
        /usr/local/bin/php \
        /usr/bin/php \
        "$(command -v php 2>/dev/null)"; do
        if [ -n "$candidate" ] && [ -x "$candidate" ]; then
            PHP_BIN="$candidate"
            break
        fi
    done
fi

if [ -z "$PHP_BIN" ]; then
    echo "$(date '+%F %T') ВелоТОР cron: не знайдено PHP CLI бінарник" >&2
    exit 1
fi

exec "$PHP_BIN" artisan schedule:run
