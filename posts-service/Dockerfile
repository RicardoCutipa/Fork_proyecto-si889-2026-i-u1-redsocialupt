FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip curl \
    && docker-php-ext-install pdo pdo_mysql zip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY . .

RUN cp .env.example .env \
    && composer install --no-dev --optimize-autoloader

# Crear carpeta de uploads con permisos de escritura
RUN mkdir -p public/uploads storage/logs storage/framework/views \
    && chmod -R 775 public/uploads storage

EXPOSE 8000

CMD php artisan migrate --force 2>/dev/null; php -S 0.0.0.0:8000 -t public
