.DEFAULT_GOAL := help

DC := docker-compose
DC_PROD := docker-compose -f docker-compose.prod.yml

# Интерактивные команды (shell, tinker) — требуют TTY (-it)
# Команды без stdin (тесты, миграции, composer) — без -t, чтобы работать в CI
PHP_IT := docker exec -it php-solarSysto
PHP_BAZA_IT := docker exec -it php-baza
NODE_IT := docker exec -it -u0 node-solarSysto

PHP := docker exec php-solarSysto
PHP_BAZA := docker exec php-baza
NODE := docker exec -u0 node-solarSysto

##@ Помощь

.PHONY: help
help: ## Список доступных команд
	@awk 'BEGIN {FS = ":.*##"; printf "\nИспользование:\n  make \033[36m<цель>\033[0m\n"} \
	/^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2 } \
	/^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) }' $(MAKEFILE_LIST)

##@ Окружение

.PHONY: env
env: ## Скопировать .env.example в .env (если ещё нет)
	@if [ ! -f .env ]; then cp .env.example .env && echo "✓ .env создан из .env.example"; \
	else echo "→ .env уже существует, не трогаю"; fi

##@ Docker dev (локальная разработка)

.PHONY: up
up: ## Поднять dev-окружение (без билда)
	$(DC) up -d

.PHONY: build
build: ## Билд dev-образов (без запуска)
	$(DC) build

.PHONY: up-build
up-build: ## Билд + поднять dev-окружение
	$(DC) up -d --build

.PHONY: down
down: ## Остановить dev-окружение
	$(DC) down

.PHONY: restart
restart: down up ## Перезапустить dev-окружение

.PHONY: ps
ps: ## Список запущенных контейнеров
	$(DC) ps

.PHONY: logs
logs: ## Все логи (потоково)
	$(DC) logs -f --tail=100

.PHONY: logs-php
logs-php: ## Логи Backend (php-solarSysto)
	$(DC) logs -f --tail=100 php

.PHONY: logs-baza
logs-baza: ## Логи Baza (php-baza)
	$(DC) logs -f --tail=100 phpBaza

.PHONY: logs-worker
logs-worker: ## Логи воркера очередей
	$(DC) logs -f --tail=100 worker

.PHONY: logs-mysql
logs-mysql: ## Логи MySQL
	$(DC) logs -f --tail=100 mysql

##@ Docker prod

.PHONY: up-prod
up-prod: ## Поднять prod-окружение (без билда)
	$(DC_PROD) up -d

.PHONY: build-prod
build-prod: ## Билд prod-образов (без запуска)
	$(DC_PROD) build

.PHONY: up-prod-build
up-prod-build: ## Билд + поднять prod-окружение
	$(DC_PROD) up -d --build

.PHONY: down-prod
down-prod: ## Остановить prod-окружение
	$(DC_PROD) down

##@ Shell-доступ к контейнерам

.PHONY: shell-php
shell-php: ## Войти в Backend php-контейнер
	$(PHP_IT) bash

.PHONY: shell-baza
shell-baza: ## Войти в Baza php-контейнер
	$(PHP_BAZA_IT) bash

.PHONY: shell-node
shell-node: ## Войти в node-контейнер (root)
	$(NODE_IT) bash

.PHONY: shell-mysql
shell-mysql: ## MySQL CLI как root
	$(DC) exec mysql mysql -uroot -psecret systo

##@ Laravel — Backend

.PHONY: migrate
migrate: ## Применить миграции Backend
	$(PHP) php artisan migrate

.PHONY: migrate-fresh
migrate-fresh: ## ВНИМАНИЕ: пересоздать БД с нуля + seeders (Backend)
	@echo "⚠️  ВНИМАНИЕ: миграции с нуля удалят все данные в БД."
	@read -p "Продолжить? (yes/no) " confirm && [ "$$confirm" = "yes" ]
	$(PHP) php artisan migrate:fresh --seed

.PHONY: seed
seed: ## Запустить seeders (Backend)
	$(PHP) php artisan db:seed

.PHONY: cache-clear
cache-clear: ## Сбросить весь кеш Laravel (Backend)
	$(PHP) php artisan optimize:clear

.PHONY: tinker
tinker: ## Laravel Tinker (Backend)
	$(PHP_IT) php artisan tinker

##@ Laravel — Baza

.PHONY: migrate-baza
migrate-baza: ## Применить миграции Baza
	$(PHP_BAZA) php artisan migrate

.PHONY: cache-clear-baza
cache-clear-baza: ## Сбросить кеш Laravel (Baza)
	$(PHP_BAZA) php artisan optimize:clear

##@ Зависимости

.PHONY: composer-install
composer-install: ## composer install на Backend и Baza
	$(PHP) composer install
	$(PHP_BAZA) composer install

.PHONY: composer-update
composer-update: ## composer update на Backend и Baza
	$(PHP) composer update
	$(PHP_BAZA) composer update

.PHONY: npm-install
npm-install: ## npm install на фронтенде
	$(NODE) npm install

.PHONY: install
install: composer-install npm-install ## Установить все зависимости

##@ Фронтенд

.PHONY: build-frontend
build-frontend: ## Сборка фронтенда (npm run build)
	$(NODE) npm run build

.PHONY: dev-frontend
dev-frontend: ## Dev-сервер фронтенда (npm run dev)
	$(NODE) npm run dev

##@ Тесты

.PHONY: test
test: test-backend test-baza ## Запустить все тесты (Backend + Baza)

.PHONY: test-backend
test-backend: ## Запустить PHPUnit Backend
	$(PHP) ./vendor/bin/phpunit

.PHONY: test-baza
test-baza: ## Запустить PHPUnit Baza
	$(PHP_BAZA) ./vendor/bin/phpunit

.PHONY: test-coverage
test-coverage: ## Запустить тесты Backend с coverage HTML
	$(PHP) ./vendor/bin/phpunit --coverage-html tests-coverage

##@ Линтеры

.PHONY: pint
pint: ## Laravel Pint (форматирование PHP) — Backend
	$(PHP) ./vendor/bin/pint

.PHONY: pint-test
pint-test: ## Laravel Pint в режиме проверки (без правок) — Backend
	$(PHP) ./vendor/bin/pint --test

.PHONY: eslint
eslint: ## ESLint на фронтенде
	$(NODE) npm run lint

##@ Сервис

.PHONY: clean
clean: ## Удалить vendor, node_modules, build артефакты
	rm -rf Backend/vendor Baza/vendor FrontEnd/node_modules FrontEnd/dist

.PHONY: backup
backup: ## Бэкап БД systo в ./backups/
	@mkdir -p backups
	$(DC) exec mysql sh -c 'mysqldump -uroot -psecret systo' > backups/systo-$$(date +%Y%m%d-%H%M%S).sql
	@echo "✓ Бэкап создан в backups/"
