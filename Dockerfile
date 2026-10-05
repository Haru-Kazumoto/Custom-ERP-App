FROM dunglas/frankenphp:php8.4-alpine

RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    zip \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    mysql-client \
    linux-headers \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    bcmath \
    exif \
    gd \
    intl \
    opcache \
    pcntl \
    pdo_mysql \
    zip

RUN pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy seluruh Laravel terlebih dahulu
COPY . .

# Baru install dependency
RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --optimize-autoloader

RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache

ENV SERVER_NAME=:80

EXPOSE 80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
