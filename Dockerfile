FROM php:7.0-apache

RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    && docker-php-ext-install zip

COPY . /var/www

COPY php.ini /usr/local/etc/php/php.ini

RUN a2enmod rewrite

# REDIS
RUN pecl install redis && docker-php-ext-enable redis
