FROM php:8.0-fpm


ENV HOME ${WORKING_DIR}


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
        && docker-php-source delete

COPY vhost.conf /etc/apache2/sites-available/000-default.conf

#composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
#xdebug
ARG WITH_XDEBUG="false"
COPY xdebug.ini /tmp/xdebug.ini

RUN if [ ${WITH_XDEBUG} = "true" ]; then \
    pecl install xdebug \
     && docker-php-ext-enable xdebug \
;fi

#
RUN adduser --disabled-password --gecos "" user
RUN chown -R user:user /var/www/html
RUN chgrp -R user /var/www/html

COPY docker-entrypoint.sh /
ENTRYPOINT ["/docker-entrypoint.sh"]


