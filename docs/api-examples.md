# Exemplos de uso da TaskFlow API

Este guia usa `http://127.0.0.1:8000/api` e dados fictícios. Nunca grave um
token real no repositório. A especificação completa está em
[`openapi.json`](openapi.json).

Todas as requisições e respostas usam JSON. Nas rotas protegidas, envie o
token do cadastro ou login no cabeçalho:

```http
Authorization: Bearer <SEU_TOKEN>
Accept: application/json
```

## 1. Cadastrar e autenticar

Cadastre uma conta:

```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name":"Usuário Exemplo","email":"usuario@example.test","password":"Password123!","password_confirmation":"Password123!"}'
```

Resposta `201 Created`:

```json
{
  "message": "Usuário cadastrado com sucesso.",
  "data": {
    "user": {
      "id": 1,
      "name": "Usuário Exemplo",
      "email": "usuario@example.test",
      "timezone": "UTC",
      "deadline_notifications_enabled": true
    },
    "token": "<TOKEN_GERADO>"
  }
}
```

Para entrar novamente:

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"usuario@example.test","password":"Password123!"}'
```

Guarde o valor de `data.token` apenas no ambiente local. Nos próximos exemplos,
substitua `<SEU_TOKEN>` pelo token retornado.

## 2. Criar recursos de organização

Crie um projeto:

```bash
curl -X POST http://127.0.0.1:8000/api/projects \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name":"Lançamento","description":"Entrega da primeira versão"}'
```

Crie uma categoria e duas tags:

```bash
curl -X POST http://127.0.0.1:8000/api/categories \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name":"Backend"}'

curl -X POST http://127.0.0.1:8000/api/tags \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name":"urgente"}'

curl -X POST http://127.0.0.1:8000/api/tags \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name":"api"}'
```

Anote os identificadores retornados em `data.id`. Os exemplos seguintes usam
projeto `1`, categoria `1` e tags `1` e `2`.

## 3. Fluxo CRUD de tarefas

Crie uma tarefa associada aos recursos anteriores:

```bash
curl -X POST http://127.0.0.1:8000/api/tasks \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"title":"Publicar API","description":"Revisar e publicar a versão","priority":"high","due_date":"2026-10-10","project_id":1,"category_id":1,"tag_ids":[1,2]}'
```

Resposta `201 Created` resumida:

```json
{
  "data": {
    "id": 1,
    "project_id": 1,
    "category_id": 1,
    "parent_id": null,
    "tags": [
      { "id": 1, "name": "urgente" },
      { "id": 2, "name": "api" }
    ],
    "title": "Publicar API",
    "status": "pending",
    "priority": "high",
    "due_date": "2026-10-10",
    "is_overdue": false
  }
}
```

Consulte a tarefa:

```bash
curl http://127.0.0.1:8000/api/tasks/1 \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Accept: application/json"
```

O cabeçalho `X-Task-Cache` será `MISS` na primeira leitura e `HIT` enquanto a
resposta permanecer no cache.

Atualize somente os campos necessários:

```bash
curl -X PATCH http://127.0.0.1:8000/api/tasks/1 \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"status":"in_progress","priority":"medium"}'
```

Crie uma subtarefa usando `parent_id`:

```bash
curl -X POST http://127.0.0.1:8000/api/tasks \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"title":"Revisar documentação","parent_id":1}'
```

Somente um nível de subtarefas é permitido.

## 4. Listar, filtrar e paginar

Liste tarefas de alta prioridade do projeto, ordenadas pelo prazo:

```bash
curl "http://127.0.0.1:8000/api/tasks?project_id=1&priority=high&sort_by=due_date&sort_direction=asc&page=1" \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Accept: application/json"
```

Filtros disponíveis: `status`, `priority`, `due_date`, `project_id`,
`category_id`, `tag_id`, `parent_id` e `deadline`. Para tarefas próximas do
prazo, use `deadline=due_soon&days=7`; para atrasadas, `deadline=overdue`.

As coleções de tarefas, projetos, categorias e tags retornam 15 itens por
página. Comentários e atividades retornam 20. A resposta contém `data`,
`links` e `meta`:

```json
{
  "data": [],
  "links": { "first": "...page=1", "last": "...page=1", "prev": null, "next": null },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 0 }
}
```

## 5. Comentários e histórico

Adicione um comentário:

```bash
curl -X POST http://127.0.0.1:8000/api/tasks/1/comments \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"body":"Pronta para revisão"}'
```

Liste comentários e o histórico automático de criação e alterações:

```bash
curl http://127.0.0.1:8000/api/tasks/1/comments \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Accept: application/json"

curl http://127.0.0.1:8000/api/tasks/1/activities \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Accept: application/json"
```

O histórico é somente leitura. Um comentário pode ser excluído com
`DELETE /api/tasks/1/comments/1`.

## 6. Preferências, exclusão e logout

Configure fuso e lembretes:

```bash
curl -X PATCH http://127.0.0.1:8000/api/me/preferences \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"timezone":"America/Sao_Paulo","deadline_notifications_enabled":true}'
```

Exclua a tarefa e encerre a sessão:

```bash
curl -X DELETE http://127.0.0.1:8000/api/tasks/1 \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Accept: application/json"

curl -X POST http://127.0.0.1:8000/api/logout \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Accept: application/json"
```

Exclusões bem-sucedidas retornam `204 No Content`. O logout revoga apenas o
token usado na requisição.

## 7. Erros comuns

- `401 Unauthorized`: token ausente, inválido ou revogado.
- `403 Forbidden`: tentativa de acessar um recurso de outra conta.
- `404 Not Found`: identificador inexistente ou relação aninhada incorreta.
- `422 Unprocessable Content`: dados inválidos; detalhes aparecem em `errors`.
- `429 Too Many Requests`: limite excedido; consulte o cabeçalho `Retry-After`.

Exemplo de validação `422`:

```json
{
  "message": "The title field is required.",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

Limites padrão: 5 tentativas de login por minuto, 3 cadastros por hora e 120
requisições autenticadas por minuto. Eles podem ser alterados pelas variáveis
`RATE_LIMIT_LOGIN_PER_MINUTE`, `RATE_LIMIT_REGISTER_PER_HOUR` e
`RATE_LIMIT_API_PER_MINUTE`.
