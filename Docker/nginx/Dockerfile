FROM nginx

ENV TZ=Europe/Moscow
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN adduser --disabled-password --gecos "" user

RUN touch /var/run/nginx.pid \
    && mkdir /socket \
    && chown -Rf user:user \
    /etc/nginx/nginx.conf \
    /var/cache/nginx \
    /var/log/nginx \
    /var/run/nginx.pid \
    /socket
