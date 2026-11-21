#!/bin/sh
set -eu

port="${PORT:-10000}"
if [ -n "${APP_KEY:-}" ] && [ "${APP_KEY#base64:}" = "${APP_KEY}" ]; then
    export APP_KEY="base64:${APP_KEY}"
fi

sed -i "s/Listen 80/Listen ${port}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

php artisan migrate --force

exec apache2-foreground
