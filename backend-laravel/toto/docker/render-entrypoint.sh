#!/bin/sh
set -e

cd /var/www/html

php artisan config:clear || true
php artisan storage:link || true

# Migrations must succeed before seeding (do not swallow errors)
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  echo "==> Running migrations..."
  php artisan migrate --force
fi

if [ "${RUN_SEED:-false}" = "true" ]; then
  echo "==> Running database seed..."
  php artisan db:seed --force --class=DatabaseSeeder
fi

php artisan config:cache || true
php artisan route:cache || true

PORT="${PORT:-10000}"
exec php artisan serve --host=0.0.0.0 --port="$PORT"
