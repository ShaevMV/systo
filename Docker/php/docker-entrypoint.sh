#!/usr/bin/env sh
echo "xdebug"

echo "${WITH_XDEBUG}"

if [ "${WITH_XDEBUG}" = "true" ]
then
    cp /tmp/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
fi

/usr/local/sbin/php-fpm -F