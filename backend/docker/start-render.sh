#!/bin/sh
set -eu

render_port="${PORT:-10000}"

sed -ri "s/^Listen [0-9]+$/Listen ${render_port}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${render_port}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

php artisan storage:link --force
php artisan config:cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

# Render's web service only starts this container command. Keep Laravel's
# scheduler running alongside Apache so due reminders and notification
# campaigns are dispatched without requiring a separate cron service.
php artisan schedule:work --no-interaction &

exec apache2-foreground
