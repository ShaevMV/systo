# systo
install
  ```console

  git clone https://github.com/ShaevMV/systo.git

  cd systo/Backend

  mv .env.example .env

  docker-composer up -d

  docker exec -it -u0 php-solarSysto bash

  chmod -R 777 storage/

  composer install

  php artisan storage:link

  php artisan key:generate



```

## Переключение сред

### Локальная разработка
```bash
cp .env.example .env
# В .env уже установлен NGINX_PORT=80 и VUE_APP_API_URL=http://api.tickets.loc/
docker-compose up -d
```

### Продакшен
```bash
cp .env.production .env
# В .env.production установлен NGINX_PORT=50080 и VUE_APP_API_URL=https://api.spaceofjoy.ru/
docker-compose --project-directory . -f docker-compose.yml -f docker-compose.prod.yml up -d
```

Или вручную измените в `.env`:
- `NGINX_PORT` - порт nginx (80 для локальной, 50080 для продакшена)
- `VUE_APP_API_URL` - URL API (http://api.tickets.loc/ для локальной, https://api.spaceofjoy.ru/ для продакшена)
 
  
