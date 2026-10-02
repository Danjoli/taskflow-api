# Postman

Esta pasta contém uma coleção executável e um ambiente local sem credenciais
reais:

- `TaskFlow-API.postman_collection.json`
- `TaskFlow-Local.postman_environment.json`

## Executar no Postman

1. Inicie a aplicação com `docker compose up -d --build`.
2. Importe os dois arquivos no Postman.
3. Selecione o ambiente **TaskFlow — Local**.
4. Abra a coleção **TaskFlow API — End-to-End** e use **Run collection**.
5. Mantenha a ordem original e execute uma iteração.

O fluxo gera um e-mail exclusivo em `example.test`, cadastra um usuário, salva
o token e os identificadores no ambiente, percorre os 29 endpoints e remove os
recursos criados. O token e os identificadores são apagados do ambiente ao
final.

## Executar pelo Docker

Com o ambiente Docker disponível, execute:

```bash
docker compose run --rm postman
```

O serviço usa Newman dentro da rede do Compose, sem exigir instalação global
do Postman CLI ou do Newman. Uma falha de requisição ou asserção resulta em
código de saída diferente de zero.

## Segurança

Os valores versionados são exclusivamente locais e fictícios. Não substitua o
arquivo de ambiente por uma exportação que contenha tokens, credenciais ou URLs
privadas. Para outros ambientes, crie uma cópia local ignorada pelo Git e use o
tipo `secret` para valores sensíveis.
