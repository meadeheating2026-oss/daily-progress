FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    default-mysql-client \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring zip exif pcntl bcmath gd intl xml \
    && rm -rf /var/lib/apt/lists/*

RUN printf "upload_max_filesize=64M\npost_max_size=64M\nmemory_limit=256M\nmax_execution_time=300\n" > /usr/local/etc/php/conf.d/kon.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . /app

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8080

CMD ["sh", "-c", "php artisan config:clear && php artisan view:clear && php -d variables_order=EGPCS artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]
