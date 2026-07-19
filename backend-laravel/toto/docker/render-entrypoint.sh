#!/bin/sh
set -e

cd /var/www/html

php artisan config:clear || true
php artisan storage:link || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force || true
fi

if [ "${RUN_SEED:-false}" = "true" ]; then
  php artisan db:seed --force --class=DatabaseSeeder || true
fi

php artisan config:cache || true
php artisan route:cache || true

PORT="${PORT:-10000}"
exec php artisan serve --host=0.0.0.0 --port="$PORT"
