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
 
  
