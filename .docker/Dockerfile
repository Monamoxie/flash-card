FROM php:8.2-fpm

RUN docker-php-ext-install pdo pdo_mysql bcmath

# Install PCOV
RUN pecl install pcov redis && docker-php-ext-enable pcov && docker-php-ext-enable redis

RUN apt-get update && apt-get install -y \
    autoconf bash zip \
    supervisor \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install zip

# install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN alias composer='php composer.phar'

ENV COMPOSER_ALLOW_SUPER_USER=1

# Clear package cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

