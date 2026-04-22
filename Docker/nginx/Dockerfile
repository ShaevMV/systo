FROM nginx:stable

ENV TZ=Europe/Moscow
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update && apt-get install -y \
    apache2-utils \
    && rm -rf /var/lib/apt/lists/*

RUN useradd -m user

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
