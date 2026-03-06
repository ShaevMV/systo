# Определяем доступную команду docker compose
DOCKER_COMPOSE := $(shell docker compose version >/dev/null 2>&1 && echo "docker compose" || echo "docker-compose")

env:
	cp .env.example .env
	cp Backend/.env.example Backend/.env
	cp FrontEnd/.env.example FrontEnd/.env
	cp Friendly/.env.example Friendly/.env
	cp Baza/.env.example Baza/.env
	@if ! grep -q "^JWT_SECRET=" Backend/.env 2>/dev/null; then \
		echo "JWT_SECRET=$(openssl rand -base64 32)" >> Backend/.env; \
		echo "✓ JWT_SECRET сгенерирован"; \
	fi

up-prod: ## Полная конфигурация, старт + миграции
	$(DOCKER_COMPOSE) -f docker-compose.prod.yml up --build -d


down-prod:
	$(DOCKER_COMPOSE) -f docker-compose.prod.yml down

# =============================================================================
# Local Development
# =============================================================================

up: ## Запуск всех контейнеров
	$(DOCKER_COMPOSE) up -d --build

down: ## Остановка всех контейнеров
	$(DOCKER_COMPOSE) down

ps: ## Показать статус контейнеров
	$(DOCKER_COMPOSE) ps

logs: ## Показать логи всех контейнеров
	$(DOCKER_COMPOSE) logs -f

logs-php: ## Показать логи PHP контейнера
	$(DOCKER_COMPOSE) logs -f php

logs-nginx: ## Показать логи Nginx контейнера
	$(DOCKER_COMPOSE) logs -f nginx

logs-mysql: ## Показать логи MySQL контейнера
	$(DOCKER_COMPOSE) logs -f mysql

# =============================================================================
# Backend (Laravel)
# =============================================================================

composer: ## Запустить composer в PHP контейнере
	$(DOCKER_COMPOSE) exec -it php composer $(filter-out $@,$(MAKECMDGOALS))

install: ## composer install
	$(DOCKER_COMPOSE) exec -it php composer install

update: ## composer update
	$(DOCKER_COMPOSE) exec -it php composer update

dump-autoload: ## composer dump-autoload
	$(DOCKER_COMPOSE) exec -it php composer dump-autoload

# =============================================================================
# Artisan Commands
# =============================================================================

artisan: ## Запустить artisan команду
	$(DOCKER_COMPOSE) exec -it php php artisan $(filter-out $@,$(MAKECMDGOALS))

migrate: ## Запустить миграции
	$(DOCKER_COMPOSE) exec -it php php artisan migrate

migrate-fresh: ## Свежие миграции с сидерами
	$(DOCKER_COMPOSE) exec -it php php artisan migrate:fresh --seed

migrate-rollback: ## Откат миграций
	$(DOCKER_COMPOSE) exec -it php php artisan migrate:rollback

db-seed: ## Запустить сидеры
	$(DOCKER_COMPOSE) exec -it php php artisan db:seed

cache-clear: ## Очистить все кэши
	$(DOCKER_COMPOSE) exec -it php php artisan cache:clear && \
	$(DOCKER_COMPOSE) exec -it php php artisan config:clear && \
	$(DOCKER_COMPOSE) exec -it php php artisan route:clear && \
	$(DOCKER_COMPOSE) exec -it php php artisan view:clear

route-list: ## Показать список маршрутов
	$(DOCKER_COMPOSE) exec -it php php artisan route:list

tinker: ## Запустить Laravel Tinker
	$(DOCKER_COMPOSE) exec -it php php artisan tinker

# =============================================================================
# Tests
# =============================================================================

test: ## Запустить тесты
	$(DOCKER_COMPOSE) exec -it php php artisan test

test-coverage: ## Запустить тесты с покрытием
	$(DOCKER_COMPOSE) exec -it php php artisan test --coverage

# =============================================================================
# Frontend (Node/Vue)
# =============================================================================

npm: ## Запустить npm команду в Node контейнере
	$(DOCKER_COMPOSE) exec -it node npm $(filter-out $@,$(MAKECMDGOALS))

npm-install: ## npm install
	$(DOCKER_COMPOSE) exec -it node npm install

npm-build: ## npm run build
	$(DOCKER_COMPOSE) exec -it node npm run build

npm-dev: ## Запустить npm run serve (dev режим)
	$(DOCKER_COMPOSE) exec -d node npm run serve

# =============================================================================
# Database
# =============================================================================

db-import: ## Импорт дампа базы данных
	$(DOCKER_COMPOSE) exec -T mysql mysql -uroot -psecret systo < systo_dump.sql

db-dump: ## Дамп базы данных
	$(DOCKER_COMPOSE) exec mysql mysqldump -uroot -psecret systo > systo_dump.sql

# =============================================================================
# Helpers
# =============================================================================

shell: ## Войти в PHP контейнер
	$(DOCKER_COMPOSE) exec -it php bash

shell-root: ## Войти в PHP контейнер как root
	$(DOCKER_COMPOSE) exec -it -u0 php bash

shell-node: ## Войти в Node контейнер
	$(DOCKER_COMPOSE) exec -it node bash

restart: ## Перезапуск всех контейнеров
	$(DOCKER_COMPOSE) restart

rebuild: ## Пересборка и запуск контейнеров
	$(DOCKER_COMPOSE) up -d --build

clean: ## Остановка и удаление контейнеров и томов
	$(DOCKER_COMPOSE) down -v --remove-orphans
	sudo rm -rf ./Docker/mysql/db/*
	sudo rm -rf ./Docker/mysqlFriendly/db/*

setup-hosts: ## Настройка локальных доменов
	sudo ./scripts/setup-hosts.sh

setup: ## Полная настройка и запуск проекта на новой машине
	@echo "=== Настройка проекта systo ==="
	@echo ""
	$(MAKE) clean
	$(MAKE) env
	$(MAKE) setup-hosts
	$(MAKE) up
	@echo ""
	@echo "Ожидание готовности MySQL..."
	while ! $(DOCKER_COMPOSE) exec -T mysql mysqladmin ping -uroot -psecret --silent 2>/dev/null; do \
		echo "Ждём MySQL..."; \
		sleep 2; \
	done
	@echo "✓ MySQL готов"
	@echo ""
	$(MAKE) install
	$(DOCKER_COMPOSE) exec -it php php artisan key:generate
	$(DOCKER_COMPOSE) exec -it php php artisan jwt:secret
	$(DOCKER_COMPOSE) exec -it php php artisan config:clear
	$(DOCKER_COMPOSE) exec -it php php artisan storage:link
	$(DOCKER_COMPOSE) exec -it -u0 php chmod -R 777 /var/www/org/storage
	$(MAKE) migrate
	$(MAKE) db-seed
	@echo ""
	@echo "=== Настройка завершена! ==="
	@echo ""
	@echo "Доступ к приложению:"
	@echo "  - http://org.tickets.loc (Frontend)"
	@echo "  - http://api.tickets.loc (Backend API)"
	@echo ""

%:
	@:

