# Backend Laravel — AutoChain Emma+ (Render)
FROM php:8.2-cli-bookworm

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev libpng-dev libgmp-dev \
    && docker-php-ext-install pdo_pgsql pgsql zip gd bcmath gmp \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi || true \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/public bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/render-entrypoint.sh /usr/local/bin/render-entrypoint.sh
RUN chmod +x /usr/local/bin/render-entrypoint.sh

EXPOSE 10000
ENTRYPOINT ["render-entrypoint.sh"]
