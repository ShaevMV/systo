# =============================================================================
# Configuration
# =============================================================================

# Docker Compose command (auto-detect v1 vs v2)
DOCKER_COMPOSE := $(shell docker compose version >/dev/null 2>&1 && echo "docker compose" || echo "docker-compose")

# Shortcuts for docker compose commands
DC = $(DOCKER_COMPOSE)
DC_RUN = $(DOCKER_COMPOSE) exec $(if $(CI),,-it)
DC_RUN_T = $(DOCKER_COMPOSE) exec -T

# Colors for output
COLOR_RESET := \033[0m
COLOR_GREEN := \033[32m
COLOR_YELLOW := \033[33m
COLOR_BLUE := \033[34m

# =============================================================================
# Main Targets
# =============================================================================

.PHONY: help check-env env up down ps restart rebuild clean setup setup-hosts logs logs-php logs-nginx logs-mysql composer install update dump-autoload artisan migrate migrate-fresh migrate-rollback db-seed cache-clear route-list tinker test test-coverage npm npm-install npm-build npm-dev db-import db-dump shell shell-root shell-node _get-mysql-password _get-mysql-friendly-password _generate-jwt-secret _wait-for-mysql _wait-for-php _setup-app

help: ## Показать список всех команд
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "$(COLOR_BLUE)%-20s$(COLOR_RESET) %s\n", $$1, $$2}'

check-env: ## Проверка наличия необходимых .env файлов
	@echo "$(COLOR_BLUE)=== Проверка окружения ===$(COLOR_RESET)"
	@if [ ! -f .env ]; then \
		echo "$(COLOR_YELLOW)⚠ .env не найден. Копирую .env.example.dev$(COLOR_RESET)"; \
		cp .env.example.dev .env; \
	else \
		echo "$(COLOR_GREEN)✓ .env найден$(COLOR_RESET)"; \
	fi
	@if [ ! -f Backend/.env ]; then \
		cp Backend/.env.example.dev Backend/.env; \
		echo "$(COLOR_YELLOW)⚠ Backend/.env не найден. Копирую из .env.example.dev$(COLOR_RESET)"; \
	else \
		echo "$(COLOR_GREEN)✓ Backend/.env найден$(COLOR_RESET)"; \
	fi
	@if [ ! -f FrontEnd/.env ]; then \
		cp FrontEnd/.env.example.dev FrontEnd/.env; \
		echo "$(COLOR_YELLOW)⚠ FrontEnd/.env не найден. Копирую из .env.example.dev$(COLOR_RESET)"; \
	else \
		echo "$(COLOR_GREEN)✓ FrontEnd/.env найден$(COLOR_RESET)"; \
	fi
	@if [ ! -f Baza/.env ]; then \
		cp Baza/.env.example.dev Baza/.env; \
		echo "$(COLOR_YELLOW)⚠ Baza/.env не найден. Копирую из .env.example.dev$(COLOR_RESET)"; \
	else \
		echo "$(COLOR_GREEN)✓ Baza/.env найден$(COLOR_RESET)"; \
	fi
	@if [ ! -f Friendly/.env ]; then \
		cp Friendly/.env.example.dev Friendly/.env; \
		echo "$(COLOR_YELLOW)⚠ Friendly/.env не найден. Копирую из .env.example.dev$(COLOR_RESET)"; \
	else \
		echo "$(COLOR_GREEN)✓ Friendly/.env найден$(COLOR_RESET)"; \
	fi
	@echo "$(COLOR_GREEN)✓ Проверка завершена$(COLOR_RESET)"

env: ## Скопировать .env.example файлы
	@echo "$(COLOR_BLUE)=== Копирование .env файлов ===$(COLOR_RESET)"
	@if [ ! -f .env ]; then cp .env.example.dev .env; else echo "$(COLOR_YELLOW)✓ .env уже существует$(COLOR_RESET)"; fi
	@cp -n Backend/.env.example.dev Backend/.env 2>/dev/null || true
	@cp -n FrontEnd/.env.example.dev FrontEnd/.env 2>/dev/null || true
	@cp -n Baza/.env.example.dev Baza/.env 2>/dev/null || true
	@cp -n Friendly/.env.example.dev Friendly/.env 2>/dev/null || true
	@$(MAKE) _generate-jwt-secret

up: ## Запуск всех контейнеров
	$(DC) up -d

build: ## Сборка контейнеров (без кеша)
	$(DC) build --no-cache

down: ## Остановка всех контейнеров
	$(DC) down

ps: ## Показать статус контейнеров
	$(DC) ps

restart: ## Перезапуск всех контейнеров
	$(DC) restart

rebuild: ## Пересборка и запуск контейнеров
	$(DC) up -d --build --force-recreate

clean: ## Остановка и удаление контейнеров, томов и данных
	@echo "$(COLOR_YELLOW)=== Очистка проекта ===$(COLOR_RESET)"
	@echo "$(COLOR_YELLOW)⚠ Внимание: Это удалит все данные баз данных!$(COLOR_RESET)"
	@read -p "Продолжить? (y/N): " confirm; \
	if [ "$$confirm" = "y" ] || [ "$$confirm" = "Y" ]; then \
		$(DC) down -v --remove-orphans; \
		sudo rm -rf ./Docker/mysql/db/* ./Docker/mysqlFriendly/db/*; \
		sudo rm -f Backend/public/storage; \
		echo "$(COLOR_GREEN)✓ Очистка завершена$(COLOR_RESET)"; \
	else \
		echo "$(COLOR_YELLOW)✗ Очистка отменена$(COLOR_RESET)"; \
	fi

setup: ## Полная настройка и запуск проекта (fresh install)
	@echo "$(COLOR_BLUE)=== Настройка проекта systo ===$(COLOR_RESET)"
	@$(MAKE) check-env
	@$(MAKE) setup-hosts
	@$(MAKE) build
	@$(MAKE) up
	@$(MAKE) _wait-for-mysql
	@$(MAKE) _setup-app
	@$(MAKE) migrate
	@$(MAKE) db-seed
	@echo ""; \
	echo "$(COLOR_GREEN)=== Настройка завершена! ===$(COLOR_RESET)"; \
	echo ""; \
	echo "Доступ к приложению:"; \
	echo "  - http://org.tickets.loc:$$(grep '^NGINX_PORT=' $(CURDIR)/.env | cut -d'=' -f2) (Frontend)"; \
	echo "  - http://api.tickets.loc:$$(grep '^NGINX_PORT=' $(CURDIR)/.env | cut -d'=' -f2) (Backend API)"; \
	echo ""; \
	echo "Или по IP:"; \
	echo "  - http://localhost:$$(grep '^NGINX_PORT=' $(CURDIR)/.env | cut -d'=' -f2) (Frontend)"; \
	echo "  - http://localhost:$$(grep '^NGINX_PORT=' $(CURDIR)/.env | cut -d'=' -f2) (Backend API)"; \
	echo ""

setup-hosts: ## Настройка локальных доменов
	@if [ -f scripts/setup-hosts.sh ]; then \
		echo "$(COLOR_BLUE)Настройка локальных доменов...$(COLOR_RESET)"; \
		if ! sudo ./scripts/setup-hosts.sh 2>/dev/null; then \
			echo "$(COLOR_YELLOW)⚠ Не удалось выполнить setup-hosts (нет прав sudo)$(COLOR_RESET)"; \
			echo "$(COLOR_YELLOW)  Добавьте вручную в /etc/hosts:$(COLOR_RESET)"; \
			echo "$(COLOR_YELLOW)    127.0.0.1 api.tickets.loc org.tickets.loc$(COLOR_RESET)"; \
		fi; \
	else \
		echo "$(COLOR_YELLOW)⚠ scripts/setup-hosts.sh не найден, пропускаем$(COLOR_RESET)"; \
	fi

# =============================================================================
# Container Logs
# =============================================================================

.PHONY: logs logs-php logs-nginx logs-mysql

logs: ## Показать логи всех контейнеров
	$(DC) logs -f

logs-php: ## Показать логи PHP контейнера
	$(DC) logs -f php

logs-nginx: ## Показать логи Nginx контейнера
	$(DC) logs -f nginx

logs-mysql: ## Показать логи MySQL контейнера
	$(DC) logs -f mysql

# =============================================================================
# Backend (Laravel) Commands
# =============================================================================

.PHONY: composer install update dump-autoload artisan migrate migrate-fresh migrate-rollback db-seed cache-clear route-list tinker

composer: ## Запустить composer команду (make composer install)
	$(DC_RUN) php composer $(filter-out $@,$(MAKECMDGOALS))

install: ## composer install
	$(DC_RUN) php composer install

update: ## composer update
	$(DC_RUN) php composer update

dump-autoload: ## composer dump-autoload
	$(DC_RUN) php composer dump-autoload

artisan: ## Запустить artisan команду (make artisan route:list)
	$(DC_RUN) php php artisan $(filter-out $@,$(MAKECMDGOALS))

migrate: ## Запустить миграции
	$(DC_RUN) php php artisan migrate

migrate-fresh: ## Свежие миграции с сидерами
	$(DC_RUN) php php artisan migrate:fresh --seed

migrate-rollback: ## Откат миграций
	$(DC_RUN) php php artisan migrate:rollback

db-seed: ## Запустить сидеры
	$(DC_RUN) php php artisan db:seed

cache-clear: ## Очистить все кэши Laravel
	$(DC_RUN) php php artisan cache:clear; \
	$(DC_RUN) php php artisan config:clear; \
	$(DC_RUN) php php artisan route:clear; \
	$(DC_RUN) php php artisan view:clear

route-list: ## Показать список маршрутов
	$(DC_RUN) php php artisan route:list

tinker: ## Запустить Laravel Tinker
	$(DC_RUN) php php artisan tinker

# =============================================================================
# Tests
# =============================================================================

.PHONY: test test-coverage

test: ## Запустить тесты
	$(DC_RUN) php php artisan test

test-coverage: ## Запустить тесты с покрытием
	$(DC_RUN) php php artisan test --coverage

# =============================================================================
# Frontend (Node/Vue) Commands
# =============================================================================

.PHONY: npm npm-install npm-build npm-dev

npm: ## Запустить npm команду (make npm run build)
	$(DC_RUN) node npm $(filter-out $@,$(MAKECMDGOALS))

npm-install: ## npm install
	$(DC_RUN) node npm install

npm-build: ## npm run build
	$(DC_RUN) node npm run build

npm-dev: ## Запустить npm run serve (dev режим)
	$(DC) exec -d node npm run serve

# =============================================================================
# Database Commands
# =============================================================================

.PHONY: db-import db-dump

db-import: ## Импорт дампа базы данных
	@MYSQL_ROOT_PASSWORD=$$(grep '^MYSQL_ROOT_PASSWORD=' .env 2>/dev/null | cut -d'=' -f2 | tr -d '\r'); \
	if [ -z "$$MYSQL_ROOT_PASSWORD" ]; then MYSQL_ROOT_PASSWORD="secret"; fi; \
	$(DC_RUN_T) mysql mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" systo < systo_dump.sql

db-dump: ## Дамп базы данных
	@MYSQL_ROOT_PASSWORD=$$(grep '^MYSQL_ROOT_PASSWORD=' .env 2>/dev/null | cut -d'=' -f2 | tr -d '\r'); \
	if [ -z "$$MYSQL_ROOT_PASSWORD" ]; then MYSQL_ROOT_PASSWORD="secret"; fi; \
	$(DC_RUN) mysql mysqldump -uroot -p"$$MYSQL_ROOT_PASSWORD" systo > systo_dump.sql

# =============================================================================
# Shell Access
# =============================================================================

.PHONY: shell shell-root shell-node

shell: ## Войти в PHP контейнер
	$(DC_RUN) php bash

shell-root: ## Войти в PHP контейнер как root
	$(DC_RUN) -u0 php bash

shell-node: ## Войти в Node контейнер
	$(DC_RUN) node bash

# =============================================================================
# Internal Helpers
# =============================================================================

.PHONY: _generate-jwt-secret _wait-for-mysql _setup-app _wait-for-php _get-mysql-password _get-mysql-friendly-password

_get-mysql-password: ## Получить пароль MySQL из .env
	@MYSQL_ROOT_PASSWORD=$$(grep '^MYSQL_ROOT_PASSWORD=' .env 2>/dev/null | cut -d'=' -f2 | tr -d '\r'); \
	if [ -z "$$MYSQL_ROOT_PASSWORD" ]; then \
		MYSQL_ROOT_PASSWORD="secret"; \
	fi; \
	echo "$$MYSQL_ROOT_PASSWORD"

_get-mysql-friendly-password: ## Получить пароль MySQL Friendly из .env
	@MYSQL_FRIENDLY_ROOT_PASSWORD=$$(grep '^MYSQL_FRIENDLY_ROOT_PASSWORD=' .env 2>/dev/null | cut -d'=' -f2 | tr -d '\r'); \
	if [ -z "$$MYSQL_FRIENDLY_ROOT_PASSWORD" ]; then \
		MYSQL_FRIENDLY_ROOT_PASSWORD="common404"; \
	fi; \
	echo "$$MYSQL_FRIENDLY_ROOT_PASSWORD"

_generate-jwt-secret: ## Сгенерировать JWT_SECRET если отсутствует или пуст
	@if ! grep -q "^JWT_SECRET=" Backend/.env 2>/dev/null || \
	    ! grep -q "^JWT_SECRET=." Backend/.env 2>/dev/null; then \
		JWT_SECRET=$$(openssl rand -base64 32 | tr -d '\n'); \
		if grep -q "^JWT_SECRET=" Backend/.env 2>/dev/null; then \
			sed -i.bak "s|^JWT_SECRET=.*|JWT_SECRET=$$JWT_SECRET|" Backend/.env && rm -f Backend/.env.bak; \
		else \
			echo "JWT_SECRET=$$JWT_SECRET" >> Backend/.env; \
		fi; \
		echo "$(COLOR_GREEN)✓ JWT_SECRET сгенерирован$(COLOR_RESET)"; \
	else \
		echo "$(COLOR_YELLOW)✓ JWT_SECRET уже существует$(COLOR_RESET)"; \
	fi

_wait-for-mysql: ## Ожидание готовности MySQL
	@echo "$(COLOR_BLUE)Ожидание готовности MySQL...$(COLOR_RESET)"
	@MYSQL_ROOT_PASSWORD=$$(grep '^MYSQL_ROOT_PASSWORD=' .env 2>/dev/null | cut -d'=' -f2 | tr -d '\r'); \
	if [ -z "$$MYSQL_ROOT_PASSWORD" ]; then MYSQL_ROOT_PASSWORD="secret"; fi; \
	while ! $(DC_RUN_T) mysql mysqladmin ping -uroot -p"$$MYSQL_ROOT_PASSWORD" --silent 2>/dev/null; do \
		echo "  Ждём MySQL..."; \
		sleep 2; \
	done
	@echo "$(COLOR_GREEN)✓ MySQL готов$(COLOR_RESET)"

_wait-for-php: ## Ожидание готовности PHP после пересоздания
	@echo "$(COLOR_BLUE)Ожидание готовности PHP...$(COLOR_RESET)"
	@for i in 1 2 3 4 5; do \
		if $(DC_RUN_T) php php --version >/dev/null 2>&1; then \
			echo "$(COLOR_GREEN)✓ PHP готов$(COLOR_RESET)"; \
			break; \
		fi; \
		echo "  Ждём PHP... ($$i/5)"; \
		sleep 1; \
	done

_setup-app: ## Настройка Laravel приложения
	@echo "$(COLOR_BLUE)Настройка Laravel...$(COLOR_RESET)"
	@echo "$(COLOR_BLUE)Проверка наличия composer в контейнере...$(COLOR_RESET)"
	@if ! $(DC_RUN_T) php which composer >/dev/null 2>&1; then \
		echo "$(COLOR_RED)✗ Composer не найден в контейнере!$(COLOR_RESET)"; \
		echo "$(COLOR_RED)  Не удалось загрузить composer при сборке образа$(COLOR_RESET)"; \
		echo "$(COLOR_RED)  Возможная причина: сетевая блокировка getcomposer.org$(COLOR_RESET)"; \
		echo "$(COLOR_RED)  Решение: включите VPN и попробуйте снова$(COLOR_RESET)"; \
		echo ""; \
		exit 1; \
	fi
	$(DC_RUN) php composer install --no-interaction
	$(DC_RUN) php composer dump-autoload
	$(DC_RUN) php php artisan key:generate
	@# Пересоздаём PHP контейнер для загрузки нового JWT_SECRET из env_file
	$(DC) up -d --force-recreate php
	@$(MAKE) _wait-for-php
	$(DC_RUN) php php artisan config:clear
	$(DC_RUN) php php artisan cache:clear
	$(DC_RUN) php php artisan storage:link
	$(DC_RUN) -u0 php chmod -R 777 /var/www/org/storage
	@echo "$(COLOR_GREEN)✓ Laravel настроен$(COLOR_RESET)"

# Catch-all target to prevent errors for unknown targets
%:
	@:
