# Production-like image: PHP-FPM + application code, no dev dependencies.
FROM php:8.4-fpm-alpine

# PHP-расширения, нужные Laravel и PostgreSQL.
RUN apk add --no-cache postgresql-dev icu-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_pgsql bcmath intl \
    && apk del .build-deps

# Composer берём из официального образа.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Сначала копируем код, затем ставим зависимости (запускаются скрипты Laravel).
COPY . .

RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache

# PHP-FPM слушает порт 9000, к нему обращается Nginx.
EXPOSE 9000

CMD ["php-fpm"]
