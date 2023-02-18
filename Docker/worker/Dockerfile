FROM php:8.0-fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
        libpng-dev \
        zlib1g-dev \
        libxml2-dev \
        libzip-dev \
        libonig-dev \
        zip \
        ssh \
        git \
        telnet \
        curl \
        unzip \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        && docker-php-ext-configure gd --with-freetype --with-jpeg \
        && docker-php-ext-install -j$(nproc) gd \
        && docker-php-ext-install pdo_mysql \
        && docker-php-ext-install mysqli \
        && docker-php-ext-install zip \
        && docker-php-ext-install soap \
        && docker-php-ext-install sockets \
        && docker-php-source delete \

#ENV HOME /var/www/html
#ENV DOCKER_PHP ${HOME}/docker/php

#COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY vhost.conf /etc/apache2/sites-available/000-default.conf
#COPY custom.ini /usr/local/etc/php/conf.d/custom.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

#
RUN adduser --disabled-password --gecos "" user
RUN chown -R user:user /var/www/html
RUN chgrp -R user /var/www/html

# supervisor
RUN apt-get install supervisor -y supervisor


