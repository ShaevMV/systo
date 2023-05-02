env:
	cp .env.example .env

up-prod: ## Полная конфигурация, старт + миграции
	docker-compose -f docker-compose.prod.yml up --build -d


down-prod:
	docker-compose -f docker-compose.prod.yml down
