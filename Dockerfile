## STAGE 1: Install PHP dependencies (produces vendor/, needed by Ziggy in the frontend build)
FROM composer:2 AS composer_builder

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize


## STAGE 2: Build Vue/Vite assets (needs vendor/tightenco/ziggy from stage 1)
FROM node:20-alpine AS node_builder

WORKDIR /app

COPY package*.json ./
RUN npm install

# Bring in the full app including vendor/ from the composer stage
COPY --from=composer_builder /app ./

RUN npm run build


## STAGE 3: Final PHP + Nginx runtime image
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

WORKDIR /var/www/html

# Copy the full app (with vendor/) from the composer stage
COPY --from=composer_builder /app ./

# Copy built frontend assets from the node stage
COPY --from=node_builder /app/public/build ./public/build

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Nginx + PHP-FPM + Supervisor config
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 10000

CMD ["/start.sh"]