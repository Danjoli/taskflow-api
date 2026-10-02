# TaskFlow API

API REST para gerenciamento de tarefas, desenvolvida com Laravel.

## Tecnologias

- PHP 8.5
- Laravel 13
- PostgreSQL 18
- Pest 5
- PHPUnit 13
- Composer

## Requisitos

Para executar o projeto localmente, é necessário ter:

- PHP 8.5 ou versão compatível com as dependências
- Composer
- PostgreSQL
- Redis ou Docker Desktop
- Git

## Instalação

Clone o repositório:

```bash
git clone https://github.com/Danjoli/taskflow-api.git
cd taskflow-api
```

Instale as dependências:

```bash
composer install
```

Crie o arquivo de ambiente.

No PowerShell:

```powershell
Copy-Item .env.example .env
```

Gere a chave da aplicação:

```bash
php artisan key:generate
```

## Configuração do banco de dados

Crie um banco PostgreSQL para desenvolvimento e configure o arquivo `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=taskflow_dev
DB_USERNAME=taskflow_app
DB_PASSWORD=
```

Preencha `DB_PASSWORD` com a senha local do usuário PostgreSQL.

O banco e o usuário devem existir antes da execução das migrations.

Execute:

```bash
php artisan migrate
```

## Configuração do Redis

O projeto usa o cliente PHP `predis`, portanto não exige a extensão nativa
`phpredis`. Para iniciar o Redis local com Docker:

```bash
docker compose up -d redis
```

As configurações padrão do `.env.example` usam três bancos separados:

```dotenv
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_DB=2
```

Para provedores que entregam uma URL completa, preencha `REDIS_URL`. Nunca
adicione senhas ou URLs reais ao repositório.

Valide cada conexão configurada:

```bash
php artisan redis:check
php artisan redis:check cache
php artisan redis:check queue
```

Para interromper o serviço local:

```bash
docker compose stop redis
```

## Executando a aplicação

Inicie o servidor de desenvolvimento:

```bash
php artisan serve
```

A aplicação estará disponível, por padrão, em:

http://127.0.0.1:8000

## Ambiente de testes

Crie um banco PostgreSQL separado para testes, chamado `taskflow_test`.

Crie o arquivo `.env.testing` a partir do `.env`:

```powershell
Copy-Item .env .env.testing
```

No `.env.testing`, configure:

```dotenv
APP_ENV=testing
DB_CONNECTION=pgsql
DB_DATABASE=taskflow_test
```

Mantenha os demais parâmetros de conexão adequados ao seu PostgreSQL local.

Execute as migrations no banco de testes:

```bash
php artisan migrate --env=testing
```

Execute os testes:

```bash
php artisan test
```

Ou diretamente com Pest:

```powershell
.\vendor\bin\pest
```

**Importante:** os testes de integração devem utilizar exclusivamente o banco `taskflow_test`. Não utilize o banco de desenvolvimento ou produção para executar testes.

## Qualidade de código

Verifique a formatação:

```powershell
.\vendor\bin\pint --test
```

Aplique as correções de formatação:

```powershell
.\vendor\bin\pint
```

Verifique as dependências:

```bash
composer validate
composer check-platform-reqs
```

## Status

Projeto em desenvolvimento.

Funcionalidades de autenticação, gerenciamento de tarefas, permissões e documentação da API serão implementadas nas próximas etapas.
