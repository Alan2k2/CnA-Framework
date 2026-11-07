# syntax=docker/dockerfile:1.4
# Stage 1: Build & Composer Autoload
FROM php:8.4-cli-alpine AS vendor
WORKDIR /app
COPY composer.json ./
COPY packages ./packages
COPY skeleton ./skeleton
RUN apk add --no-cache curl git \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer dump-autoload --optimize --no-dev

# Stage 2: Production PHP-FPM Runtime
FROM php:8.4-fpm-alpine AS runtime

# Install system dependencies & PHP extensions
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    postgresql-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        mbstring \
        opcache \
        intl \
        zip \
        gd \
        bcmath \
        pcntl \
    && apk del $PHPIZE_DEPS

# Production PHP & OPcache Configuration
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=0'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.save_comments=1'; \
    echo 'opcache.jit=tracing'; \
    echo 'opcache.jit_buffer_size=64M'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

RUN { \
    echo 'expose_php=Off'; \
    echo 'memory_limit=256M'; \
    echo 'upload_max_filesize=64M'; \
    echo 'post_max_size=64M'; \
    echo 'display_errors=Off'; \
    echo 'log_errors=On'; \
    echo 'error_log=/dev/stderr'; \
} > /usr/local/etc/php/conf.d/cna-production.ini

WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor

# Set non-root execution permissions
RUN chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage \
    && chmod +x /var/www/html/cna

USER www-data

EXPOSE 9000

CMD ["php-fpm"]

