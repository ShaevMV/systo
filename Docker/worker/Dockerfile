FROM php:8.2-fpm-bookworm

WORKDIR /var/www/html

RUN rm -rf /var/lib/apt/lists/* \
        && apt-get update --fix-missing \
        && apt-get install -y --no-install-recommends \
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
        && docker-php-ext-configure gd --with-freetype --with-jpeg \
        && docker-php-ext-install -j$(nproc) gd \
        && docker-php-ext-install pdo_mysql \
        && docker-php-ext-install mysqli \
        && docker-php-ext-install zip \
        && docker-php-ext-install soap \
        && docker-php-ext-install sockets \
        && docker-php-source delete \
        && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN useradd -m user
RUN chown -R user:user /var/www/html
RUN chgrp -R user /var/www/html

# supervisor
RUN apt-get update --fix-missing \
    && apt-get install -y --no-install-recommends supervisor \
    && rm -rf /var/lib/apt/lists/*
