FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    zip unzip git curl libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN cp .env.example .env \
    && composer install --no-dev --optimize-autoloader

RUN mkdir -p storage/logs storage/framework/views \
    && chmod -R 775 storage

EXPOSE 8000

# Ejecutar migraciones y luego iniciar el servidor
CMD php artisan migrate --force 2>/dev/null; php -S 0.0.0.0:8000 -t public
