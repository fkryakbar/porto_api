FROM node:23-alpine AS build-stage

# Set working directory
WORKDIR /app

# Salin file package.json dan package-lock.json untuk menginstall dependensi Node.js
COPY package*.json ./

# Install dependensi frontend
RUN npm install --legacy-peer-deps

# Salin semua file aplikasi untuk build assets
COPY . .

# Build assets frontend
RUN npm run build

# Stage 2: Install Laravel dependencies
FROM fkryakbar/php-alpine:v1.1 AS laravel-stage

# Set working directory
WORKDIR /var/www/html

# Salin aplikasi Laravel kecuali direktori node_modules yang tidak diperlukan
COPY . .

# Install composer dependencies (only production)
RUN composer install --no-dev --optimize-autoloader 

FROM dunglas/frankenphp:latest-php8.3.7-alpine

# Set working directory
WORKDIR /app

# Salin aplikasi Laravel dari laravel-stage
COPY --from=laravel-stage /var/www/html /app

# Salin hasil build frontend dari build-stage
COPY --from=build-stage /app/public /app/public

COPY ./.env.production .env


ENV SERVER_NAME=:8080

# ENV FRANKENPHP_CONFIG="worker ./public/index.php"

EXPOSE 8080

RUN install-php-extensions \
    pdo_mysql \
    gd \
    intl \
    zip \
    opcache \
    && chown -R www-data:www-data /app \
    && chmod -R 775 /app/storage \
    && chmod -R 775 /app/bootstrap/cache \
    && php artisan optimize