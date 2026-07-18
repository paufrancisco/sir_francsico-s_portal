## STAGE 1: Build Vue/Vite assets
FROM node:20-alpine AS node_builder

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build


## STAGE 2: PHP + Laravel
FROM php:8.2-fpm-alpine AS app

# Install system deps + PHP extensions Laravel usually needs
RUN apk add --no-cache \
    bash \
    curl \
    git \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libxml2-dev \
    nginx \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring zip exif pcntl gd bcmath xml dom simplexml soap

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy the rest of the app
COPY . .

# Copy built frontend assets from stage 1
COPY --from=node_builder /app/public/build ./public/build

# Finish composer setup (runs artisan package discovery etc.)
RUN composer dump-autoload --optimize

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Nginx + PHP-FPM + Supervisor config
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 10000

CMD ["/start.sh"]