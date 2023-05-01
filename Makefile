up-prod: ## Полная конфигурация, старт + миграции
	cp .env.example .env
	docker-compose -f docker-compose.prod.yml up --build -d


down-prod:
	docker-compose -f docker-compose.prod.yml down
