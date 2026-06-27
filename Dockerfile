# ---- Stage 1: Build frontend assets (Vite + Tailwind) ----
FROM node:20-alpine AS frontend-build
WORKDIR /app

# Copy lockfiles first for layer caching
COPY package.json package-lock.json* ./
RUN npm install

# Copy rest of source
COPY . .
RUN npm run build

# ---- Stage 2: PHP application ----
FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    curl-dev \
    zip \
    unzip \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    freetype-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        xml \
        curl \
        zip \
        gd \
        bcmath \
        fileinfo \
        opcache

# TAMBAHAN: Custom PHP config untuk upload & timeout
RUN echo "upload_max_filesize = 2M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 2M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 120" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_input_time = 120" >> /usr/local/etc/php/conf.d/uploads.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first (layer caching)
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev --no-scripts --no-interaction

# Copy application files
COPY . .

# Overwrite public/build with assets built in the frontend-build stage
COPY --from=frontend-build /app/public/build ./public/build

# Run post-install scripts
RUN composer run-script post-autoload-dump || true

# Initial permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Nginx config template
RUN printf 'server {\n\
    listen __PORT__;\n\
    root /var/www/html/public;\n\
    index index.php;\n\
    client_max_body_size 12M;\n\
    location / {\n\
        try_files $uri $uri/ /index.php?$query_string;\n\
    }\n\
    location ~ \\.php$ {\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\n\
        include fastcgi_params;\n\
        fastcgi_read_timeout 120;\n\
    }\n\
}\n' > /etc/nginx/http.d/default.conf

# Supervisor config
RUN printf '[supervisord]\n\
nodaemon=true\n\
[program:php-fpm]\n\
command=php-fpm\n\
autostart=true\n\
autorestart=true\n\
[program:nginx]\n\
command=nginx -g "daemon off;"\n\
autostart=true\n\
autorestart=true\n' > /etc/supervisord.conf

# PERBAIKAN: Start script dengan proper permission handling
RUN printf '#!/bin/sh\n\
set -e\n\
PORT=${PORT:-8080}\n\
sed -i "s/__PORT__/$PORT/" /etc/nginx/http.d/default.conf\n\
echo "Nginx listening on port: $PORT"\n\
\n\
# Clear old caches\n\
php artisan config:clear || true\n\
php artisan cache:clear || true\n\
\n\
# Run migrations\n\
php artisan migrate --force\n\
php artisan db:seed --force || true\n\
\n\
# Create storage link\n\
php artisan storage:link || true\n\
\n\
# PENTING: Reset permissions SETELAH artisan commands\n\
# Artisan bisa membuat file baru dengan owner root!\n\
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache\n\
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache\n\
\n\
# Jalankan cache commands sebagai www-data\n\
# Agar file cache dimiliki www-data, bukan root\n\
su -s /bin/sh www-data -c "php artisan config:cache"\n\
su -s /bin/sh www-data -c "php artisan route:cache"\n\
su -s /bin/sh www-data -c "php artisan view:cache"\n\
\n\
echo "Starting supervisord..."\n\
exec supervisord -c /etc/supervisord.conf\n' > /start.sh && chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]