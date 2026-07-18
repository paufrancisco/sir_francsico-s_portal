#!/bin/bash
set -e

# Render provides $PORT dynamically - default to 10000 if not set
PORT="${PORT:-10000}"

# Inject the actual port into nginx config
sed -i "s/PORT_PLACEHOLDER/${PORT}/g" /etc/nginx/http.d/default.conf

# Cache Laravel config/routes/views for production performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations automatically on boot (safe to leave, uses --force for prod)
php artisan migrate --force

# Start nginx + php-fpm via supervisor
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf