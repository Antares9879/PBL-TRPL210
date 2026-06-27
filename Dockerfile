# ---- Stage 1: Build frontend assets (Vite + Tailwind) ----
FROM node:20-alpine AS frontend-build
WORKDIR /app

# Copy lockfiles first for layer caching
COPY package.json package-lock.json* ./
RUN npm install

# Copy rest of source (Tailwind v4 + Blade JIT scans resources/views & resources/js)
COPY . .
RUN npm run build

# ---- Stage 2: PHP application ----
FROM php:8.4-fpm-alpine

# Install system dependencies (nodejs/npm tidak diperlukan lagi di sini)
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

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Nginx config template (placeholder __PORT__ disubstitusi saat runtime di start.sh)
RUN printf 'server {\n\
    listen __PORT__;\n\
    root /var/www/html/public;\n\
    index index.php;\n\
    client_max_body_size 10M;\n\
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

# Start script
RUN printf '#!/bin/sh\n\
PORT=${PORT:-8080}\n\
sed -i "s/__PORT__/$PORT/" /etc/nginx/http.d/default.conf\n\
echo "Nginx listening on port: $PORT"\n\
php artisan config:clear\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan view:cache\n\
php artisan migrate --force\n\
php artisan db:seed --force\n\
php artisan storage:link\n\
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache\n\
supervisord -c /etc/supervisord.conf\n' > /start.sh && chmod +x /start.sh

EXPOSE 8080

RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/custom.ini \
 && echo "post_max_size = 12M" >> /usr/local/etc/php/conf.d/custom.ini

CMD ["/start.sh"]