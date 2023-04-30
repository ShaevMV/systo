FROM nginx

ENV TZ=Europe/Moscow
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN adduser --disabled-password --gecos "" user

RUN touch /var/run/nginx.pid \
    && mkdir /socket \
    && mkdir /etc/nginx/ssl \
    && chown -Rf user:user \
    /etc/nginx/nginx.conf \
    /var/cache/nginx \
    /var/log/nginx \
    /var/run/nginx.pid \
    /socket

RUN chown -R user:user /etc/nginx/conf.d;
RUN chmod -R 755 /etc/nginx/conf.d;

COPY docker-entrypoint.sh /
ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]
