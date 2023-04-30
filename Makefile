up: ## Полная конфигурация, старт + миграции
	docker-compose -f docker-compose.prod.yml up --build -d
down:
	docker-compose -f docker-compose.prod.yml down
