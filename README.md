# TaskFlow API

[![CI](https://github.com/Danjoli/taskflow-api/actions/workflows/ci.yml/badge.svg)](https://github.com/Danjoli/taskflow-api/actions/workflows/ci.yml)

API REST multiusuário para organizar tarefas, projetos, categorias, tags,
subtarefas, comentários e prazos. Construída com Laravel, autenticação via
Sanctum, PostgreSQL como banco principal e Redis para cache e filas.

## Funcionalidades

- Cadastro, login, logout e identificação do usuário autenticado.
- CRUD de tarefas, projetos, categorias e tags com isolamento por conta.
- Prioridade, status, prazo, projeto, categoria e múltiplas tags por tarefa.
- Subtarefas com um nível de profundidade.
- Filtros combináveis, ordenação e paginação.
- Comentários e histórico imutável de criação e alterações.
- Lembretes de prazo em filas, respeitando o fuso horário do usuário.
- Cache Redis para leituras individuais, com invalidação automática.
- Rate limiting para cadastro, login e rotas autenticadas.
- OpenAPI 3.1, análise estática, testes de integração e CI.

## Tecnologias

| Área | Tecnologia |
| --- | --- |
| Aplicação | PHP 8.5, Laravel 13 |
| Autenticação | Laravel Sanctum 4 |
| Persistência | PostgreSQL 18 |
| Cache e filas | Redis 7, Predis 3 |
| Testes | Pest 5, PHPUnit 13 |
| Qualidade | Larastan/PHPStan, Laravel Pint |
| Automação | GitHub Actions |

## Arquitetura

O projeto segue a estrutura convencional do Laravel, com separação por
responsabilidade e Form Requests agrupados por domínio:

```text
app/
├── Console/Commands    # diagnósticos, lembretes e prontidão de produção
├── Data                # objetos de transporte internos
├── Enums               # status, prioridade, prazo e tipos de atividade
├── Http
│   ├── Controllers/Api # orquestração HTTP
│   ├── Requests        # validação e autorização por domínio
│   └── Resources       # contratos explícitos de resposta JSON
├── Jobs                # processamento assíncrono e lembretes
├── Models              # entidades e relacionamentos Eloquent
├── Notifications       # notificações de prazo
├── Observers           # invalidação de cache
├── Policies            # autorização por proprietário
└── Services            # cache e registro de atividades
```

As requisições são validadas por Form Requests, as Policies restringem cada
recurso ao proprietário e os Resources controlam os campos expostos. Operações
compostas de tarefas e histórico usam transações quando precisam ser atômicas.

## Documentação

- [Especificação OpenAPI 3.1](docs/openapi.json)
- [Guia com requisições e respostas](docs/api-examples.md)
- [Coleção executável para clientes HTTP](docs/taskflow-api.http)
- [Runbook de deploy e rollback](docs/deployment.md)

## Requisitos locais

- Docker Desktop com Docker Compose, para o fluxo recomendado; ou
- PHP 8.3 ou superior, Composer 2, PostgreSQL, Redis, Node.js e npm, para o
  fluxo manual.
- Git.

As versões usadas pelo CI são PHP 8.5 e PostgreSQL 18.

## Ambiente Docker

O ambiente Docker inclui Nginx, PHP-FPM, PostgreSQL, Redis, worker de filas,
scheduler e Vite. Na primeira execução, a imagem instala as dependências e o
serviço `setup` aplica as migrations automaticamente:

```bash
docker compose up -d --build
```

A API ficará disponível em `http://localhost:8000`. Confira o estado dos
serviços e o health check da aplicação:

```bash
docker compose ps
curl http://localhost:8000/up
```

Execute comandos Laravel e verificações dentro do container:

```bash
docker compose exec app php artisan about
docker compose run --rm test
docker compose exec app composer analyse
docker compose exec app vendor/bin/pint --test
```

Os testes usam o banco isolado `taskflow_test`; o serviço de teste nunca aponta
para o banco `taskflow_dev` da aplicação.

Para acompanhar os processos ou encerrar o ambiente:

```bash
docker compose logs -f app worker scheduler
docker compose down
```

Os dados do PostgreSQL, Redis e as dependências ficam em volumes nomeados. Use
`docker compose down -v` somente quando quiser apagar deliberadamente todos os
dados locais desse ambiente. As portas podem ser alteradas em `.env` por meio
de `APP_PORT`, `VITE_PORT`, `DB_FORWARD_PORT` e `REDIS_FORWARD_PORT`.

## Instalação rápida

Para executar sem Docker, clone e prepare a aplicação:

```bash
git clone https://github.com/Danjoli/taskflow-api.git
cd taskflow-api
composer install
```

Crie o arquivo de ambiente e a chave:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

No Linux ou macOS, use `cp .env.example .env`.

Crie o banco PostgreSQL e configure estas variáveis no `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=taskflow_dev
DB_USERNAME=taskflow_app
DB_PASSWORD=
```

Inicie o Redis local e finalize a instalação:

```bash
docker compose up -d redis
php artisan migrate
npm install
npm run build
```

O cliente `predis` já faz parte do projeto; a extensão nativa `phpredis` não é
obrigatória. O `.env.example` separa os bancos Redis de dados gerais, cache e
filas.

## Executando localmente

O comando abaixo inicia servidor, worker, logs e Vite em conjunto:

```bash
composer run dev
```

Para iniciar somente a API:

```bash
php artisan serve
```

A aplicação ficará disponível em `http://127.0.0.1:8000` e a API em
`http://127.0.0.1:8000/api`.

Para processar filas separadamente:

```bash
php artisan queue:work redis --queue=default --sleep=1 --tries=3 --timeout=60
```

Valide as conexões Redis com:

```bash
php artisan redis:check
php artisan redis:check cache
php artisan redis:check queue
```

## Endpoints principais

Todas as rotas protegidas usam `Authorization: Bearer <token>`.

| Grupo | Método e rota | Finalidade |
| --- | --- | --- |
| Autenticação | `POST /api/register` | Criar conta e emitir token |
| Autenticação | `POST /api/login` | Autenticar e emitir token |
| Autenticação | `GET /api/me` | Consultar usuário atual |
| Autenticação | `PATCH /api/me/preferences` | Alterar fuso e lembretes |
| Autenticação | `POST /api/logout` | Revogar o token atual |
| Tarefas | `GET, POST /api/tasks` | Listar, filtrar e criar |
| Tarefas | `GET, PATCH, DELETE /api/tasks/{task}` | Consultar, atualizar e excluir |
| Comentários | `GET, POST /api/tasks/{task}/comments` | Listar e comentar |
| Comentários | `DELETE /api/tasks/{task}/comments/{comment}` | Excluir comentário |
| Atividades | `GET /api/tasks/{task}/activities` | Consultar histórico |
| Projetos | `GET, POST /api/projects` | Listar e criar |
| Projetos | `GET, PATCH, DELETE /api/projects/{project}` | CRUD individual |
| Categorias | `GET, POST /api/categories` | Listar e criar |
| Categorias | `GET, PATCH, DELETE /api/categories/{category}` | CRUD individual |
| Tags | `GET, POST /api/tags` | Listar e criar |
| Tags | `GET, PATCH, DELETE /api/tags/{tag}` | CRUD individual |

Filtros de tarefas: `status`, `priority`, `due_date`, `project_id`,
`category_id`, `tag_id`, `parent_id` e `deadline`. Consulte o
[guia de exemplos](docs/api-examples.md) para o fluxo completo.

## Demonstração rápida

Cadastre um usuário fictício:

```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name":"Usuário Exemplo","email":"usuario@example.test","password":"Password123!","password_confirmation":"Password123!"}'
```

Use o valor retornado em `data.token` para criar uma tarefa:

```bash
curl -X POST http://127.0.0.1:8000/api/tasks \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"title":"Conhecer a TaskFlow API","priority":"high"}'
```

Os exemplos usam apenas localhost, o domínio reservado `example.test` e
placeholders. Nunca registre tokens ou credenciais reais no repositório.

## Testes e qualidade

Crie um banco PostgreSQL separado chamado `taskflow_test`, copie `.env` para
`.env.testing` e defina:

```dotenv
APP_ENV=testing
DB_CONNECTION=pgsql
DB_DATABASE=taskflow_test
```

Não execute os testes contra bancos de desenvolvimento ou produção.

```bash
php artisan migrate --env=testing
php artisan test --compact
composer analyse
vendor/bin/pint --test
composer validate --strict
```

O workflow [CI](.github/workflows/ci.yml) executa migrations e todas essas
verificações em PostgreSQL a cada Pull Request direcionado à `main`.

## Produção

O arquivo [`.env.production.example`](.env.production.example) lista as
variáveis de produção sem incluir segredos. Valide o ambiente antes do deploy:

```bash
php artisan app:production-check
```

O [runbook de produção](docs/deployment.md) cobre build reproduzível, migrations,
workers, scheduler, observabilidade, health check e rollback. O endpoint `/up`
é destinado a balanceadores e monitores de disponibilidade.

## Segurança e operação

- Senhas são armazenadas com hash pelo Laravel.
- Policies e queries por usuário evitam acesso entre contas.
- Campos de propriedade são proibidos nas entradas e omitidos das respostas.
- Login, cadastro e API autenticada possuem limites configuráveis.
- Logs de produção vão para `stderr`; segredos devem ser injetados pelo provedor.
- O cache de tarefas é isolado por usuário e invalidado após alterações.
- O scheduler usa locks compartilhados para evitar execuções duplicadas.

## Status

O escopo planejado da API está implementado, documentado e coberto pelo CI.
Melhorias futuras devem ser abertas como issues e integradas por Pull Request.
