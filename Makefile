COMPOSE = docker compose
ifeq ($(SOFTCOM),1)
COMPOSE = docker compose -f docker-compose.yml -f docker-compose.softcomshop.yml
endif

.PHONY: up down logs ps migrate fresh seed test bash db

up:        ## sobe tudo (make up SOFTCOM=1 para ligar à rede do softcomshop)
	@[ -f .env ] || cp .env.example .env
	$(COMPOSE) up -d --build
down:
	$(COMPOSE) down
logs:
	$(COMPOSE) logs -f
ps:
	$(COMPOSE) ps
migrate:
	$(COMPOSE) exec plano-teste-api php artisan migrate
fresh:     ## recria o banco com dados de exemplo
	$(COMPOSE) exec plano-teste-api php artisan migrate:fresh --seed
test:
	$(COMPOSE) exec plano-teste-api php artisan test
bash:
	$(COMPOSE) exec plano-teste-api bash
db:
	$(COMPOSE) exec plano-teste-db mysql -uplano_teste -pplano_teste plano_teste
