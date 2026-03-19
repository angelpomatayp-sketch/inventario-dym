FROM php:8.2-cli

# Dependencias del sistema + extensiones PHP incluyendo GD
RUN apt-get update && apt-get install -y \
    git curl unzip libpng-dev libonig-dev libxml2-dev \
    libzip-dev libfreetype6-dev libjpeg62-turbo-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring zip intl fileinfo gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

EXPOSE ${PORT:-8000}

CMD php artisan config:clear && php artisan config:cache && php artisan route:cache && php artisan migrate --force && php artisan db:seed --class=RolesPermisosSeeder --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
