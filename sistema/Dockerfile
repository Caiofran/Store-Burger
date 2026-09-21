FROM php:8.4-apache-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libonig-dev default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring gd \
    && a2enmod headers rewrite \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/brasa
COPY . .
COPY php.ini /usr/local/etc/php/conf.d/brasa.ini
COPY apache.conf /etc/apache2/sites-available/000-default.conf
RUN mkdir -p storage/private/sessions storage/media && chown -R www-data:www-data storage \
    && chmod -R 700 storage && chmod -R go-w src database bin vendor \
    && php -r "if(!defined('PASSWORD_ARGON2ID') || !extension_loaded('sodium')) exit(1);"
ENV APP_ENV=production MYSQL_ORACLE_CLIENT=0
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s CMD php -r "exit(@file_get_contents('http://127.0.0.1/api.php?action=health') === false ? 1 : 0);"
