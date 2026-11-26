# Plano de Teste

Sistema de QA e produto:

- **Planos de teste** – casos, execuções (passou / falhou / bloqueado) e resumo de progresso.
- **Validação de regras de negócio (PM | PO)** – documento por módulo (melhoria ou novo módulo) com editor
  markdown, **tags que separam o texto em casos de teste**, anexos, aprovação de PM e PO e histórico de versões.
- **Cofre de documentos e normas técnicas** – arquivos por categoria, departamentalizados pela tag do módulo.
- **Gerador de product backlog** – orientações + regras + documentos → épicos, histórias e critérios de aceite (Claude).
- **Integração MCP** – consome os casos de teste oficiais da empresa e importa para os planos.

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

### Configurações opcionais (`.env` da raiz)

| Variável | Para quê |
|---|---|
| `ANTHROPIC_API_KEY` | Gera o product backlog direto pela API do Claude. Sem ela, o sistema monta o prompt para colar no Claude e importa a resposta. |
| `ANTHROPIC_MODEL` | Modelo usado (padrão `claude-sonnet-5-5`). |

Depois de alterar: `docker compose up -d --build plano-teste-api` (o rebuild também aplica o limite de upload de 25 MB).

### Servidor MCP de demonstração

Para testar a integração sem o sistema oficial:

```bash
docker compose --profile demo up -d
```

Em **Integração MCP → Nova conexão** use a URL `http://plano-teste-mcp-demo:8765/mcp`, ferramenta `listar_casos`
e argumentos `{"modulo": "PDV"}` (ou `FISCAL`).

### Comandos úteis

| Make          | Equivalente                                                          |
|---------------|----------------------------------------------------------------------|
| `make migrate`| `docker compose exec plano-teste-api php artisan migrate`            |
| `make fresh`  | `docker compose exec plano-teste-api php artisan migrate:fresh --seed` |
| `make test`   | `docker compose exec plano-teste-api php artisan test`               |
| `make db`     | cliente MySQL dentro do container                                     |
| `make down`   | `docker compose down` (adicione `-v` para apagar o banco)             |

Nova migration: `docker compose exec plano-teste-api php artisan make:migration create_xxx_table`

## Funcionalidades novas em detalhe

### Validação de regras de negócio (PM | PO)
- Cada regra pertence a um **módulo** (a tag do módulo é o "departamento") e é do tipo *melhoria* ou *novo módulo*.
- **Editor**: markdown com visualização lado a lado, botão **Modelo** (Contexto, Objetivo, Escopo, Regras, Critérios,
  Casos, Exceções, Dúvidas), atalho **Ctrl+S**, painel de marcadores (clique para ir à linha) e checklist de qualidade do documento.
- **Tags**: selecione um trecho e clique em *Caso*, *Regra*, *Critério*, *Exceção* ou *Dúvida*. O trecho vira um bloco:

  ```
  ::: caso [alta] Desconto acima do limite exige gerente
  Pré-condições: operador com limite de 5%
  Passos:
  1. Aplicar 10% de desconto
  Resultado esperado: sistema pede a senha do gerente
  :::
  ```
  Também aceita Gherkin (Dado / Quando / Então). Cada bloco **caso** vira um caso de teste na aba *Casos marcados*,
  que gera um plano novo ou alimenta um plano existente (sem duplicar).
- **Anexos**: os arquivos vão para o cofre, no módulo da regra.
- **Validação PM | PO**: `rascunho → em validação → aprovada | reprovada`. Precisa da aprovação dos dois; reprovar exige parecer.
  Regra reprovada volta para rascunho ao ser editada; aprovada só muda se for reaberta (nova versão).
- **Versões**: cada alteração de texto gera uma versão, com comparação (diff) com a atual.

### Cofre de documentos e normas técnicas
- Menu lateral **Cofre**: documentos agrupados por módulo (tag), filtro por categoria e busca (título, arquivo, descrição, tags).
- Upload avulso da máquina (arrastar e soltar, vários arquivos) ou pela aba *Anexos* de uma regra.
- Categorias gerenciáveis (Regra de negócio, Norma técnica, Legislação, Especificação técnica, Manual, Evidência de teste, Outros).
- Arquivos ficam em `backend/storage/app/private/cofre/<TAG>/` (até 25 MB cada).

### Product backlog
- Escolha o **padrão** (template de prompt editável com `{{modulo}}`, `{{orientacoes}}`, `{{regras}}`, `{{documentos}}`),
  escreva as orientações e selecione regras e documentos do cofre.
- PDF, imagens, `.docx` e textos são enviados ao Claude; o resultado (épicos → histórias Como/Quero/Para → critérios Gherkin,
  MoSCoW e story points) é salvo e pode ser exportado em **Markdown** ou **CSV** (Excel/Jira).
- **Gerar plano de teste**: cada história vira um caso (Quando → passos, Então → resultado esperado).

### Integração MCP
- Cadastre o servidor MCP oficial (transporte *Streamable HTTP*): URL, token Bearer e cabeçalhos (guardados **criptografados**).
- *Testar conexão* faz o handshake e lista as ferramentas; o formulário de argumentos é montado a partir do schema da ferramenta.
- *Buscar casos oficiais* mostra a prévia; os escolhidos são importados para um plano novo/existente,
  guardando o ID oficial (`referencia`) para não duplicar em importações futuras.
- Dentro de um plano de teste, o botão **Importar casos oficiais (MCP)** traz os casos direto para aquele plano.
- O mapeamento aceita campos em português ou inglês (`titulo/title`, `passos/steps`, `resultado_esperado/expected_result`…)
  e listas em `casos`, `test_cases`, `items`, `data`… ou em `structuredContent`.

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
│   ├── CasoTeste/  (Entities, Enums/Prioridade|OrigemCaso, Repositories)
│   ├── Execucao/   (Entities, Enums/ResultadoExecucao, Repositories)
│   ├── Modulo/          # módulo + tag de departamento
│   ├── RegraNegocio/    # regra, fluxo PM/PO, ExtratorMarcadores (tags -> casos)
│   ├── Cofre/           # Documento, Categoria, Arquivo
│   ├── Backlog/         # Backlog, PadraoBacklog, Montador de prompt, Interpretador da resposta
│   └── IntegracaoMcp/   # ConexaoMcp, MapeadorCasosExternos
├── Application/                 # casos de uso (orquestram domínio + repositórios)
│   ├── Shared/TransactionManagerInterface.php
│   ├── PlanoTeste/  DTOs, Services/ResultadosDoPlano, UseCases/{Criar,Atualizar,Obter,Listar,Excluir,AlterarStatus}
│   ├── CasoTeste/   DTOs, UseCases/{Adicionar,Atualizar,Remover}CasoTeste
│   ├── Execucao/    DTOs, UseCases/{RegistrarExecucao,ListarExecucoesDoCaso}
│   ├── CasoTeste/Services/CriadorCasosEmPlano.php  # regra/MCP/backlog -> casos (sem duplicar)
│   ├── Modulo, RegraNegocio, Cofre, Backlog, IntegracaoMcp/UseCases
│   ├── Backlog/Ports/GeradorBacklogInterface.php     # porta da IA
│   └── IntegracaoMcp/Ports/ClienteMcpInterface.php   # porta do MCP
├── Infrastructure/              # detalhes técnicos
│   ├── Persistence/Eloquent/Models/        # models Eloquent (só persistência)
│   ├── Persistence/Eloquent/Repositories/  # implementações dos repositórios + mapeamento ↔ entidades
│   ├── Persistence/LaravelTransactionManager.php
│   ├── Storage/ArmazenamentoLocal.php        # arquivos do cofre
│   ├── IA/ClaudeGeradorBacklog.php           # Messages API do Claude
│   ├── Mcp/ClienteMcpHttp.php                # cliente MCP Streamable HTTP (JSON e SSE)
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
- `planos_teste` 1─N `casos_teste` (+ `origem`, `referencia`) 1─N `execucoes_teste`
- `modulos` 1─N `regras_negocio` 1─N `regras_negocio_versoes`
- `categorias_documento` 1─N `documentos` N─1 `modulos` / `regras_negocio`
- `padroes_backlog` 1─N `backlogs` · `conexoes_mcp`

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

### Novas rotas da API

| Recurso | Rotas |
|---|---|
| Módulos | `GET/POST /api/modulos`, `PUT/DELETE /api/modulos/{id}` |
| Regras | `GET/POST /api/regras`, `GET/PUT/DELETE /api/regras/{id}`, `POST /api/regras/analisar`, `POST /api/regras/{id}/fluxo/{enviar\|retornar\|reabrir}`, `POST /api/regras/{id}/parecer`, `GET /api/regras/{id}/versoes`, `POST /api/regras/{id}/gerar-plano` |
| Cofre | `GET/POST/PUT/DELETE /api/cofre/categorias`, `GET/POST /api/cofre/documentos` (multipart), `PUT/DELETE /api/cofre/documentos/{id}`, `GET /api/cofre/documentos/{id}/download` |
| Backlog | `GET /api/backlog/configuracao`, `GET/POST/PUT/DELETE /api/backlog/padroes`, `GET/POST /api/backlogs`, `GET/DELETE /api/backlogs/{id}`, `POST /api/backlogs/{id}/resposta`, `POST /api/backlogs/{id}/gerar-plano` |
| MCP | `GET/POST /api/mcp/conexoes`, `PUT/DELETE /api/mcp/conexoes/{id}`, `POST /api/mcp/conexoes/{id}/testar`, `POST /api/mcp/conexoes/{id}/casos`, `POST /api/mcp/conexoes/{id}/importar` |

## Frontend

```
frontend/src/
├── api/          # client fetch + planoTesteApi (todas as chamadas)
├── types/        # tipos espelhando a API
├── lib/          # markdown (render das tags), utilidades (diff, export)
├── components/   # Modal, abas, upload, destino de plano, ImportadorMcp, editor da regra…
└── pages/        # Planos, Regras/Regra, Cofre, Backlogs/BacklogNovo/BacklogDetalhe, IntegracaoMcp, Modulos
```

O Vite faz proxy de `/api` para o container da API, então não há configuração de CORS.

## Rodando sem Docker (opcional)

```bash
cd backend && cp .env.example .env   # ajuste DB_HOST=127.0.0.1 e DB_PORT=3310
composer install && php artisan key:generate && php artisan migrate --seed && php artisan serve --port=8090
cd ../frontend && npm install && npm run dev
```
