FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y libonig-dev unzip git \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --optimize-autoloader

COPY . .

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t public public/index.php"]