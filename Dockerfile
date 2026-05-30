FROM php:8.3-fpm

# dépendances système
RUN apt-get update && apt-get install -y \
    git curl unzip libicu-dev libzip-dev libpng-dev libonig-dev libxml2-dev

# extensions PHP
RUN docker-php-ext-install pdo pdo_mysql intl zip opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash \
    && apt-get install -y symfony-cli

WORKDIR /var/www/app