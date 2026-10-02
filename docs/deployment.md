# Deploy em produção

Este runbook descreve um deploy independente de provedor. Ele não contém
segredos nem substitui os recursos gerenciados da plataforma escolhida.

## Infraestrutura mínima

- PHP 8.5 com as extensões exigidas pelo Composer.
- PostgreSQL 18 ou versão compatível.
- Redis compartilhado para cache, sessões, filas e locks do scheduler.
- Um processo web, pelo menos um worker de filas e um scheduler.
- Serviço SMTP transacional e coleta dos logs enviados ao `stderr`.
- HTTPS encerrado no balanceador ou servidor web.

Use [`.env.production.example`](../.env.production.example) como checklist.
Preencha os valores secretos no gerenciador de segredos da plataforma, nunca
em um arquivo versionado. Gere `APP_KEY` uma única vez com
`php artisan key:generate --show` e preserve-a entre deploys.

Antes de publicar, valide as variáveis já injetadas:

```bash
php artisan app:production-check
```

O comando falha quando debug, HTTPS, banco, cache, filas, logs, cookies ou mail
não estão configurados de forma apropriada para produção.

## Build

O build deve ser reproduzível e não deve depender do estado do servidor:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan optimize
```

O diretório publicado deve incluir o código, `vendor` e `public/build`. Garanta
permissão de escrita apenas em `storage` e `bootstrap/cache`. Em ambientes com
sistema de arquivos efêmero, não armazene uploads, sessões, cache ou logs no
disco local.

## Deploy

Execute contra a nova versão antes de direcionar tráfego:

```bash
php artisan app:production-check
php artisan migrate --force
```

Depois da ativação:

```bash
php artisan queue:restart
curl --fail --silent --show-error https://api.example.com/up
```

O endpoint `/up` confirma que a aplicação inicializou. O monitor externo deve
verificá-lo por HTTPS e alertar após falhas consecutivas. Em plataformas que
gerenciam workers automaticamente, como Laravel Cloud, não execute
`queue:restart`; use o ciclo de processos da própria plataforma.

## Worker de filas

Mantenha o worker sob um process manager (Supervisor, systemd ou serviço
gerenciado):

```bash
php artisan queue:work redis \
  --queue=default \
  --sleep=1 \
  --tries=3 \
  --backoff=5 \
  --timeout=60 \
  --max-time=3600
```

`REDIS_QUEUE_RETRY_AFTER=90` permanece maior que o timeout de 60 segundos, o
que reduz o risco de processamento simultâneo da mesma reserva. Monitore
`php artisan queue:failed` e defina alertas para falhas e crescimento da fila.

## Scheduler

Execute em todos os nós ou em um serviço dedicado:

```cron
* * * * * cd /var/www/taskflow-api && php artisan schedule:run >> /dev/null 2>&1
```

A tarefa de lembretes usa `onOneServer()` e `withoutOverlapping()`. Todos os
nós precisam compartilhar o mesmo cache Redis para que os locks funcionem.
Confira a configuração com `php artisan schedule:list`.

## Observabilidade

- Envie logs estruturados ao `stderr` com `LOG_STACK=stderr`.
- Monitore disponibilidade e latência do `/up`, taxa de respostas 5xx e 429.
- Acompanhe conexões PostgreSQL e Redis, uso de memória e espaço em disco.
- Alerte para jobs em `failed_jobs`, filas acumuladas e scheduler sem execução.
- Registre o SHA do commit e o horário de cada release no provedor.
- Nunca registre tokens, senhas, URLs Redis autenticadas ou corpos sensíveis.

## Rollback

1. Interrompa a promoção se o build, a migration ou o health check falhar.
2. Reative o artefato ou imagem da release anterior.
3. Reinicie os workers para carregarem o código restaurado.
4. Confirme `/up`, um login controlado e o processamento de uma fila.
5. Investigue logs antes de tentar outro deploy.

Migrações devem ser retrocompatíveis durante a janela de deploy. Prefira o
padrão expandir/migrar/contrair: primeiro adicione estruturas compatíveis,
migre dados em etapa separada e só remova estruturas antigas em uma release
posterior. Não use `migrate:rollback` automaticamente: migrations destrutivas
podem perder dados. Restaure o banco apenas a partir de backup validado e com
aprovação explícita.

## Checklist de liberação

- [ ] Backup recente do PostgreSQL testado.
- [ ] Segredos e `APP_KEY` injetados pelo provedor.
- [ ] `app:production-check` aprovado.
- [ ] Testes, PHPStan e Pint aprovados no commit publicado.
- [ ] Migration revisada quanto a locks e retrocompatibilidade.
- [ ] Worker e scheduler configurados.
- [ ] `/up`, logs e alertas operacionais.
- [ ] Artefato anterior disponível para rollback.
