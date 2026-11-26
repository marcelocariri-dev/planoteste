# Plano de Teste

Sistema simples para criar planos de teste, cadastrar casos de teste e registrar execuções
(passou / falhou / bloqueado), com resumo de progresso por plano.

- **Backend:** Laravel 12 (PHP 8.3) em **DDD + arquitetura limpa**
- **Frontend:** React 19 + TypeScript + Vite
- **Banco:** MySQL 8 em container próprio
- **Docker:** 3 containers novos (`plano-teste-db`, `plano-teste-api`, `plano-teste-web`) rodando no mesmo
  Docker do SoftcomShop, sem mexer nos containers dele. Opcionalmente entram na rede do SoftcomShop.

## Subindo (WSL)

```bash
# copie/extraia a pasta para dentro do WSL (ex.: ~/projetos/plano-teste) — fica bem mais rápido que /mnt/c
cd ~/projetos/plano-teste
cp .env.example .env            # ajuste as portas se precisar
docker compose up -d --build    # ou: make up
```

| Serviço  | URL                              |
|----------|----------------------------------|
| Frontend | http://localhost:5180            |
| API      | http://localhost:8090/api/planos |
| MySQL    | localhost:3310 (plano_teste / plano_teste, db `plano_teste`) |

Na primeira subida o container da API roda `composer install`, gera a `APP_KEY`, executa as
**migrations** e o **seeder** (um plano de exemplo de NFC-e). Acompanhe com `docker compose logs -f plano-teste-api`.

Você conecta no banco pelo HeidiSQL em `127.0.0.1:3310`.

### Ligando à rede do docker do SoftcomShop

```bash
docker network ls                       # ache a rede do softcomshop (ex.: softcomshop_default)
# coloque o nome em SOFTCOMSHOP_NETWORK no .env
docker compose -f docker-compose.yml -f docker-compose.softcomshop.yml up -d --build
# ou: make up SOFTCOM=1
```

Assim `plano-teste-api` e `plano-teste-db` enxergam os containers do SoftcomShop pelo nome (e vice‑versa).

### Comandos úteis

| Make          | Equivalente                                                          |
|---------------|----------------------------------------------------------------------|
| `make migrate`| `docker compose exec plano-teste-api php artisan migrate`            |
| `make fresh`  | `docker compose exec plano-teste-api php artisan migrate:fresh --seed` |
| `make test`   | `docker compose exec plano-teste-api php artisan test`               |
| `make db`     | cliente MySQL dentro do container                                     |
| `make down`   | `docker compose down` (adicione `-v` para apagar o banco)             |

Nova migration: `docker compose exec plano-teste-api php artisan make:migration create_xxx_table`

## Arquitetura (backend)

```
backend/src/
├── Domain/                      # regras de negócio puras (sem Laravel)
│   ├── Shared/Exceptions/       # DomainException (422) e EntidadeNaoEncontradaException (404)
│   ├── PlanoTeste/
│   │   ├── Entities/PlanoTeste.php          # agregado raiz: status, edição, concluir/reabrir
│   │   ├── Enums/StatusPlano.php            # rascunho → em_execucao → concluido
│   │   ├── ValueObjects/ResumoExecucao.php  # totais e percentuais
│   │   └── Repositories/PlanoTesteRepositoryInterface.php
│   ├── CasoTeste/  (Entities, Enums/Prioridade, Repositories)
│   └── Execucao/   (Entities, Enums/ResultadoExecucao, Repositories)
├── Application/                 # casos de uso (orquestram domínio + repositórios)
│   ├── Shared/TransactionManagerInterface.php
│   ├── PlanoTeste/  DTOs, Services/ResultadosDoPlano, UseCases/{Criar,Atualizar,Obter,Listar,Excluir,AlterarStatus}
│   ├── CasoTeste/   DTOs, UseCases/{Adicionar,Atualizar,Remover}CasoTeste
│   └── Execucao/    DTOs, UseCases/{RegistrarExecucao,ListarExecucoesDoCaso}
├── Infrastructure/              # detalhes técnicos
│   ├── Persistence/Eloquent/Models/        # models Eloquent (só persistência)
│   ├── Persistence/Eloquent/Repositories/  # implementações dos repositórios + mapeamento ↔ entidades
│   ├── Persistence/LaravelTransactionManager.php
│   └── Providers/DomainServiceProvider.php  # bind interface → implementação
└── Interfaces/Http/             # entrada HTTP
    ├── Controllers/             # finos: Request → DTO → caso de uso → JSON
    └── Requests/                # validação de formato (FormRequest)
```

Dependências só apontam para dentro: `Interfaces → Application → Domain` e `Infrastructure → Domain/Application`.
O domínio não conhece Laravel nem Eloquent (os testes unitários dele rodam sem o framework).

**Regras de negócio no domínio**
- Plano nasce como *rascunho*; a primeira execução registrada muda para *em execução*.
- Plano *concluído* não aceita edição, novos casos, remoção de casos nem execuções — é preciso reabrir.
- Execução *falhou* ou *bloqueado* exige observação.
- O resultado atual de um caso é o da última execução; o histórico completo fica guardado.

**Tabelas (migrations em `backend/database/migrations`)**
`planos_teste` 1─N `casos_teste` 1─N `execucoes_teste` (exclusão em cascata).

## API

| Método | Rota                              | Descrição                         |
|--------|-----------------------------------|-----------------------------------|
| GET    | /api/planos?busca=&status=        | lista planos com resumo           |
| POST   | /api/planos                       | cria plano                        |
| GET    | /api/planos/{id}                  | plano + casos + resumo            |
| PUT    | /api/planos/{id}                  | atualiza plano                    |
| DELETE | /api/planos/{id}                  | exclui plano (cascata)            |
| POST   | /api/planos/{id}/concluir         | conclui                           |
| POST   | /api/planos/{id}/reabrir          | reabre                            |
| POST   | /api/planos/{id}/casos            | adiciona caso                     |
| PUT    | /api/casos/{id}                   | atualiza caso                     |
| DELETE | /api/casos/{id}                   | remove caso                       |
| GET    | /api/casos/{id}/execucoes         | histórico de execuções            |
| POST   | /api/casos/{id}/execucoes         | registra execução                 |

## Frontend

```
frontend/src/
├── api/          # client fetch + planoTesteApi (todas as chamadas)
├── types/        # tipos espelhando a API
├── components/   # Modal, formulários, badges, barra de resumo, painel de execução
└── pages/        # PlanosPage (lista) e PlanoDetalhePage (casos + execução)
```

O Vite faz proxy de `/api` para o container da API, então não há configuração de CORS.

## Rodando sem Docker (opcional)

```bash
cd backend && cp .env.example .env   # ajuste DB_HOST=127.0.0.1 e DB_PORT=3310
composer install && php artisan key:generate && php artisan migrate --seed && php artisan serve --port=8090
cd ../frontend && npm install && npm run dev
```
