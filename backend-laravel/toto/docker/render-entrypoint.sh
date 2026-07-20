#!/bin/sh
set -e

cd /var/www/html

php artisan config:clear || true
php artisan storage:link || true

# One-shot: wipe Neon/Postgres and recreate schema (set DB_FRESH=true once, then false)
if [ "${DB_FRESH:-false}" = "true" ]; then
  echo "==> DB_FRESH=true — migrate:fresh --seed"
  php artisan migrate:fresh --force --seed --seeder=DatabaseSeeder
elif [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  echo "==> Running migrations..."
  php artisan migrate --force
  if [ "${RUN_SEED:-false}" = "true" ]; then
    echo "==> Running database seed..."
    php artisan db:seed --force --class=DatabaseSeeder
  fi
fi

php artisan config:cache || true
php artisan route:cache || true

PORT="${PORT:-10000}"
exec php artisan serve --host=0.0.0.0 --port="$PORT"
