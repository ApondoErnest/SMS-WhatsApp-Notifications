#!/bin/sh
set -e

cd /var/www/html

mkdir -p \
  storage/app/public \
  storage/app/backups/database \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwx storage bootstrap/cache || true

# Optional one-shot optimize when APP_KEY is present (ignore failures on first boot).
if [ -n "${APP_KEY:-}" ]; then
  php artisan package:discover --ansi >/dev/null 2>&1 || true
  php artisan config:cache >/dev/null 2>&1 || true
  php artisan route:cache >/dev/null 2>&1 || true
  php artisan view:cache >/dev/null 2>&1 || true
fi

exec "$@"
