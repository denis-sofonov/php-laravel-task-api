# Production-like image: PHP-FPM + application code, no dev dependencies.
FROM php:8.4-fpm-alpine

# PHP extensions required by Laravel and PostgreSQL.
RUN apk add --no-cache postgresql-dev icu-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_pgsql bcmath intl \
    && apk del .build-deps

# Composer from the official image.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy the code first, then install dependencies (Laravel scripts run here).
COPY . .

RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache

# PHP-FPM listens on 9000; Nginx talks to it there.
EXPOSE 9000

CMD ["php-fpm"]
