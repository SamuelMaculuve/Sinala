# Local testing image only — not for production.
FROM php:8.4-cli-alpine

RUN apk add --no-cache libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev icu-dev sqlite \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install gd zip intl bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
