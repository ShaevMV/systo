FROM php:8.2-fpm-bookworm


ENV HOME ${WORKING_DIR}


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

COPY error_reporting.ini /usr/local/etc/php/conf.d/error_reporting.ini

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
RUN useradd -m user
RUN chown -R user:user /var/www/html
RUN chgrp -R user /var/www/html
RUN if [ ${WITH_XDEBUG} = "true" ]; then \
    chown -R user:user /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
;fi

RUN if [ ${WITH_XDEBUG} = "true" ]; then \
    chmod 777 /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
;fi

COPY docker-entrypoint.sh /
ENTRYPOINT ["/docker-entrypoint.sh"]


